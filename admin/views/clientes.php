<div class="crm-page client-profile">
    <section class="client-hero card">
        <div>
            <span class="eyebrow">Perfil completo</span>
            <h2><?= e($crmClient['name']); ?></h2>
            <p><?= e($crmClient['document']); ?> · <?= e($crmClient['property']); ?></p>
            <div class="crm-tags">
                <?php foreach ($crmClient['tags'] as $tag): ?>
                    <?php partial(__DIR__ . '/../components/tag-pill.php', ['tagName' => $tag]); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="client-hero__actions">
            <a class="btn btn--primary" href="https://wa.me/<?= e($crmClient['phone']); ?>?text=<?= rawurlencode('Olá, recebemos seu diagnóstico técnico da Galvão Lavagem Técnica.'); ?>" target="_blank" rel="noopener">Chamar no WhatsApp</a>
            <button class="btn btn--ghost" type="button" data-copy-whatsapp="<?= e($crmClient['phone']); ?>">Copiar telefone</button>
        </div>
    </section>

    <section class="client-grid">
        <aside class="card panel client-details">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Dados</span>
                    <h2>Cliente e localizacao</h2>
                </div>
            </div>
            <dl class="detail-list">
                <div><dt>Telefone</dt><dd><?= e($crmClient['phone']); ?></dd></div>
                <div><dt>E-mail</dt><dd><?= e($crmClient['email']); ?></dd></div>
                <div><dt>Endereco</dt><dd><?= e($crmClient['address']); ?></dd></div>
                <div><dt>Coordenadas</dt><dd><?= e($crmClient['coordinates']); ?></dd></div>
            </dl>
            <div class="external-links">
                <?php foreach ($crmClient['external_links'] as $link): ?>
                    <a class="btn btn--ghost" href="<?= e($link['url']); ?>" target="_blank" rel="noopener"><?= e($link['label']); ?></a>
                <?php endforeach; ?>
            </div>
        </aside>

        <article class="card panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Timeline</span>
                    <h2>Historico do relacionamento</h2>
                </div>
                <button class="btn btn--ghost" type="button">Novo evento</button>
            </div>
            <div class="timeline-list">
                <?php foreach ($crmTimeline as $event): ?>
                    <article class="timeline-item">
                        <span><?= e($event['type']); ?></span>
                        <div>
                            <strong><?= e($event['title']); ?></strong>
                            <p><?= e($event['body']); ?></p>
                            <small><?= e($event['time']); ?></small>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section class="client-grid client-grid--bottom">
        <article class="card panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Recorrencia</span>
                    <h2>Retornos preventivos</h2>
                </div>
                <span class="badge badge--gold">Nova Friburgo · 6 meses</span>
            </div>
            <div class="client-recurrence-list">
                <?php foreach (($crmRecurrences ?? []) as $recurrence): ?>
                    <article>
                        <div>
                            <strong><?= e($recurrence['title']); ?></strong>
                            <p><?= e($recurrence['reason']); ?></p>
                        </div>
                        <span><?= e($recurrence['due_at']); ?></span>
                        <small><?= e($recurrence['status']); ?></small>
                    </article>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="card panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Observacoes internas</span>
                    <h2>Comentarios da equipe</h2>
                </div>
            </div>
            <form class="comment-box" data-comment-form>
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="client_id" value="<?= (int) ($crmClient['id'] ?? 0); ?>">
                <select class="select" name="note_type">
                    <?php foreach (NoteService::TYPES as $value => $label): ?>
                        <option value="<?= e($value); ?>"><?= e($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <input class="input" name="title" placeholder="Titulo da observacao">
                <textarea class="textarea" name="body" placeholder="Adicionar observacao interna..." required></textarea>
                <input class="input" name="tags" placeholder="Tags opcionais separadas por virgula">
                <button class="btn btn--primary" type="submit">Comentar</button>
            </form>
            <div class="comment-list" data-comment-list>
                <?php foreach ($crmNotes as $note): ?>
                    <article class="comment-item">
                        <strong><?= e($note['author']); ?></strong>
                        <p><?= e($note['body']); ?></p>
                        <small><?= e($note['time']); ?></small>
                    </article>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="card panel">
            <div class="panel__header">
                <div>
                    <span class="eyebrow">Uploads</span>
                    <h2>Banco visual do cliente</h2>
                </div>
                <span class="badge badge--gold">Sem duplicar imagens</span>
            </div>
            <div class="upload-grid">
                <?php foreach ($crmUploads as $upload): ?>
                    <?php partial(__DIR__ . '/../components/upload-tile.php', $upload); ?>
                <?php endforeach; ?>
            </div>
        </article>
    </section>
</div>
