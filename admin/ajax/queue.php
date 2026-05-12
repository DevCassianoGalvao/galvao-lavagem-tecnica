<?php

require_once __DIR__ . '/../../core/bootstrap.php';
require_auth();

header('Content-Type: application/json; charset=utf-8');

$pdo = Connection::get($config);
$logger = new SecurityLogger($pdo);
SecurityService::requireJsonPost();
SecurityService::requireJsonCsrf($logger);
(new RateLimitService($pdo, $logger))->requireAllowed('queue_admin', 30, 3600);

$role = auth_user()['role'] ?? 'viewer';

if (!in_array($role, ['owner', 'admin', 'manager'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permissao insuficiente para gerenciar filas.']);
    exit;
}

$audit = new AuditLogService($pdo);
$queue = new QueueService($pdo, $audit);
$action = clean_text($_POST['action'] ?? 'status');

try {
    $data = match ($action) {
        'run_once' => [
            'processed' => (new QueueWorker($pdo, $config, $queue))->run(max(1, min(10, (int) ($_POST['limit'] ?? 3)))),
        ],
        'enqueue_notifications' => [
            'job_id' => $queue->enqueueNotifications(),
        ],
        'retry' => (function () use ($queue): array {
            $queue->retry((int) ($_POST['job_id'] ?? 0));
            return ['job_id' => (int) ($_POST['job_id'] ?? 0)];
        })(),
        'cancel' => (function () use ($queue): array {
            $queue->cancel((int) ($_POST['job_id'] ?? 0));
            return ['job_id' => (int) ($_POST['job_id'] ?? 0)];
        })(),
        default => [
            'stats' => $queue->stats(),
            'jobs' => $queue->list([], 80),
        ],
    };

    echo json_encode([
        'success' => true,
        'message' => 'Fila atualizada.',
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    $logger->log('error', 'queue_action_failed', 'Falha ao operar fila.', [
        'action' => $action,
        'error' => $exception->getMessage(),
    ]);

    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Nao foi possivel executar a acao da fila.',
    ], JSON_UNESCAPED_UNICODE);
}
