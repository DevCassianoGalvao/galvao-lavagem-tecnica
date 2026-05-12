<?php

require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
$pdo = Connection::get($config);
$logger = new SecurityLogger($pdo);
SecurityService::requireJsonPost();
SecurityService::requireJsonCsrf($logger);
(new RateLimitService($pdo, $logger))->requireAllowed('kanban_update', 120, 3600);

$cardId = (int) ($_POST['card_id'] ?? 0);
$columnId = clean_text($_POST['column_id'] ?? '');
$position = (int) ($_POST['position'] ?? 0);

if ($cardId <= 0 || $columnId === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Dados insuficientes para atualizar o kanban.']);
    exit;
}

// Placeholder: atualizar leads.pipeline_stage_id e lead_stage_history com PDO prepared statements.
(new AuditLogService($pdo))->write('operational', 'info', 'kanban_moved', 'Card movido no kanban operacional.', [
    'card_id' => $cardId,
    'column_id' => $columnId,
    'position' => $position,
], auth_id(), 'lead', $cardId);

echo json_encode([
    'success' => true,
    'message' => 'Etapa preparada para persistencia.',
    'kanban' => [
        'card_id' => $cardId,
        'column_id' => $columnId,
        'position' => $position,
    ],
]);
