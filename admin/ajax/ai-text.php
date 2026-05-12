<?php

require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = Connection::get($config);
$logger = new SecurityLogger($pdo);
SecurityService::requireJsonPost();
SecurityService::requireJsonCsrf($logger);
(new RateLimitService($pdo, $logger))->requireAllowed('ai_text', 20, 3600);

$action = clean_text($_POST['action'] ?? 'process_lead');
$leadId = (int) ($_POST['lead_id'] ?? 0);

if ($leadId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Lead invalido.']);
    exit;
}

if ($action === 'queue_lead') {
    $jobs = new QueueService($pdo, new AuditLogService($pdo));

    echo json_encode([
        'success' => true,
        'message' => 'Analise textual enfileirada.',
        'job_id' => $jobs->enqueueAiText($leadId),
    ]);
    exit;
}

$service = new AiTextService($pdo, $config);

echo json_encode(
    $service->analyzeLead($leadId, filter_var($_POST['force'] ?? false, FILTER_VALIDATE_BOOLEAN)),
    JSON_UNESCAPED_UNICODE
);
