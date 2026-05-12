<?php

require_once __DIR__ . '/../../core/bootstrap.php';
require_auth();

header('Content-Type: application/json; charset=utf-8');

$pdo = Connection::get($config);
$logger = new SecurityLogger($pdo);
SecurityService::requireJsonPost();
SecurityService::requireJsonCsrf($logger);
(new RateLimitService($pdo, $logger))->requireAllowed('backups', 12, 3600);

$user = auth_user();
$role = $user['role'] ?? 'viewer';

if (!in_array($role, ['owner', 'admin'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permissao insuficiente para backups.']);
    exit;
}

$service = new BackupService($pdo, $config, $logger);
$action = clean_text($_POST['action'] ?? 'status');

try {
    $result = match ($action) {
        'create' => $service->create(
            clean_text($_POST['frequency'] ?? 'daily'),
            (string) ($_POST['include_uploads'] ?? '1') === '1',
            auth_id()
        ),
        'cleanup' => ['removed' => $service->cleanup($_POST['frequency'] ?? null)],
        default => ['status' => $service->status(), 'backups' => $service->list()],
    };

    echo json_encode([
        'success' => true,
        'message' => $action === 'create' ? 'Backup criado com seguranca.' : 'Rotina processada.',
        'data' => $result,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    $logger->log('error', 'backup_action_failed', 'Falha na rotina de backup.', [
        'action' => $action,
        'error' => $exception->getMessage(),
    ]);

    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $exception instanceof InvalidArgumentException
            ? $exception->getMessage()
            : 'Nao foi possivel executar a rotina de backup.',
    ], JSON_UNESCAPED_UNICODE);
}
