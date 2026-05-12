<?php

$dashboard = null;

try {
    $dashboard = (new DashboardAnalyticsService(Connection::get($GLOBALS['config'])))->build([
        'kpis' => [
            ['label' => 'Leads do mes', 'value' => '42', 'delta' => '+18% vs. mes anterior', 'tone' => 'gold'],
            ['label' => 'Servicos concluidos', 'value' => '18', 'delta' => 'execucao premium', 'tone' => 'green'],
            ['label' => 'Recorrencia', 'value' => '31%', 'delta' => '+6% em ciclos', 'tone' => 'blue'],
            ['label' => 'Clientes ativos', 'value' => '128', 'delta' => 'base monitorada', 'tone' => 'green'],
            ['label' => 'Propostas enviadas', 'value' => '24', 'delta' => 'pipeline quente', 'tone' => 'gold'],
        ],
        'timeline' => $crmTimeline,
        'calendar' => array_map(static fn (array $event): array => [
            'title' => $event['title'],
            'category' => $event['category'],
            'status' => $event['status'],
            'location' => $event['location'],
            'day' => date('d/m', strtotime($event['date'])),
            'time' => $event['time'],
        ], $calendarEvents),
        'recent_leads' => array_map(static fn (array $lead): array => [
            'client' => $lead['client'],
            'phone' => $lead['phone'],
            'status' => $lead['status'],
            'score' => $lead['score'],
            'detail' => $lead['surface'],
        ], $crmLeads),
        'recurrences' => [
            ['client_name' => 'Marina Albuquerque', 'neighborhood' => 'Jardim Europa', 'city' => 'Nova Friburgo', 'due_at' => '07/11/2026', 'days_left' => 184, 'interval_months' => 6],
            ['client_name' => 'Residencial Arvoredo', 'neighborhood' => 'Brooklin', 'city' => 'Nova Friburgo', 'due_at' => '12/11/2026', 'days_left' => 189, 'interval_months' => 6],
        ],
    ]);
} catch (Throwable) {
    $dashboard = [
        'kpis' => [
            ['label' => 'Leads do mes', 'value' => '42', 'delta' => '+18% vs. mes anterior', 'tone' => 'gold'],
            ['label' => 'Servicos concluidos', 'value' => '18', 'delta' => 'execucao premium', 'tone' => 'green'],
            ['label' => 'Recorrencia', 'value' => '31%', 'delta' => '+6% em ciclos', 'tone' => 'blue'],
            ['label' => 'Clientes ativos', 'value' => '128', 'delta' => 'base monitorada', 'tone' => 'green'],
            ['label' => 'Propostas enviadas', 'value' => '24', 'delta' => 'pipeline quente', 'tone' => 'gold'],
        ],
        'charts' => [
            'districts' => [
                ['label' => 'Jardim Europa', 'value' => 18],
                ['label' => 'Moema', 'value' => 14],
                ['label' => 'Brooklin', 'value' => 12],
                ['label' => 'Pinheiros', 'value' => 9],
            ],
            'surfaces' => [
                ['label' => 'Garagem', 'value' => 31],
                ['label' => 'Muro', 'value' => 27],
                ['label' => 'Fachada', 'value' => 21],
                ['label' => 'Pedra', 'value' => 18],
            ],
            'dirt' => [
                ['label' => 'Lodo', 'value' => 34],
                ['label' => 'Musgo', 'value' => 26],
                ['label' => 'Manchas', 'value' => 19],
                ['label' => 'Mofo', 'value' => 13],
            ],
            'conversion' => [
                ['label' => 'Novo', 'value' => 42],
                ['label' => 'Diagnostico', 'value' => 28],
                ['label' => 'Proposta', 'value' => 24],
                ['label' => 'Agendado', 'value' => 16],
                ['label' => 'Concluido', 'value' => 18],
            ],
            'recurrence' => [
                ['label' => 'Preventivo', 'value' => 31],
                ['label' => 'Follow-up', 'value' => 22],
                ['label' => 'Servico', 'value' => 18],
                ['label' => 'Visita', 'value' => 11],
            ],
        ],
        'timeline' => $crmTimeline,
        'calendar' => array_map(static fn (array $event): array => [
            'title' => $event['title'],
            'category' => $event['category'],
            'status' => $event['status'],
            'location' => $event['location'],
            'day' => date('d/m', strtotime($event['date'])),
            'time' => $event['time'],
        ], $calendarEvents),
        'activities' => [
            ['level' => 'info', 'action' => 'ia_textual', 'message' => 'Resumo tecnico gerado para lead premium.', 'created_at' => 'Hoje, 09:44'],
            ['level' => 'warning', 'action' => 'follow_up', 'message' => 'Retorno comercial prioritario em aberto.', 'created_at' => 'Hoje, 10:15'],
            ['level' => 'info', 'action' => 'kanban', 'message' => 'Lead movido para proposta enviada.', 'created_at' => 'Ontem, 17:10'],
        ],
        'recent_leads' => $crmLeads,
        'followups' => [
            ['title' => 'Apresentar plano tecnico Villa Serena', 'status' => 'pending', 'due_at' => '08/05 14:30'],
            ['title' => 'Confirmar visita Marina Albuquerque', 'status' => 'pending', 'due_at' => '09/05 09:00'],
            ['title' => 'Reativar lead Atelier Brava', 'status' => 'pending', 'due_at' => '10/05 11:00'],
        ],
        'recurrences' => [
            ['client_name' => 'Marina Albuquerque', 'neighborhood' => 'Jardim Europa', 'city' => 'Nova Friburgo', 'due_at' => '07/11/2026', 'days_left' => 184, 'interval_months' => 6],
            ['client_name' => 'Residencial Arvoredo', 'neighborhood' => 'Brooklin', 'city' => 'Nova Friburgo', 'due_at' => '12/11/2026', 'days_left' => 189, 'interval_months' => 6],
        ],
    ];
}

