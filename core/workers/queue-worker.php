<?php

require_once __DIR__ . '/../bootstrap.php';

$pdo = Connection::get($config);
$queue = new QueueService($pdo, new AuditLogService($pdo));
$worker = new QueueWorker($pdo, $config, $queue);
$limit = max(1, min(50, (int) ($argv[1] ?? 5)));

try {
    $processed = $worker->run($limit);

    if (!$processed) {
        echo "Nenhum job pendente.\n";
        exit;
    }

    echo json_encode([
        'processed' => $processed,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $exception) {
    $queue->log(0, 'error', 'worker_failed', 'Falha geral no worker de filas.', [
        'error' => $exception->getMessage(),
    ]);

    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
