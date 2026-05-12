<?php

declare(strict_types=1);

require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
SecurityService::requireJsonPost();

try {
    $pdo = Connection::get($config);
    $logger = new SecurityLogger($pdo);
    SecurityService::requireJsonCsrf($logger);

    if (APP_ENV !== 'local') {
        $rateLimit = new RateLimitService($pdo, $logger);
        if (!$rateLimit->hit('landing_ai_visual', 3, 3600)) {
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'message' => 'Limite de simulações atingido. Tente novamente em alguns minutos.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $uploadService = new ImageUploadService($pdo, null, new UploadSecurityService($logger));
    $sourceUpload = $uploadService->storeUploadedFile($_FILES['environment_image'] ?? [], 'ai-images/landing-originals');

    $result = (new AiVisualService($pdo, $config, new AiLogger($pdo)))->simulateRevitalization($sourceUpload, [
        'caption' => 'Simulação IA - Landing Galvão Lavagem Técnica',
        'created_by' => null,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Simulação visual gerada. Use como prévia; a avaliação final depende do diagnóstico técnico no local.',
        'simulation' => $result,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => APP_DEBUG ? $exception->getMessage() : 'Não foi possível gerar a simulação agora.',
    ], JSON_UNESCAPED_UNICODE);
}
