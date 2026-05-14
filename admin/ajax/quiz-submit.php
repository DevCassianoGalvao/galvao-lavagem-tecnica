<?php
require_once __DIR__ . '/../_bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = mvp_pdo();
    $logger = new SecurityLogger($pdo);
    (new RateLimitService($pdo, $logger))->requireAllowed('mvp_quiz_submit', 8, 3600);

    $name = clean_text($_POST['name'] ?? '');
    $phone = clean_text($_POST['phone'] ?? '');

    if ($name === '' || $phone === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Informe nome e WhatsApp.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $service = mvp_service();
    $leadId = $service->createLead($_POST, $_FILES['images'] ?? []);
    $lead = $service->lead($leadId);

    if ($lead) {
        (new LeadNotificationService($config))->notifyNewLead($lead);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Recebemos suas informações e entraremos em contato em breve pelo WhatsApp.',
        'lead_id' => $leadId,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => APP_DEBUG ? $exception->getMessage() : 'Não foi possível enviar agora.',
    ], JSON_UNESCAPED_UNICODE);
}

