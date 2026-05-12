<?php

require_once __DIR__ . '/../../core/bootstrap.php';
require_auth();

header('Content-Type: application/json; charset=utf-8');

$pdo = Connection::get($config);
$logger = new SecurityLogger($pdo);

SecurityService::requireJsonPost();
SecurityService::requireJsonCsrf($logger);
(new RateLimitService($pdo, $logger))->requireAllowed('products', 80, 3600);

$action = clean_text($_POST['action'] ?? '');
$service = new ProductService($pdo);

try {
    $result = match ($action) {
        'save_product' => [
            'product_id' => $service->saveProduct($_POST, auth_id()),
            'message' => 'Produto salvo no catalogo tecnico.',
        ],
        'register_usage' => [
            'usage_id' => $service->registerUsage($_POST, auth_id()),
            'message' => 'Aplicacao registrada no historico operacional.',
        ],
        default => throw new InvalidArgumentException('Acao invalida.'),
    };

    $logger->log('info', 'product_' . $action, 'Modulo de produtos atualizado.', [
        'action' => $action,
        'user_id' => auth_id(),
    ]);
    (new AuditLogService($pdo))->write('operational', 'info', 'product_' . $action, 'Modulo de produtos atualizado.', [
        'action' => $action,
    ], auth_id(), 'product', (int) ($_POST['product_id'] ?? 0) ?: null);

    echo json_encode(['success' => true] + $result);
} catch (Throwable $exception) {
    $logger->log('error', 'product_error', 'Falha no modulo de produtos.', [
        'action' => $action,
        'error' => $exception->getMessage(),
        'user_id' => auth_id(),
    ]);

    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $exception instanceof InvalidArgumentException
            ? $exception->getMessage()
            : 'Nao foi possivel processar o produto.',
    ]);
}
