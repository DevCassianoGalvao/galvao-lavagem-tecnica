<div class="crm-page">
    <section class="crm-toolbar card">
        <div>
            <span class="eyebrow">Sistema de tags</span>
            <h2>Segmentacao operacional</h2>
        </div>
        <button class="btn btn--primary" type="button" data-tag-create>Nova tag</button>
    </section>

    <section class="tag-manager-grid" data-tag-list>
        <?php foreach ($crmTags as $tag): ?>
            <article class="card tag-manager-card" style="--tag-color: <?= e($tag['color']); ?>">
                <div>
                    <span class="tag-color-dot"></span>
                    <strong><?= e($tag['name']); ?></strong>
                    <p><?= e((string) $tag['count']); ?> clientes vinculados</p>
                </div>
                <div class="tag-manager-card__actions">
                    <button class="btn btn--ghost" type="button" data-tag-edit>Editar</button>
                    <button class="btn btn--ghost" type="button" data-tag-delete>Excluir</button>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <div class="modal" data-tag-modal>
        <form class="modal__panel tag-form" data-tag-form>
            <?= csrf_field(); ?>
            <div class="modal__header">
                <h2>Nova tag</h2>
                <button class="btn btn--ghost btn--icon" type="button" data-tag-close>x</button>
            </div>
            <div class="modal__body">
                <div class="field">
                    <label for="tag-name">Nome</label>
                    <input class="input" id="tag-name" name="tag_name" required>
                </div>
                <div class="field">
                    <label for="tag-color">Cor</label>
                    <input class="input" id="tag-color" name="tag_color" type="color" value="#C8A95B">
                </div>
            </div>
            <div class="modal__footer">
                <button class="btn btn--primary" type="submit">Salvar tag</button>
            </div>
        </form>
    </div>
</div>
