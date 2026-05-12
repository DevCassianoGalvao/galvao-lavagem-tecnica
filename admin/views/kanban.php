<div class="crm-page kanban-page">
    <section class="crm-toolbar card">
        <div>
            <span class="eyebrow">Kanban operacional</span>
            <h2>Pipeline de leads e servicos</h2>
        </div>
        <div class="crm-toolbar__actions">
            <input class="input" type="search" placeholder="Buscar cliente, bairro ou tag" data-kanban-search>
            <button class="btn btn--primary" type="button">Novo card</button>
        </div>
    </section>

    <section class="kanban-board-shell" aria-label="Kanban Galvao">
        <div class="kanban-board" data-kanban-board>
            <?php foreach ($kanbanColumns as $column): ?>
                <?php $columnCards = array_values(array_filter($kanbanCards, static fn ($card) => $card['column'] === $column['id'])); ?>
                <article class="kanban-lane" data-kanban-column="<?= e($column['id']); ?>">
                    <header class="kanban-lane__header">
                        <div>
                            <h3><?= e($column['title']); ?></h3>
                            <span><?= e($column['hint']); ?></span>
                        </div>
                        <strong data-kanban-count><?= e((string) count($columnCards)); ?></strong>
                    </header>

                    <div class="kanban-dropzone" data-kanban-dropzone>
                        <?php foreach ($columnCards as $card): ?>
                            <article class="kanban-lead-card" draggable="true" data-kanban-card data-card-id="<?= e((string) $card['id']); ?>">
                                <div class="kanban-lead-card__thumb">
                                    <span><?= e($card['thumb']); ?></span>
                                </div>
                                <div class="kanban-lead-card__body">
                                    <div class="kanban-lead-card__topline">
                                        <span class="kanban-priority"><?= e($card['priority']); ?></span>
                                        <small><?= e($card['district']); ?></small>
                                    </div>
                                    <h4><?= e($card['client']); ?></h4>
                                    <p><?= e($card['surface']); ?></p>
                                    <div class="kanban-ai">
                                        <span>Resumo IA</span>
                                        <p><?= e($card['ai_summary']); ?></p>
                                    </div>
                                    <div class="crm-tags">
                                        <?php foreach ($card['tags'] as $tag): ?>
                                            <?php partial(__DIR__ . '/../components/tag-pill.php', ['tagName' => $tag]); ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="kanban-save-status" data-kanban-status hidden>Atualizando etapa...</div>
</div>