$chartMax = static fn (array $items): int => max(1, ...array_map(static fn (array $item): int => (int) ($item['value'] ?? 0), $items ?: [['value' => 1]]));
$percent = static fn (int $value, int $max): int => max(6, (int) round(($value / max(1, $max)) * 100));
?>

<div class="analytics-page">
    <section class="card analytics-hero">
        <div>
            <span class="eyebrow">Inteligencia operacional</span>
            <h2>Visao analitica da operacao premium</h2>
            <p>Indicadores comerciais, agenda, recorrencia, conversao e sinais de demanda reunidos para decisao rapida.</p>
        </div>
        <div class="analytics-hero__pulse">
            <strong>Alta</strong>
            <span>qualidade do pipeline</span>
        </div>
    </section>

    <section class="metric-grid analytics-kpis">
        <?php foreach ($dashboard['kpis'] as $metric): ?>
            <article class="card metric-card metric-card--<?= e($metric['tone'] ?? 'gold'); ?>">
                <span><?= e($metric['label']); ?></span>
                <strong><?= e((string) $metric['value']); ?></strong>
                <small><?= e($metric['delta'] ?? ''); ?></small>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="analytics-grid analytics-grid--main">
        <article class="card analytics-panel analytics-panel--wide">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Conversao</span>
                    <h2>Funil comercial</h2>
                </div>
                <a class="btn btn--ghost" href="?page=kanban">Abrir kanban</a>
            </div>
            <div class="funnel-chart">
                <?php $max = $chartMax($dashboard['charts']['conversion']); ?>
                <?php foreach ($dashboard['charts']['conversion'] as $item): ?>
                    <?php $width = $percent((int) $item['value'], $max); ?>
                    <div class="funnel-row">
                        <span><?= e($item['label']); ?></span>
                        <div><i style="width: <?= $width; ?>%"></i></div>
                        <strong><?= (int) $item['value']; ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="card analytics-panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Recorrencia</span>
                    <h2>Ciclos preventivos</h2>
                </div>
                <span class="badge badge--gold">premium</span>
            </div>
            <div class="analytics-ring">
                <strong><?= e($dashboard['kpis'][2]['value'] ?? '31%'); ?></strong>
                <span>clientes com potencial de manutencao recorrente</span>
            </div>
        </article>
    </section>

    <section class="analytics-grid analytics-grid--charts">
        <?php
        $charts = [
            'districts' => ['title' => 'Bairros com mais clientes', 'eyebrow' => 'Territorio'],
            'surfaces' => ['title' => 'Superficies mais comuns', 'eyebrow' => 'Demanda'],
            'dirt' => ['title' => 'Tipos de sujeira', 'eyebrow' => 'Diagnostico'],
            'recurrence' => ['title' => 'Eventos por categoria', 'eyebrow' => 'Agenda'],
        ];
        ?>
        <?php foreach ($charts as $key => $meta): ?>
            <article class="card analytics-panel">
                <div class="panel__header">
                    <div>
                        <span class="eyebrow"><?= e($meta['eyebrow']); ?></span>
                        <h2><?= e($meta['title']); ?></h2>
                    </div>
                </div>
                <div class="bar-list">
                    <?php $max = $chartMax($dashboard['charts'][$key]); ?>
                    <?php foreach ($dashboard['charts'][$key] as $item): ?>
                        <div class="bar-list__row">
                            <span><?= e($item['label']); ?></span>
                            <div><i style="width: <?= $percent((int) $item['value'], $max); ?>%"></i></div>
                            <strong><?= (int) $item['value']; ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="analytics-grid analytics-grid--ops">
        <article class="card analytics-panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Timeline</span>
                    <h2>Operacao em movimento</h2>
                </div>
            </div>
            <div class="timeline-list analytics-timeline">
                <?php foreach (array_slice($dashboard['timeline'], 0, 5) as $event): ?>
                    <article class="timeline-item">
                        <span><?= e($event['type'] ?? 'Log'); ?></span>
                        <div>
                            <strong><?= e($event['title'] ?? $event['message'] ?? 'Atividade'); ?></strong>
                            <p><?= e($event['body'] ?? 'Evento registrado no sistema.'); ?></p>
                            <small><?= e($event['time'] ?? $event['created_at'] ?? 'Agora'); ?></small>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="card analytics-panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Agenda</span>
                    <h2>Calendario resumido</h2>
                </div>
                <a class="btn btn--ghost" href="?page=agenda">Ver agenda</a>
            </div>
            <div class="mini-calendar-list">
                <?php foreach (array_slice($dashboard['calendar'], 0, 5) as $event): ?>
                    <article class="mini-calendar-card">
                        <div>
                            <strong><?= e($event['day'] ?? '--/--'); ?></strong>
                            <span><?= e($event['time'] ?? '--:--'); ?></span>
                        </div>
                        <section>
                            <b><?= e($event['title']); ?></b>
                            <p><?= e(($event['location'] ?? 'Local a confirmar') . ' - ' . ($event['status'] ?? 'Pendente')); ?></p>
                        </section>
                    </article>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="card analytics-panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Atividades</span>
                    <h2>Eventos recentes</h2>
                </div>
            </div>
            <div class="activity-list">
                <?php foreach (array_slice($dashboard['activities'], 0, 6) as $activity): ?>
                    <article>
                        <span class="activity-dot activity-dot--<?= e($activity['level'] ?? 'info'); ?>"></span>
                        <div>
                            <strong><?= e($activity['action'] ?? 'atividade'); ?></strong>
                            <p><?= e($activity['message']); ?></p>
                        </div>
                        <small><?= e($activity['created_at'] ?? 'Agora'); ?></small>
                    </article>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section class="analytics-grid analytics-grid--bottom">
        <article class="card analytics-panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Leads recentes</span>
                    <h2>Entradas qualificadas</h2>
                </div>
                <a class="btn btn--ghost" href="?page=leads">Ver leads</a>
            </div>
            <div class="lead-list lead-list--compact">
                <?php foreach (array_slice($dashboard['recent_leads'], 0, 5) as $lead): ?>
                    <article class="lead-row-card analytics-lead-row">
                        <div>
                            <strong><?= e($lead['client']); ?></strong>
                            <p><?= e($lead['detail'] ?? $lead['ai_summary'] ?? 'Lead em avaliacao tecnica.'); ?></p>
                            <small><?= e($lead['status'] ?? 'Novo'); ?></small>
                        </div>
                        <span class="lead-score"><?= e((string) ($lead['score'] ?? '0')); ?></span>
                    </article>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="card analytics-panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Recorrencia</span>
                    <h2>Clientes proximos do retorno</h2>
                </div>
                <span class="badge badge--gold">6 meses Nova Friburgo</span>
            </div>
            <div class="recurrence-list">
                <?php foreach (array_slice($dashboard['recurrences'], 0, 5) as $recurrence): ?>
                    <article>
                        <div>
                            <strong><?= e($recurrence['client_name']); ?></strong>
                            <p><?= e(trim(($recurrence['neighborhood'] ?? '') . ' - ' . ($recurrence['city'] ?? ''), ' -')); ?></p>
                        </div>
                        <span><?= e($recurrence['due_at']); ?></span>
                        <small><?= e((string) ($recurrence['days_left'] ?? 0)); ?> dias</small>
                    </article>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="card analytics-panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Follow-ups</span>
                    <h2>Proximas acoes</h2>
                </div>
                <span class="badge">futuro</span>
            </div>
            <div class="followup-list">
                <?php foreach (array_slice($dashboard['followups'], 0, 5) as $followup): ?>
                    <article>
                        <div>
                            <strong><?= e($followup['title']); ?></strong>
                            <p><?= e($followup['status'] ?? 'pending'); ?></p>
                        </div>
                        <span><?= e($followup['due_at']); ?></span>
                    </article>
                <?php endforeach; ?>
            </div>
        </article>
    </section>
</div>
