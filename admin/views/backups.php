<?php

$backupStatus = ['latest' => null, 'daily_count' => 0, 'weekly_count' => 0, 'total_size' => 0, 'retention' => ['daily' => 14, 'weekly' => 8]];
$backupList = ['daily' => [], 'weekly' => []];
$backupError = null;

try {
    $backupService = new BackupService(Connection::get($GLOBALS['config']), $GLOBALS['config'], new SecurityLogger(Connection::get($GLOBALS['config'])));
    $backupStatus = $backupService->status();
    $backupList = $backupService->list();
} catch (Throwable $exception) {
    $backupError = 'Backups disponiveis apos migracao do banco e ativacao da extensao ZIP.';
}

$formatBackupBytes = static function (int $bytes): string {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2, ',', '.') . ' GB';
    }

    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
    }

    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 2, ',', '.') . ' KB';
    }

    return $bytes . ' B';
};
?>

<div class="backups-page" data-backups-page>
    <section class="card backups-hero">
        <div>
            <span class="eyebrow">Seguranca operacional</span>
            <h2>Backups organizados para recuperacao de dados</h2>
            <p>Rotina modular para banco MySQL, imagens, thumbnails, arquivos de IA e uploads tecnicos, com retencao automatica e trilha de auditoria.</p>
        </div>
        <div class="backups-hero__status" data-backup-status>
            <strong><?= e($backupStatus['latest']['created_at'] ?? 'Sem backup'); ?></strong>
            <span><?= $backupStatus['latest'] ? 'Ultimo backup gerado' : 'Aguardando primeira rotina' ?></span>
        </div>
    </section>

    <?php if ($backupError): ?>
        <div class="security-alert"><?= e($backupError); ?></div>
    <?php endif; ?>

    <section class="backup-kpi-grid">
        <article class="card backup-kpi">
            <span>Diarios</span>
            <strong><?= (int) $backupStatus['daily_count']; ?></strong>
            <small>retencao de <?= (int) ($backupStatus['retention']['daily'] ?? 14); ?> arquivos</small>
        </article>
        <article class="card backup-kpi">
            <span>Semanais</span>
            <strong><?= (int) $backupStatus['weekly_count']; ?></strong>
            <small>retencao de <?= (int) ($backupStatus['retention']['weekly'] ?? 8); ?> arquivos</small>
        </article>
        <article class="card backup-kpi">
            <span>Volume</span>
            <strong><?= e($formatBackupBytes((int) $backupStatus['total_size'])); ?></strong>
            <small>armazenado localmente</small>
        </article>
    </section>

    <section class="card backup-actions">
        <div>
            <span class="eyebrow">Rotina manual</span>
            <h3>Criar backup agora</h3>
            <p>Use em momentos criticos: antes de migracoes, alteracoes estruturais ou publicacao em cPanel.</p>
        </div>
        <form data-backup-form>
            <input type="hidden" name="action" value="create">
            <label class="field">
                <span>Tipo</span>
                <select class="select" name="frequency">
                    <option value="daily">Diario</option>
                    <option value="weekly">Semanal</option>
                </select>
            </label>
            <label class="backup-toggle">
                <input type="checkbox" name="include_uploads" value="1" checked>
                <span>Incluir uploads e IA</span>
            </label>
            <button class="btn btn--primary" type="submit">Gerar backup seguro</button>
        </form>
        <button class="btn btn--ghost" type="button" data-backup-cleanup>Limpar antigos</button>
    </section>

    <section class="backup-list-grid">
        <?= backupListPanel('daily', 'Backups diarios', $backupList['daily']); ?>
        <?= backupListPanel('weekly', 'Backups semanais', $backupList['weekly']); ?>
    </section>
</div>

<?php
function backupListPanel(string $frequency, string $title, array $items): string
{
    $html = '<article class="card backup-list-panel"><header><span class="eyebrow">' . e($frequency) . '</span><h3>' . e($title) . '</h3></header>';
    $html .= '<div class="backup-file-list">';

    if (!$items) {
        $html .= '<p class="muted">Nenhum backup gerado ainda.</p>';
    }

    foreach ($items as $item) {
        $download = 'api/backup-download.php?frequency=' . rawurlencode($frequency) . '&file=' . rawurlencode($item['name']);
        $html .= '<article class="backup-file">';
        $html .= '<div><strong>' . e($item['name']) . '</strong><span>' . e($item['created_at']) . ' - ' . e($item['size_label']) . '</span></div>';
        $html .= '<small title="' . e($item['sha256']) . '">SHA-256 verificado</small>';
        $html .= '<a class="btn btn--ghost" href="' . e($download) . '">Baixar</a>';
        $html .= '</article>';
    }

    return $html . '</div></article>';
}
