<?php

require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
$pdo = Connection::get($config);
$logger = new SecurityLogger($pdo);
SecurityService::requireJsonPost();
SecurityService::requireJsonCsrf($logger);
(new RateLimitService($pdo, $logger))->requireAllowed('calendar_event', 80, 3600);

$eventId = (int) ($_POST['event_id'] ?? 0);
$title = clean_text($_POST['title'] ?? '');
$date = clean_text($_POST['date'] ?? '');
$time = clean_text($_POST['time'] ?? '');
$category = clean_text($_POST['category'] ?? '');

if ($title === '' || $date === '' || $time === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Dados obrigatorios ausentes.']);
    exit;
}

// Placeholder: salvar/editar em calendar_events e sincronizar futuramente com Google Calendar API.
(new AuditLogService($pdo))->write('operational', 'info', $eventId > 0 ? 'calendar_event_updated' : 'calendar_event_created', 'Evento operacional salvo no calendario.', [
    'event_id' => $eventId,
    'title' => $title,
    'date' => $date,
    'time' => $time,
    'category' => $category,
], auth_id(), 'calendar_event', $eventId > 0 ? $eventId : null);

echo json_encode([
    'success' => true,
    'message' => 'Evento preparado para persistencia.',
    'event' => [
        'id' => $eventId,
        'title' => $title,
        'date' => $date,
        'time' => $time,
        'category' => $category,
        'client' => clean_text($_POST['client'] ?? ''),
        'location' => clean_text($_POST['location'] ?? ''),
    ],
]);
