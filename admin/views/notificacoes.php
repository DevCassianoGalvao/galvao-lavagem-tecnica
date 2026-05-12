<?php

$notificationTimeline = [];
$notificationStats = [
    'unread' => 0,
    'email_ready' => 0,
    'whatsapp_ready' => 0,
    'push_ready' => 0,
];

try {
    $pdo = Connection::get($GLOBALS['config']);
    $service = new NotificationService($pdo, new SecurityLogger($pdo));
    $notificationTimeline = $service->timeline(auth_id(), 80);
    $notificationStats['unread'] = $service->unreadCount(auth_id());
    $notificationStats['email_ready'] = count(array_filter($notificationTimeline, static fn (array $item): bool => ($item['channel'] ?? '') === 'email'));
    $notificationStats['whatsapp_ready'] = count(array_filter($notificationTimeline, static fn (array $item): bool => ($item['channel'] ?? '') === 'whatsapp'));
    $notificationStats['push_ready'] = count(array_filter($notificationTimeline, static fn (array $item): bool => ($item['channel'] ?? '') === 'push'));
} catch (Throwable) {
    $notificationTimeline = [
        ['id' => 0, 'notification_type' => 'new_lead', 'priority' => 'high', 'title' => 'Novo lead recebido', 'body' => 'Diagnostico tecnico aguardando analise.', 'status' => 'pending', 'channel' => 'in_app', 'notify_at' => 'Agora'],
        ['id' => 0, 'notification_type' => 'follow_up', 'priority' => 'normal', 'title' => 'Follow-up proximo', 'body' => 'Retorno consultivo agendado para proposta premium.', 'status' => 'sent', 'channel' => 'in_app', 'notify_at' => 'Hoje'],
        ['id' => 0, 'notification_type' => 'preventive_return', 'priority' => 'normal', 'title' => 'Retorno preventivo', 'body' => 'Cliente em ciclo medio de lodo de 6 meses.', 'status' => 'read', 'channel' => 'whatsapp', 'notify_at' => 'Ontem'],
    ];
    $notificationStats['unread'] = 2;
    $notificationStats['whatsapp_ready'] = 1;
}
?>

<div class="notifications-page">
    <section class="card notifications-hero">
        <div>
            <span class="eyebrow">Central de alertas</span>
            <h2>Notificacoes operacionais importantes</h2>
            <p>Leads, follow-ups, retornos preventivos, propostas, eventos agendados e alertas de pipeline em uma timeline unica.</p>
        </div>
        <div class="notifications-hero__metric">
            <strong data-notification-page-count><?= (int) $notificationStats['unread']; ?></strong>
            <span>nao lidas</span>
        </div>
    </section>

    <section class="notification-kpi-grid">
        <article class="card notification-kpi">
            <span>In-app</span>
            <strong><?= count($notificationTimeline); ?></strong>
            <small>central interna</small>
        </article>
        <article class="card notification-kpi">
            <span>Email futuro</span>
            <strong><?= (int) $notificationStats['email_ready']; ?></strong>
            <small>canal preparado</small>
        </article>
        <article class="card notification-kpi">
            <span>WhatsApp futuro</span>
            <strong><?= (int) $notificationStats['whatsapp_ready']; ?></strong>
            <small>canal preparado</small>
        </article>
        <article class="card notification-kpi">
            <span>Push futuro</span>
            <strong><?= (int) $notificationStats['push_ready']; ?></strong>
            <small>canal preparado</small>
        </article>
    </section>

    <section class="card notifications-timeline-panel">
        <div class="panel__header">
            <div>
                <span class="eyebrow">Timeline</span>
                <h2>Fluxo de notificacoes</h2>
            </div>
            <button class="btn btn--primary" type="button" data-notification-mark-all>Marcar todas como lidas</button>
        </div>

        <div class="notifications-timeline">
            <?php foreach ($notificationTimeline as $notification): ?>
                <article class="notification-timeline-item <?= in_array($notification['status'] ?? 'pending', ['pending', 'sent'], true) ? 'is-unread' : ''; ?>" data-notification-id="<?= (int) ($notification['id'] ?? 0); ?>">
                    <span class="notification-dot notification-dot--<?= e($notification['notification_type'] ?? 'system'); ?>"></span>
                    <div>
                        <header>
                            <strong><?= e($notification['title']); ?></strong>
                            <small><?= e($notification['priority'] ?? 'normal'); ?> · <?= e($notification['channel'] ?? 'in_app'); ?></small>
                        </header>
                        <p><?= e($notification['body']); ?></p>
                        <footer>
                            <span><?= e((string) ($notification['notify_at'] ?? '')); ?></span>
                            <?php if (!empty($notification['client_name'])): ?>
                                <span><?= e($notification['client_name']); ?></span>
                            <?php endif; ?>
                            <button class="btn btn--ghost" type="button" data-notification-read>Marcar lida</button>
                        </footer>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>
