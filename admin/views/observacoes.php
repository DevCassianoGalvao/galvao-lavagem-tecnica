<?php

$noteRows = [];
$noteClients = [];
$noteServices = [];
$noteLeads = [];

try {
    $noteService = new NoteService(Connection::get($GLOBALS['config']));
    $noteRows = $noteService->timeline([], 80);
    $noteClients = $noteService->clients();
    $noteServices = $noteService->services();
    $noteLeads = $noteService->leads();
} catch (Throwable) {
    $noteRows = noteFallbackRows($crmNotes ?? [], $crmClient ?? []);
}
?>

<div class="notes-page" data-notes-page>
    <section class="card notes-hero">
        <div>
            <span class="eyebrow">Observacoes internas</span>
            <h2>Timeline detalhada de clientes, leads e servicos</h2>
            <p>Registre contexto de atendimento, operacao, financeiro e tecnica com responsavel, tags, fixacao e associacoes por entidade.</p>
        </div>
        <div class="notes-hero__metric">
            <strong><?= count($noteRows); ?></strong>
            <span>registros internos</span>
        </div>
    </section>

    <section class="notes-layout">
        <aside class="card notes-composer">
            <form data-note-form>
                <input type="hidden" name="action" value="create">
                <header>
                    <span class="eyebrow">Novo registro</span>
                    <h2>Observacao interna</h2>
                </header>

                <label class="field">
                    <span>Categoria</span>
                    <select class="select" name="note_type">
                        <?php foreach (NoteService::TYPES as $value => $label): ?>
                            <option value="<?= e($value); ?>"><?= e($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="field"><span>Titulo</span><input class="input" name="title" placeholder="Resumo curto"></label>
                <label class="field"><span>Observacao</span><textarea class="textarea" name="body" required></textarea></label>
                <label class="field"><span>Tags opcionais</span><input class="input" name="tags" placeholder="prioritario, proposta, financeiro"></label>

                <div class="notes-association-grid">
                    <label class="field"><span>Cliente</span><?= noteEntitySelect('client_id', $noteClients, 'name'); ?></label>
                    <label class="field"><span>Servico</span><?= noteEntitySelect('service_id', $noteServices, 'title'); ?></label>
                    <label class="field"><span>Lead</span><?= noteEntitySelect('lead_id', $noteLeads, 'name'); ?></label>
                </div>

                <label class="note-pin-toggle">
                    <input type="checkbox" name="is_pinned">
                    <span>Fixar como importante</span>
                </label>

                <button class="btn btn--primary" type="submit">Salvar observacao</button>
            </form>
        </aside>

        <main class="notes-main">
            <section class="card notes-filter-bar">
                <label class="field">
                    <span>Busca rapida</span>
                    <input class="input" data-note-search placeholder="Buscar por cliente, servico, tag, texto...">
                </label>
                <label class="field">
                    <span>Categoria</span>
                    <select class="select" data-note-type-filter>
                        <option value="">Todas</option>
                        <?php foreach (NoteService::TYPES as $value => $label): ?>
                            <option value="<?= e($value); ?>"><?= e($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </section>

            <section class="notes-timeline" data-notes-timeline>
                <?php foreach ($noteRows as $note): ?>
                    <?php $tags = noteTags($note['tags_json'] ?? ''); ?>
                    <article
                        class="card note-timeline-card <?= (int) ($note['is_pinned'] ?? 0) === 1 ? 'is-pinned' : ''; ?>"
                        data-note-card
                        data-note-id="<?= (int) ($note['id'] ?? 0); ?>"
                        data-note-type="<?= e($note['note_type'] ?? 'general'); ?>"
                        data-search="<?= e(strtolower(implode(' ', [
                            $note['title'] ?? '',
                            $note['body'] ?? '',
                            $note['client_name'] ?? '',
                            $note['lead_name'] ?? '',
                            $note['service_title'] ?? '',
                            implode(' ', $tags),
                        ]))); ?>"
                    >
                        <div class="note-timeline-card__rail">
                            <span></span>
                        </div>
                        <div class="note-timeline-card__content">
                            <header>
                                <div>
                                    <span class="badge badge--gold"><?= e(noteTypeLabel($note['note_type'] ?? 'general')); ?></span>
                                    <?php if ((int) ($note['is_pinned'] ?? 0) === 1): ?>
                                        <span class="badge">Fixada</span>
                                    <?php endif; ?>
                                </div>
                                <button class="btn btn--ghost" type="button" data-note-pin><?= (int) ($note['is_pinned'] ?? 0) === 1 ? 'Desfixar' : 'Fixar'; ?></button>
                            </header>
                            <h3><?= e($note['title'] ?: 'Observacao interna'); ?></h3>
                            <p><?= e($note['body']); ?></p>
                            <div class="note-meta-row">
                                <span><?= e($note['author_name'] ?? $note['author'] ?? 'Equipe'); ?></span>
                                <span><?= e(noteDate($note['created_at'] ?? $note['time'] ?? null)); ?></span>
                                <?php if (!empty($note['client_name'])): ?><span><?= e($note['client_name']); ?></span><?php endif; ?>
                                <?php if (!empty($note['service_title'])): ?><span><?= e($note['service_title']); ?></span><?php endif; ?>
                            </div>
                            <?php if ($tags): ?>
                                <div class="crm-tags">
                                    <?php foreach ($tags as $tag): ?>
                                        <span class="crm-tag" style="--tag-color: #C8A95B;"><?= e($tag); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        </main>
    </section>

    <div class="kanban-save-status" data-note-status hidden>Salvando observacao...</div>
</div>

<?php
function noteEntitySelect(string $name, array $items, string $labelKey): string
{
    $html = '<select class="select" name="' . e($name) . '"><option value="">Nao associar</option>';

    foreach ($items as $item) {
        $html .= '<option value="' . (int) $item['id'] . '">' . e($item[$labelKey]) . '</option>';
    }

    return $html . '</select>';
}

function noteTags(mixed $value): array
{
    if (is_array($value)) {
        return array_values(array_filter($value));
    }

    $decoded = json_decode((string) $value, true);

    if (is_array($decoded)) {
        return array_values(array_filter($decoded));
    }

    return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
}

function noteTypeLabel(string $type): string
{
    return NoteService::TYPES[$type] ?? ucfirst($type);
}

function noteDate(?string $value): string
{
    return $value ? date('d/m/Y H:i', strtotime($value)) : 'Agora';
}

function noteFallbackRows(array $notes, array $client): array
{
    return array_map(static fn (array $note, int $index): array => [
        'id' => 0,
        'note_type' => ['attendance', 'operational', 'technical'][$index % 3],
        'title' => $index === 0 ? 'Atendimento consultivo' : 'Observacao operacional',
        'body' => $note['body'],
        'author' => $note['author'] ?? 'Equipe',
        'client_name' => $client['name'] ?? 'Cliente',
        'created_at' => $note['time'] ?? 'Agora',
        'tags_json' => json_encode(['premium', 'interno'], JSON_UNESCAPED_UNICODE),
        'is_pinned' => $index === 0 ? 1 : 0,
    ], $notes, array_keys($notes));
}
?>
