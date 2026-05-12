<?php

$navItems = [
    'dashboard' => 'Dashboard',
    'leads' => 'Leads',
    'kanban' => 'Kanban',
    'agenda' => 'Agenda',
    'clientes' => 'Cliente',
    'banco-visual' => 'Banco Visual',
    'produtos' => 'Produtos',
    'notificacoes' => 'Notificacoes',
    'observacoes' => 'Observacoes',
    'tags' => 'Tags',
    'metricas' => 'Metricas',
    'configuracoes' => 'Configuracoes',
    'backups' => 'Backups',
    'auditoria' => 'Auditoria',
    'filas' => 'Filas',
];
?>
<aside class="admin-sidebar" data-admin-sidebar>
    <a class="admin-brand" href="/admin/">
        <img src="../public/assets/images/logo-galvao.png" alt="Galvao Lavagem Tecnica" width="164" height="164" decoding="async">
        <span>
            <strong>Galvao</strong>
            <span>Lavagem Tecnica</span>
        </span>
    </a>

    <nav class="admin-nav" aria-label="Navegacao administrativa">
        <?php foreach ($navItems as $key => $label): ?>
            <a class="<?= $currentPage === $key ? 'is-active' : ''; ?>" href="?page=<?= e($key); ?>">
                <span><?= e($label); ?></span>
                <small>&gt;</small>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
