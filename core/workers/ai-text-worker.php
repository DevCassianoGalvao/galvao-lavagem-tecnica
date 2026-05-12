<?php

require_once __DIR__ . '/../bootstrap.php';

$pdo = Connection::get($config);
$worker = new AiQueueWorker($pdo, $config);
$logger = new AiLogger($pdo);

try {
    $result = $worker->runOnce();

    if ($result === null) {
        $logger->info('ai_worker_idle', 'Nenhum job de IA textual pendente.');
        echo "Nenhum job pendente.\n";
        exit;
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $exception) {
    $logger->error('ai_worker_failed', 'Falha ao executar worker de IA textual.', [
        'error' => $exception->getMessage(),
    ]);

    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
