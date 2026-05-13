<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

$isCli = PHP_SAPI === 'cli';
$force = $isCli
    ? in_array('--force', $argv ?? [], true)
    : (($_GET['force'] ?? '') === '1');
$installKey = (string) galvao_env('CPANEL_INSTALL_KEY', '');
$providedKey = (string) ($_GET['key'] ?? '');
$lockFile = STORAGE_PATH . '/install-cpanel.lock';

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
}

function installer_out(string $message, string $status = 'info'): void
{
    $prefix = match ($status) {
        'ok' => '[OK] ',
        'warn' => '[AVISO] ',
        'error' => '[ERRO] ',
        default => '',
    };

    if (PHP_SAPI === 'cli') {
        echo $prefix . $message . PHP_EOL;
        return;
    }

    $color = match ($status) {
        'ok' => '#6ee7a8',
        'warn' => '#e8c96a',
        'error' => '#ff9b9b',
        default => '#f4efe6',
    };

    echo '<p style="margin:8px 0;color:' . $color . '">' . htmlspecialchars($prefix . $message, ENT_QUOTES, 'UTF-8') . '</p>';
}

function installer_page_start(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    echo '<!doctype html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Instalador cPanel · Galvão Lavagem Técnica</title>';
    echo '<style>body{margin:0;background:#090909;color:#f4efe6;font-family:Arial,sans-serif;padding:32px}.box{max-width:820px;margin:auto;border:1px solid rgba(201,168,76,.25);border-radius:18px;background:#111;padding:28px}code{color:#e8c96a}.btn{display:inline-block;margin-top:12px;padding:12px 18px;border-radius:999px;background:#c9a84c;color:#090909;text-decoration:none;font-weight:700}</style>';
    echo '</head><body><main class="box"><h1>Instalador cPanel</h1>';
}

function installer_page_end(): void
{
    if (PHP_SAPI !== 'cli') {
        echo '</main></body></html>';
    }
}

function installer_exec(PDO $pdo, string $sql): void
{
    $pdo->exec($sql);
}

installer_page_start();

try {
    if (!$isCli) {
        if ($installKey === '') {
            installer_out('Defina CPANEL_INSTALL_KEY no arquivo .env antes de rodar este instalador pelo navegador.', 'error');
            installer_out('Exemplo: CPANEL_INSTALL_KEY=uma-chave-forte-aqui');
            installer_page_end();
            exit;
        }

        if (!hash_equals($installKey, $providedKey)) {
            installer_out('Chave de instalação inválida.', 'error');
            installer_out('Acesse usando: /tools/install-cpanel.php?key=SUA_CHAVE');
            installer_page_end();
            exit;
        }
    }

    if (is_file($lockFile) && !$force) {
        installer_out('A instalação já foi executada. Para rodar novamente, use --force no terminal ou &force=1 no navegador.', 'warn');
        installer_page_end();
        exit;
    }

    foreach ([STORAGE_PATH, STORAGE_PATH . '/uploads', STORAGE_PATH . '/temp', LOG_PATH] as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!is_writable($dir)) {
            throw new RuntimeException('Sem permissão de escrita em: ' . $dir);
        }
    }

    /** @var array<string, mixed> $config */
    global $config;
    $pdo = Connection::get($config);
    $pdo->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');

    installer_out('Conectado ao banco configurado em DB_NAME=' . (string) ($config['db_name'] ?? ''), 'ok');

    installer_exec($pdo, 'CREATE TABLE IF NOT EXISTS users (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(160) NOT NULL,
        phone VARCHAR(40) NULL,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(40) NOT NULL DEFAULT "owner",
        status VARCHAR(40) NOT NULL DEFAULT "active",
        last_login_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at DATETIME NULL,
        UNIQUE KEY uq_users_email (email),
        KEY idx_users_role_status (role, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    installer_exec($pdo, 'CREATE TABLE IF NOT EXISTS logs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NULL,
        level ENUM("debug", "info", "warning", "error", "critical") NOT NULL DEFAULT "info",
        channel VARCHAR(80) NOT NULL DEFAULT "app",
        action VARCHAR(120) NULL,
        entity_type VARCHAR(80) NULL,
        entity_id BIGINT UNSIGNED NULL,
        message TEXT NOT NULL,
        context_json JSON NULL,
        ip_address VARCHAR(45) NULL,
        user_agent VARCHAR(255) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_logs_level_created (level, created_at),
        KEY idx_logs_channel_created (channel, created_at),
        KEY idx_logs_action_created (action, created_at),
        KEY idx_logs_ip_created (ip_address, created_at),
        KEY idx_logs_entity (entity_type, entity_id),
        KEY idx_logs_user_created (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    installer_exec($pdo, 'CREATE TABLE IF NOT EXISTS rate_limits (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        rate_key CHAR(64) NOT NULL,
        action VARCHAR(80) NOT NULL,
        identity_hash CHAR(64) NOT NULL,
        attempts INT UNSIGNED NOT NULL DEFAULT 0,
        reset_at DATETIME NOT NULL,
        last_attempt_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_rate_limits_key (rate_key),
        KEY idx_rate_limits_action_identity (action, identity_hash),
        KEY idx_rate_limits_reset (reset_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    new MvpService($pdo);
    installer_out('Tabelas do MVP criadas ou atualizadas.', 'ok');

    $adminEmail = (string) galvao_env('ADMIN_EMAIL', 'admin@galvao.local');
    $adminPassword = (string) galvao_env('ADMIN_PASSWORD', 'Admin@12345');

    $stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password_hash, role, status, created_at, updated_at)
        VALUES (:name, :email, "", :password_hash, "owner", "active", NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            password_hash = VALUES(password_hash),
            role = VALUES(role),
            status = VALUES(status),
            updated_at = NOW()');
    $stmt->execute([
        'name' => 'Administrador Galvão',
        'email' => $adminEmail,
        'password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT),
    ]);

    file_put_contents($lockFile, 'Installed at ' . date('c') . PHP_EOL);

    installer_out('Administrador registrado para compatibilidade.', 'ok');
    installer_out('E-mail administrativo: ' . $adminEmail);
    installer_out('Instalação concluída. Remova este arquivo do servidor ou mantenha a chave CPANEL_INSTALL_KEY em segredo.', 'warn');
} catch (Throwable $exception) {
    installer_out($exception->getMessage(), 'error');
    installer_page_end();
    exit(1);
}

installer_page_end();
