<?php

$queueStats = [];
$queueJobs = [];
$queueError = null;
$queueType = clean_text($_GET['type'] ?? '');
$queueStatus = clean_text($_GET['status'] ?? '');

$queueTypeLabels = [
    'ai_text' => 'IA textual',
    'ai_visual' => 'IA visual',
    'thumbnail' => 'Thumbnails',
    'compression' => 'Compressao',
    'notification' => 'Notificacoes',
];

$queueStatusLabels = [
    'pending' => 'Pendente',
    'processing' => 'Processando',
    'completed' => 'Concluido',
    'failed' => 'Erro',
    'canceled' => 'Cancelado',
];

try {
    $queueService = new QueueService(Connection::get($GLOBALS['config']), new AuditLogService(Connection::get($GLOBALS['config'])));
    $queueStats = $queueService->stats();
    $queueJobs = $queueService->list([
        'type' => in_array($queueType, QueueService::TYPES, true) ? $queueType : '',
        'status' => in_array($queueStatus, QueueService::STATUSES, true) ? $queueStatus : '',
    ], 160);
} catch (Throwable) {
    $queueError = 'Filas disponiveis apos aplicar a migracao queue_jobs e queue_job_logs.';
    foreach (QueueService::TYPES as $type) {
        $queueStats[$type] = array_fill_keys(QueueService::STATUSES, 0);
    }
}

$totalPending = array_sum(array_column($queueStats, 'pending'));
$totalProcessing = array_sum(array_column($queueStats, 'processing'));
$totalFailed = array_sum(array_column($queueStats, 'failed'));
$totalCompleted = array_sum(array_column($queueStats, 'completed'));

$formatPayload = static function (?string $json): string {
    $payload = json_decode($json ?: '{}', true);

    if (!is_array($payload) || !$payload) {
        return 'Sem payload';
    }

    $summary = [];

    foreach ($payload as $key => $value) {
        if (is_array($value)) {
            $summary[] = $key . ': ' . count($value) . ' item(ns)';
            continue;
        }

        $summary[] = $key . ': ' . (string) $value;
    }

    return implode(' | ', array_slice($summary, 0, 4));
};
?>

<div class="queue-page" data-queue-page>
    <section class="card queue-hero">
        <div>
            <span class="eyebrow">Processamento assincrono</span>
            <h2>Filas para tarefas pesadas sem travar a experiencia</h2>
            <p>IA textual, IA visual, thumbnails, compressao e notificacoes saem do fluxo principal e passam por workers com retry, logs e status operacional.</p>
        </div>
        <div class="queue-hero__actions">
            <button class="btn btn--primary" type="button" data-queue-run>Executar worker</button>
            <button class="btn btn--ghost" type="button" data-queue-notifications>Enfileirar notificacoes</button>
        </div>
    </section>

    <?php if ($queueError): ?>
        <div class="security-alert"><?= e($queueError); ?></div>
    <?php endif; ?>

    <section class="queue-kpi-grid">
        <article class="card queue-kpi">
            <span>Pendentes</span>
            <strong><?= (int) $totalPending; ?></strong>
            <small>Aguardando worker</small>
        </article>
        <article class="card queue-kpi">
            <span>Processando</span>
            <strong><?= (int) $totalProcessing; ?></strong>
            <small>Reservados agora</small>
        </article>
        <article class="card queue-kpi">
            <span>Com erro</span>
            <strong><?= (int) $totalFailed; ?></strong>
            <small>Prontos para retry</small>
        </article>
        <article class="card queue-kpi">
            <span>Concluidos</span>
            <strong><?= (int) $totalCompleted; ?></strong>
            <small>Historico operacional</small>
        </article>
    </section>

    <section class="queue-type-grid">
        <?php foreach ($queueTypeLabels as $type => $label): ?>
            <?php $stats = $queueStats[$type] ?? array_fill_keys(QueueService::STATUSES, 0); ?>
            <article class="card queue-type-card">
                <header>
                    <span><?= e($label); ?></span>
                    <strong><?= (int) array_sum($stats); ?></strong>
                </header>
                <div>
                    <small>Pendente <?= (int) ($stats['pending'] ?? 0); ?></small>
                    <small>Erro <?= (int) ($stats['failed'] ?? 0); ?></small>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="card queue-panel">
        <header class="queue-panel__header">
            <div>
                <span class="eyebrow">Jobs</span>
                <h3>Controle operacional da fila</h3>
            </div>
            <form class="queue-filters" method="get">
                <input type="hidden" name="page" value="filas">
                <select class="select" name="type" aria-label="Filtrar por tipo">
                    <option value="">Todos os tipos</option>
                    <?php foreach ($queueTypeLabels as $type => $label): ?>
                        <option value="<?= e($type); ?>" <?= $queueType === $type ? 'selected' : ''; ?>><?= e($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="select" name="status" aria-label="Filtrar por status">
                    <option value="">Todos os status</option>
                    <?php foreach ($queueStatusLabels as $status => $label): ?>
                        <option value="<?= e($status); ?>" <?= $queueStatus === $status ? 'selected' : ''; ?>><?= e($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn--ghost" type="submit">Filtrar</button>
            </form>
        </header>

        <div class="queue-status" data-queue-status hidden></div>

        <div class="queue-list">
            <?php if (!$queueJobs): ?>
                <p class="muted">Nenhum job encontrado para os filtros atuais.</p>
            <?php endif; ?>

            <?php foreach ($queueJobs as $job): ?>
                <article class="queue-row" data-queue-job-id="<?= (int) $job['id']; ?>">
                    <div class="queue-row__main">
                        <span class="badge badge--gold">#<?= (int) $job['id']; ?></span>
                        <div>
                            <strong><?= e($queueTypeLabels[$job['type']] ?? $job['type']); ?></strong>
                            <small><?= e($formatPayload($job['payload_json'] ?? null)); ?></small>
                        </div>
                    </div>
                    <div class="queue-row__meta">
                        <span class="queue-status-pill is-<?= e($job['status']); ?>"><?= e($queueStatusLabels[$job['status']] ?? $job['status']); ?></span>
                        <small><?= (int) $job['attempts']; ?>/<?= (int) $job['max_attempts']; ?> tentativas</small>
                        <small>Prioridade <?= (int) $job['priority']; ?></small>
                    </div>
                    <div class="queue-row__log">
                        <small><?= e($job['last_log'] ?: ($job['error_message'] ?: 'Sem log recente')); ?></small>
                        <time><?= e($job['created_at']); ?></time>
                    </div>
                    <div class="queue-row__actions">
                        <?php if (in_array($job['status'], ['failed', 'canceled'], true)): ?>
                            <button class="btn btn--ghost" type="button" data-queue-retry="<?= (int) $job['id']; ?>">Retry</button>
                        <?php endif; ?>
                        <?php if (in_array($job['status'], ['pending', 'failed'], true)): ?>
                            <button class="btn btn--ghost" type="button" data-queue-cancel="<?= (int) $job['id']; ?>">Cancelar</button>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>
