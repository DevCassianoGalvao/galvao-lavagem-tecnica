<?php

$auditRows = [];
$auditUsers = [];
$auditStats = ['total_today' => 0, 'security_today' => 0, 'ai_today' => 0, 'critical_week' => 0];
$auditError = null;
$auditFilters = [
    'query' => clean_text($_GET['q'] ?? ''),
    'channel' => clean_text($_GET['channel'] ?? ''),
    'level' => clean_text($_GET['level'] ?? ''),
    'user_id' => clean_text($_GET['user_id'] ?? ''),
    'date_from' => clean_text($_GET['date_from'] ?? ''),
    'date_to' => clean_text($_GET['date_to'] ?? ''),
];

try {
    $auditService = new AuditLogService(Connection::get($GLOBALS['config']));
    $auditRows = $auditService->search($auditFilters, 160);
    $auditUsers = $auditService->users();
    $auditStats = $auditService->stats();
} catch (Throwable) {
    $auditError = 'Auditoria disponivel apos migracao do banco.';
}

$levels = ['debug' => 'Debug', 'info' => 'Info', 'warning' => 'Alerta', 'error' => 'Erro', 'critical' => 'Critico'];
$channels = AuditLogService::channelLabels();
?>

<div class="audit-page">
    <section class="card audit-hero">
        <div>
            <span class="eyebrow">Logs e auditoria</span>
            <h2>Trilha completa das atividades importantes</h2>
            <p>Login, logout, uploads, IA, seguranca, rate limit, mudancas operacionais, eventos e alteracoes administrativas em uma timeline rastreavel.</p>
        </div>
        <div class="audit-hero__status">
            <strong><?= (int) $auditStats['total_today']; ?></strong>
            <span>eventos hoje</span>
        </div>
    </section>

    <?php if ($auditError): ?>
        <div class="security-alert"><?= e($auditError); ?></div>
    <?php endif; ?>

    <section class="audit-kpi-grid">
        <article class="card audit-kpi">
            <span>Seguranca hoje</span>
            <strong><?= (int) $auditStats['security_today']; ?></strong>
            <small>login, CSRF e limites</small>
        </article>
        <article class="card audit-kpi">
            <span>IA hoje</span>
            <strong><?= (int) $auditStats['ai_today']; ?></strong>
            <small>texto, imagem e fallback</small>
        </article>
        <article class="card audit-kpi">
            <span>Erros 7 dias</span>
            <strong><?= (int) $auditStats['critical_week']; ?></strong>
            <small>erros e criticos</small>
        </article>
    </section>

    <form class="card audit-filters" method="get">
        <input type="hidden" name="page" value="auditoria">
        <label class="field audit-search">
            <span>Busca rapida</span>
            <input class="input" name="q" value="<?= e($auditFilters['query']); ?>" placeholder="acao, mensagem, usuario, contexto...">
        </label>
        <label class="field">
            <span>Categoria</span>
            <select class="select" name="channel">
                <option value="">Todas</option>
                <?php foreach ($channels as $value => $label): ?>
                    <option value="<?= e($value); ?>" <?= $auditFilters['channel'] === $value ? 'selected' : ''; ?>><?= e($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span>Nivel</span>
            <select class="select" name="level">
                <option value="">Todos</option>
                <?php foreach ($levels as $value => $label): ?>
                    <option value="<?= e($value); ?>" <?= $auditFilters['level'] === $value ? 'selected' : ''; ?>><?= e($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span>Usuario</span>
            <select class="select" name="user_id">
                <option value="">Todos</option>
                <?php foreach ($auditUsers as $user): ?>
                    <option value="<?= (int) $user['id']; ?>" <?= (string) $auditFilters['user_id'] === (string) $user['id'] ? 'selected' : ''; ?>>
                        <?= e($user['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span>De</span>
            <input class="input" type="date" name="date_from" value="<?= e($auditFilters['date_from']); ?>">
        </label>
        <label class="field">
            <span>Ate</span>
            <input class="input" type="date" name="date_to" value="<?= e($auditFilters['date_to']); ?>">
        </label>
        <button class="btn btn--primary" type="submit">Filtrar</button>
    </form>

    <section class="card audit-timeline-panel">
        <div class="panel__header">
            <div>
                <span class="eyebrow">Timeline</span>
                <h2>Eventos rastreados</h2>
            </div>
            <span class="tag tag--gold"><?= count($auditRows); ?> registros</span>
        </div>

        <div class="audit-timeline">
            <?php if (!$auditRows): ?>
                <p class="muted">Nenhum registro encontrado para os filtros atuais.</p>
            <?php endif; ?>

            <?php foreach ($auditRows as $row): ?>
                <?php $context = auditContextPreview($row['context_json'] ?? null); ?>
                <article class="audit-event audit-event--<?= e($row['level']); ?>">
                    <span class="audit-event__dot"></span>
                    <div class="audit-event__body">
                        <header>
                            <div>
                                <strong><?= e($row['message']); ?></strong>
                                <span><?= e($row['action'] ?? 'evento'); ?> · <?= e(AuditLogService::channelLabel((string) $row['channel'])); ?></span>
                            </div>
                            <small><?= e(date('d/m/Y H:i', strtotime($row['created_at']))); ?></small>
                        </header>
                        <p><?= e($context); ?></p>
                        <footer>
                            <span><?= e($row['user_name'] ?? 'Sistema'); ?></span>
                            <span><?= e($row['ip_address'] ?? 'IP indisponivel'); ?></span>
                            <span><?= e($levels[$row['level']] ?? $row['level']); ?></span>
                        </footer>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php
function auditContextPreview(?string $json): string
{
    if (!$json) {
        return 'Sem contexto adicional.';
    }

    $decoded = json_decode($json, true);

    if (!is_array($decoded)) {
        return strlen($json) > 180 ? substr($json, 0, 180) . '...' : $json;
    }

    unset($decoded['session_id']);

    if (!$decoded) {
        return 'Contexto tecnico protegido.';
    }

    $preview = json_encode($decoded, JSON_UNESCAPED_UNICODE) ?: '';

    return strlen($preview) > 180 ? substr($preview, 0, 180) . '...' : $preview;
}
