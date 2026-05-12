<div class="crm-page">
    <section class="crm-toolbar card">
        <div>
            <span class="eyebrow">CRM visual</span>
            <h2>Leads em diagnostico</h2>
        </div>
        <div class="crm-toolbar__actions">
            <input class="input" type="search" placeholder="Buscar cliente, superficie ou tag" data-crm-search>
            <button class="btn btn--primary" type="button">Novo lead</button>
        </div>
    </section>

    <section class="lead-card-grid" data-lead-grid>
        <?php foreach ($crmLeads as $lead): ?>
            <article class="card lead-card" data-lead-card>
                <div class="lead-card__media">
                    <?php foreach ($lead['images'] as $image): ?>
                        <span><?= e($image); ?></span>
                    <?php endforeach; ?>
                </div>

                <div class="lead-card__body">
                    <div class="lead-card__topline">
                        <span class="badge badge--gold"><?= e($lead['status']); ?></span>
                        <span class="lead-score"><?= e((string) $lead['score']); ?></span>
                    </div>

                    <h3><?= e($lead['client']); ?></h3>
                    <p><?= e($lead['property']); ?></p>
                    <small><?= e($lead['surface']); ?></small>

                    <div class="ai-summary">
                        <span>Resumo IA</span>
                        <p><?= e($lead['ai_summary']); ?></p>
                    </div>

                    <div class="crm-tags">
                        <?php foreach ($lead['tags'] as $tag): ?>
                            <?php partial(__DIR__ . '/../components/tag-pill.php', ['tagName' => $tag]); ?>
                        <?php endforeach; ?>
                    </div>

                    <div class="lead-card__actions">
                        <a class="btn btn--ghost" href="?page=clientes">Abrir perfil</a>
                        <a class="btn btn--primary" href="https://wa.me/<?= e($lead['phone']); ?>" target="_blank" rel="noopener">WhatsApp</a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</div>
