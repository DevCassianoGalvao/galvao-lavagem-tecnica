<?php

require_once __DIR__ . '/../core/bootstrap.php';
require_auth();
require_once __DIR__ . '/data/crm-demo.php';

$headerNotifications = [];
$headerUnreadCount = 0;

try {
    $notificationService = new NotificationService(Connection::get($config), new SecurityLogger(Connection::get($config)));
    $headerNotifications = $notificationService->latest(auth_id(), 7);
    $headerUnreadCount = $notificationService->unreadCount(auth_id());
} catch (Throwable) {
    $headerNotifications = [
        ['id' => 0, 'notification_type' => 'new_lead', 'priority' => 'high', 'title' => 'Novo lead recebido', 'body' => 'Diagnostico tecnico aguardando analise.', 'status' => 'pending', 'notify_at' => 'Agora'],
        ['id' => 0, 'notification_type' => 'preventive_return', 'priority' => 'normal', 'title' => 'Retorno preventivo proximo', 'body' => 'Cliente em ciclo de 6 meses para Nova Friburgo.', 'status' => 'pending', 'notify_at' => 'Hoje'],
    ];
    $headerUnreadCount = 2;
}

$pages = [
    'dashboard' => [
        'title' => 'Dashboard',
        'view' => __DIR__ . '/views/dashboard.php',
    ],
    'leads' => [
        'title' => 'Leads',
        'view' => __DIR__ . '/views/leads.php',
    ],
    'kanban' => [
        'title' => 'Kanban',
        'view' => __DIR__ . '/views/kanban.php',
    ],
    'agenda' => [
        'title' => 'Agenda',
        'view' => __DIR__ . '/views/agenda.php',
    ],
    'clientes' => [
        'title' => 'Perfil do Cliente',
        'view' => __DIR__ . '/views/clientes.php',
    ],
    'banco-visual' => [
        'title' => 'Banco Visual',
        'view' => __DIR__ . '/views/banco-visual.php',
    ],
    'produtos' => [
        'title' => 'Produtos',
        'view' => __DIR__ . '/views/produtos.php',
    ],
    'notificacoes' => [
        'title' => 'Notificacoes',
        'view' => __DIR__ . '/views/notificacoes.php',
    ],
    'observacoes' => [
        'title' => 'Observacoes',
        'view' => __DIR__ . '/views/observacoes.php',
    ],
    'tags' => [
        'title' => 'Tags',
        'view' => __DIR__ . '/views/tags.php',
    ],
    'metricas' => [
        'title' => 'Metricas',
        'view' => __DIR__ . '/views/metricas.php',
    ],
    'configuracoes' => [
        'title' => 'Configuracoes',
        'view' => __DIR__ . '/views/configuracoes.php',
    ],
    'backups' => [
        'title' => 'Backups',
        'view' => __DIR__ . '/views/backups.php',
    ],
    'auditoria' => [
        'title' => 'Auditoria',
        'view' => __DIR__ . '/views/auditoria.php',
    ],
    'filas' => [
        'title' => 'Filas',
        'view' => __DIR__ . '/views/filas.php',
    ],
];

$currentPage = $_GET['page'] ?? 'dashboard';

if (!isset($pages[$currentPage])) {
    $currentPage = 'dashboard';
}

$pageTitle = $pages[$currentPage]['title'];
$adminCssBundle = AssetService::adminBundle('css');
$adminJsBundle = AssetService::adminBundle('js');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle); ?> | Admin Galvao</title>
    <?php if ($adminCssBundle): ?>
        <link rel="preload" href="<?= e($adminCssBundle); ?>" as="style">
        <link rel="stylesheet" href="<?= e($adminCssBundle); ?>">
    <?php else: ?>
        <link rel="stylesheet" href="assets/css/admin.css">
    <?php endif; ?>
</head>
<body>
    <div class="admin-shell">
        <?php partial(__DIR__ . '/components/sidebar.php', [
            'currentPage' => $currentPage,
            'pages' => $pages,
        ]); ?>

        <main class="admin-main">
            <?php partial(__DIR__ . '/components/header.php', [
                'pageTitle' => $pageTitle,
                'headerNotifications' => $headerNotifications,
                'headerUnreadCount' => $headerUnreadCount,
            ]); ?>

            <section class="admin-content">
                <?php partial($pages[$currentPage]['view'], [
                    'crmMetrics' => $crmMetrics,
                    'crmTags' => $crmTags,
                    'crmLeads' => $crmLeads,
                    'crmClient' => $crmClient,
                    'crmTimeline' => $crmTimeline,
                    'crmNotes' => $crmNotes,
                    'crmUploads' => $crmUploads,
                    'crmRecurrences' => $crmRecurrences,
                    'kanbanColumns' => $kanbanColumns,
                    'kanbanCards' => $kanbanCards,
                    'calendarCategories' => $calendarCategories,
                    'calendarEvents' => $calendarEvents,
                ]); ?>
            </section>
        </main>
    </div>

    <script>window.GALVAO_CSRF = "<?= e(csrf_token()); ?>";</script>
    <?php if ($adminJsBundle): ?>
        <script src="<?= e($adminJsBundle); ?>" defer></script>
    <?php else: ?>
        <script src="assets/js/admin.js" defer></script>
    <?php endif; ?>
</body>
</html>
