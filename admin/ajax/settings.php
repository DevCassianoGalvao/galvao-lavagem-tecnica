<?php

require_once __DIR__ . '/../../core/bootstrap.php';
require_auth();

header('Content-Type: application/json; charset=utf-8');

$pdo = Connection::get($config);
$logger = new SecurityLogger($pdo);
SecurityService::requireJsonPost();
SecurityService::requireJsonCsrf($logger);
(new RateLimitService($pdo, $logger))->requireAllowed('settings', 60, 3600);

$user = auth_user();
$role = $user['role'] ?? 'viewer';

if (!in_array($role, ['owner', 'admin'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permissao insuficiente.']);
    exit;
}

$action = clean_text($_POST['action'] ?? 'save_settings');
$settings = new SettingsService($pdo);

try {
    match ($action) {
        'save_settings' => saveSettings($settings, $logger),
        'create_user' => createUser($pdo, $logger),
        'update_user' => updateUser($pdo, $logger),
        'reset_password' => resetPassword($pdo, $logger),
        default => throw new InvalidArgumentException('Acao invalida.'),
    };
} catch (Throwable $exception) {
    $logger->log('error', 'settings_error', 'Falha ao salvar configuracoes.', [
        'action' => $action,
        'error' => $exception->getMessage(),
    ]);

    $message = $exception instanceof InvalidArgumentException
        ? $exception->getMessage()
        : 'Nao foi possivel processar a solicitacao.';

    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $message]);
}

function saveSettings(SettingsService $settings, SecurityLogger $logger): void
{
    $payload = [];
    $postedSettings = is_array($_POST['settings'] ?? null) ? $_POST['settings'] : $_POST;

    foreach (SettingsService::allowedKeys() as $key) {
        if (array_key_exists($key, $postedSettings)) {
            $payload[$key] = (string) $postedSettings[$key];
        }
    }

    $settings->saveMany($payload, auth_id());
    $logger->log('info', 'settings_updated', 'Configuracoes atualizadas.', [
        'user_id' => auth_id(),
        'keys' => array_keys($payload),
    ]);
    (new AuditLogService($GLOBALS['pdo'] ?? Connection::get($GLOBALS['config'])))->write('admin', 'info', 'settings_updated', 'Configuracoes atualizadas.', [
        'keys' => array_keys($payload),
    ], auth_id(), 'settings', null);

    echo json_encode(['success' => true, 'message' => 'Configuracoes salvas com seguranca.']);
}

function createUser(PDO $pdo, SecurityLogger $logger): void
{
    $name = clean_text($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = (string) ($_POST['password'] ?? '');
    $role = clean_text($_POST['role'] ?? 'operator');
    $status = clean_text($_POST['status'] ?? 'active');

    validateUserPayload($name, $email ?: '', $role, $status);

    if (strlen($password) < 10) {
        throw new InvalidArgumentException('A senha deve ter pelo menos 10 caracteres.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, phone, password_hash, role, status)
         VALUES (:name, :email, :phone, :password_hash, :role, :status)'
    );
    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'phone' => clean_text($_POST['phone'] ?? ''),
        'password_hash' => AuthService::hashPassword($password),
        'role' => $role,
        'status' => $status,
    ]);

    $logger->log('info', 'user_created', 'Usuario administrativo criado.', [
        'created_user_id' => (int) $pdo->lastInsertId(),
        'user_id' => auth_id(),
    ]);
    (new AuditLogService($pdo))->write('admin', 'info', 'user_created', 'Usuario administrativo criado.', [
        'created_user_id' => (int) $pdo->lastInsertId(),
        'email' => $email,
        'role' => $role,
    ], auth_id(), 'user', (int) $pdo->lastInsertId());

    echo json_encode(['success' => true, 'message' => 'Usuario criado.']);
}

function updateUser(PDO $pdo, SecurityLogger $logger): void
{
    $id = (int) ($_POST['user_id'] ?? 0);
    $name = clean_text($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $role = clean_text($_POST['role'] ?? 'operator');
    $status = clean_text($_POST['status'] ?? 'active');

    if ($id <= 0) {
        throw new InvalidArgumentException('Usuario invalido.');
    }

    validateUserPayload($name, $email ?: '', $role, $status);

    $stmt = $pdo->prepare(
        'UPDATE users
         SET name = :name, email = :email, phone = :phone, role = :role, status = :status
         WHERE id = :id AND deleted_at IS NULL'
    );
    $stmt->execute([
        'id' => $id,
        'name' => $name,
        'email' => $email,
        'phone' => clean_text($_POST['phone'] ?? ''),
        'role' => $role,
        'status' => $status,
    ]);

    $logger->log('info', 'user_updated', 'Usuario administrativo atualizado.', [
        'target_user_id' => $id,
        'user_id' => auth_id(),
    ]);
    (new AuditLogService($pdo))->write('admin', 'info', 'user_updated', 'Usuario administrativo atualizado.', [
        'target_user_id' => $id,
        'role' => $role,
        'status' => $status,
    ], auth_id(), 'user', $id);

    echo json_encode(['success' => true, 'message' => 'Usuario atualizado.']);
}

function resetPassword(PDO $pdo, SecurityLogger $logger): void
{
    $id = (int) ($_POST['user_id'] ?? 0);
    $password = (string) ($_POST['password'] ?? '');

    if ($id <= 0 || strlen($password) < 10) {
        throw new InvalidArgumentException('Informe uma nova senha com pelo menos 10 caracteres.');
    }

    $stmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id AND deleted_at IS NULL');
    $stmt->execute([
        'id' => $id,
        'password_hash' => AuthService::hashPassword($password),
    ]);

    $logger->log('warning', 'password_reset', 'Senha administrativa redefinida.', [
        'target_user_id' => $id,
        'user_id' => auth_id(),
    ]);
    (new AuditLogService($pdo))->write('admin', 'warning', 'password_reset', 'Senha administrativa redefinida.', [
        'target_user_id' => $id,
    ], auth_id(), 'user', $id);

    echo json_encode(['success' => true, 'message' => 'Senha redefinida.']);
}

function validateUserPayload(string $name, string $email, string $role, string $status): void
{
    if ($name === '' || $email === '') {
        throw new InvalidArgumentException('Nome e e-mail sao obrigatorios.');
    }

    if (!in_array($role, ['owner', 'admin', 'manager', 'operator', 'commercial', 'viewer'], true)) {
        throw new InvalidArgumentException('Permissao invalida.');
    }

    if (!in_array($status, ['active', 'inactive', 'blocked'], true)) {
        throw new InvalidArgumentException('Status invalido.');
    }
}
