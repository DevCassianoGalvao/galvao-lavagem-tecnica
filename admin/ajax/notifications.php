<?php

require_once __DIR__ . '/../../core/bootstrap.php';
require_auth();

header('Content-Type: application/json; charset=utf-8');

$pdo = Connection::get($config);
$logger = new SecurityLogger($pdo);

SecurityService::requireJsonPost();
SecurityService::requireJsonCsrf($logger);
(new RateLimitService($pdo, $logger))->requireAllowed('notifications', 160, 3600);

$service = new NotificationService($pdo, $logger);
$action = clean_text($_POST['action'] ?? '');

try {
    $message = match ($action) {
        'mark_read' => markNotificationRead($service),
        'mark_all_read' => markAllNotificationsRead($service),
        'dismiss' => dismissNotification($service),
        default => throw new InvalidArgumentException('Acao invalida.'),
    };

    echo json_encode([
        'success' => true,
        'message' => $message,
        'unread_count' => $service->unreadCount(auth_id()),
    ]);
} catch (Throwable $exception) {
    $logger->log('error', 'notification_error', 'Falha ao processar notificacao.', [
        'action' => $action,
        'error' => $exception->getMessage(),
        'user_id' => auth_id(),
    ]);

    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $exception instanceof InvalidArgumentException
            ? $exception->getMessage()
            : 'Nao foi possivel processar a notificacao.',
    ]);
}

function markNotificationRead(NotificationService $service): string
{
    $id = (int) ($_POST['notification_id'] ?? 0);

    if ($id <= 0) {
        throw new InvalidArgumentException('Notificacao invalida.');
    }

    $service->markRead($id, auth_id());

    return 'Notificacao marcada como lida.';
}

function markAllNotificationsRead(NotificationService $service): string
{
    $count = $service->markAllRead(auth_id());

    return $count . ' notificacoes marcadas como lidas.';
}

function dismissNotification(NotificationService $service): string
{
    $id = (int) ($_POST['notification_id'] ?? 0);

    if ($id <= 0) {
        throw new InvalidArgumentException('Notificacao invalida.');
    }

    $service->dismiss($id, auth_id());

    return 'Notificacao dispensada.';
}
