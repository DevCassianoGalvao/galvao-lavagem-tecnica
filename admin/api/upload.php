<?php

require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
SecurityService::requireJsonPost();

try {
    $pdo = Connection::get($config);
    $logger = new SecurityLogger($pdo);
    SecurityService::requireJsonCsrf($logger);
    (new RateLimitService($pdo, $logger))->requireAllowed('ai_visual_upload', 6, 3600);

    $leadId = isset($_POST['lead_id']) && $_POST['lead_id'] !== '' ? (int) $_POST['lead_id'] : null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $sessionId = session_id();

    $usage = new AiImageUsageService($pdo, $config);
    $usage->assertAllowed($leadId, $ipAddress, $sessionId);

    $uploadService = new ImageUploadService($pdo, auth_id(), new UploadSecurityService($logger));
    $sourceUpload = $uploadService->storeUploadedFile($_FILES['environment_image'] ?? []);

    $logger->log('info', 'upload_received', 'Upload recebido para simulacao visual.', [
        'upload_id' => $sourceUpload['upload_id'],
        'lead_id' => $leadId,
    ]);
    (new AuditLogService($pdo))->write('admin', 'info', 'upload_received', 'Upload recebido para simulacao visual.', [
        'upload_id' => $sourceUpload['upload_id'],
        'lead_id' => $leadId,
        'mime_type' => $sourceUpload['mime_type'],
    ], auth_id(), 'upload', (int) $sourceUpload['upload_id']);

    if ((string) ($_POST['async'] ?? '') === '1') {
        $jobId = (new QueueService($pdo, new AuditLogService($pdo)))->enqueue('ai_visual', [
            'source_upload_id' => (int) $sourceUpload['upload_id'],
            'lead_id' => $leadId,
            'ip_address' => $ipAddress,
            'session_id' => $sessionId,
            'relations' => [
                'lead_id' => $leadId,
                'caption' => 'Simulacao IA - Galvao Lavagem Tecnica',
                'created_by' => auth_id(),
            ],
        ], 6, null, 2);

        echo json_encode([
            'success' => true,
            'message' => 'Simulacao visual enfileirada.',
            'job_id' => $jobId,
            'next_cta' => 'Receber diagnostico tecnico.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $ai = new AiVisualService($pdo, $config, new AiLogger($pdo));
    $result = $ai->simulateRevitalization($sourceUpload, [
        'lead_id' => $leadId,
        'caption' => 'Simulacao IA - Galvao Lavagem Tecnica',
        'created_by' => auth_id(),
    ]);

    $usage->register($leadId, $ipAddress, $sessionId, $sourceUpload['upload_id'], $result['result_upload_id']);

    echo json_encode([
        'success' => true,
        'message' => 'Simulacao visual gerada.',
        'simulation' => $result,
        'next_cta' => 'Receber diagnostico tecnico.',
    ], JSON_UNESCAPED_UNICODE);
} catch (RuntimeException $exception) {
    $code = in_array($exception->getMessage(), ['limit', 'cooldown'], true) ? 200 : 422;
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'soft_block' => in_array($exception->getMessage(), ['limit', 'cooldown'], true),
        'message' => 'Receber diagnostico tecnico.',
        'next_cta' => 'Receber diagnostico tecnico.',
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Nao foi possivel concluir a simulacao agora.',
        'next_cta' => 'Receber diagnostico tecnico.',
    ], JSON_UNESCAPED_UNICODE);
}
