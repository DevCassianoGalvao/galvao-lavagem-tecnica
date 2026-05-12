<header class="admin-header">
    <div>
        <span class="eyebrow">Painel administrativo</span>
        <h1><?= e($pageTitle); ?></h1>
    </div>

    <div class="admin-header__actions">
        <button class="btn btn--ghost mobile-menu-button" type="button" data-admin-menu>Menu</button>
        <div class="notification-center" data-notification-center>
            <button class="btn btn--ghost notification-trigger" type="button" data-notification-toggle aria-expanded="false">
                <span>Alertas</span>
                <strong data-notification-count <?= (int) ($headerUnreadCount ?? 0) === 0 ? 'hidden' : ''; ?>><?= (int) ($headerUnreadCount ?? 0); ?></strong>
            </button>
            <div class="notification-dropdown card" data-notification-dropdown>
                <header>
                    <div>
                        <span class="eyebrow">Central operacional</span>
                        <h2>Notificacoes</h2>
                    </div>
                    <button class="btn btn--ghost" type="button" data-notification-mark-all>Marcar lidas</button>
                </header>
                <div class="notification-list" data-notification-list>
                    <?php foreach (($headerNotifications ?? []) as $notification): ?>
                        <article class="notification-item <?= in_array($notification['status'] ?? 'pending', ['pending', 'sent'], true) ? 'is-unread' : ''; ?>" data-notification-id="<?= (int) ($notification['id'] ?? 0); ?>">
                            <span class="notification-dot notification-dot--<?= e($notification['notification_type'] ?? 'system'); ?>"></span>
                            <div>
                                <strong><?= e($notification['title'] ?? 'Notificacao'); ?></strong>
                                <p><?= e($notification['body'] ?? ''); ?></p>
                                <small><?= e((string) ($notification['notify_at'] ?? '')); ?> · <?= e($notification['channel'] ?? 'in_app'); ?></small>
                            </div>
                            <button type="button" data-notification-read title="Marcar como lida">OK</button>
                        </article>
                    <?php endforeach; ?>
                    <?php if (empty($headerNotifications)): ?>
                        <p class="muted">Nenhum alerta operacional no momento.</p>
                    <?php endif; ?>
                </div>
                <a class="notification-footer" href="?page=notificacoes">Ver timeline completa</a>
            </div>
        </div>
        <a class="btn btn--ghost" href="../public/landing/">Ver landing</a>
        <a class="btn btn--ghost" href="logout.php">Sair</a>
        <button class="btn btn--primary" type="button">Novo lead</button>
    </div>
</header>
