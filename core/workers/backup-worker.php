<?php

require_once __DIR__ . '/../bootstrap.php';

$frequency = $argv[1] ?? 'daily';
$includeUploads = ($argv[2] ?? '1') !== '0';
$pdo = Connection::get($config);
$logger = new SecurityLogger($pdo);

try {
    $backup = (new BackupService($pdo, $config, $logger))->create($frequency, $includeUploads, null);
    echo 'Backup criado: ' . $backup['name'] . PHP_EOL;
} catch (Throwable $exception) {
    $logger->log('critical', 'backup_worker_failed', 'Worker de backup falhou.', ['error' => $exception->getMessage()]);
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
