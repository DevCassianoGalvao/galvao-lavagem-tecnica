<?php

require_once __DIR__ . '/../../core/bootstrap.php';
require_auth();

$pdo = Connection::get($config);
$logger = new SecurityLogger($pdo);
$user = auth_user();
$role = $user['role'] ?? 'viewer';

if (!in_array($role, ['owner', 'admin'], true)) {
    http_response_code(403);
    exit;
}

(new RateLimitService($pdo, $logger))->requireAllowed('backup_download', 24, 3600);

try {
    $frequency = clean_text($_GET['frequency'] ?? 'daily');
    $filename = clean_text($_GET['file'] ?? '');
    $path = (new BackupService($pdo, $config, $logger))->downloadPath($frequency, $filename);

    $logger->log('warning', 'backup_downloaded', 'Backup baixado pelo painel administrativo.', [
        'user_id' => auth_id(),
        'filename' => basename($path),
    ]);

    $mimeType = str_ends_with($path, '.tar.gz') ? 'application/gzip' : 'application/zip';
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . basename($path) . '"');
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store');
    readfile($path);
} catch (Throwable $exception) {
    $logger->log('error', 'backup_download_failed', 'Falha ao baixar backup.', ['error' => $exception->getMessage()]);
    http_response_code(404);
}
