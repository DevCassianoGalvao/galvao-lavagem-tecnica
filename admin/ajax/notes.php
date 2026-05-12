<?php

require_once __DIR__ . '/../../core/bootstrap.php';
require_auth();

header('Content-Type: application/json; charset=utf-8');

$pdo = Connection::get($config);
$logger = new SecurityLogger($pdo);

SecurityService::requireJsonPost();
SecurityService::requireJsonCsrf($logger);
(new RateLimitService($pdo, $logger))->requireAllowed('notes', 90, 3600);

$service = new NoteService($pdo);
$action = clean_text($_POST['action'] ?? 'create');

try {
    $payload = match ($action) {
        'create' => createInternalNote($service, $logger),
        'toggle_pin' => toggleInternalNotePin($service, $logger),
        default => throw new InvalidArgumentException('Acao invalida.'),
    };

    echo json_encode(['success' => true] + $payload);
} catch (Throwable $exception) {
    $logger->log('error', 'note_error', 'Falha ao processar observacao interna.', [
        'action' => $action,
        'error' => $exception->getMessage(),
        'user_id' => auth_id(),
    ]);

    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $exception instanceof InvalidArgumentException
            ? $exception->getMessage()
            : 'Nao foi possivel processar a observacao.',
    ]);
}

function createInternalNote(NoteService $service, SecurityLogger $logger): array
{
    $noteId = $service->create($_POST, auth_id());
    $logger->log('info', 'note_created', 'Observacao interna criada.', [
        'note_id' => $noteId,
        'user_id' => auth_id(),
    ]);

    return [
        'message' => 'Observacao salva.',
        'note_id' => $noteId,
    ];
}

function toggleInternalNotePin(NoteService $service, SecurityLogger $logger): array
{
    $noteId = (int) ($_POST['note_id'] ?? 0);

    if ($noteId <= 0) {
        throw new InvalidArgumentException('Observacao invalida.');
    }

    $service->togglePinned($noteId, auth_id());
    $logger->log('info', 'note_pin_toggled', 'Fixacao de observacao alterada.', [
        'note_id' => $noteId,
        'user_id' => auth_id(),
    ]);

    return [
        'message' => 'Fixacao atualizada.',
        'note_id' => $noteId,
    ];
}
