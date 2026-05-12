<?php

require_once __DIR__ . '/../../core/bootstrap.php';
require_auth();

header('Content-Type: application/json; charset=utf-8');

$pdo = Connection::get($config);
$logger = new SecurityLogger($pdo);

SecurityService::requireJsonPost();
SecurityService::requireJsonCsrf($logger);
(new RateLimitService($pdo, $logger))->requireAllowed('service_complete', 30, 3600);

try {
    $serviceId = (int) ($_POST['service_id'] ?? 0);

    if ($serviceId <= 0) {
        throw new InvalidArgumentException('Servico invalido.');
    }

    $result = (new RecurrenceService($pdo, $logger))->completeService($serviceId, auth_id());

    echo json_encode([
        'success' => true,
        'message' => $result['created']
            ? 'Servico concluido e retorno preventivo criado.'
            : 'Servico ja possuia recorrencia preventiva.',
        'recurrence' => $result,
    ]);
} catch (Throwable $exception) {
    $logger->log('error', 'service_complete_failed', 'Falha ao concluir servico e criar recorrencia.', [
        'error' => $exception->getMessage(),
        'user_id' => auth_id(),
    ]);

    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $exception instanceof InvalidArgumentException
            ? $exception->getMessage()
            : 'Nao foi possivel criar a recorrencia.',
    ]);
}
