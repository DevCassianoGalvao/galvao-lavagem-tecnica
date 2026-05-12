<?php

$visualItems = [];
$visualPairs = [];
$visualFacets = [];

try {
    $visualService = new VisualBankService(Connection::get($GLOBALS['config']));
    $visualItems = $visualService->search([], 80);
    $visualPairs = $visualService->beforeAfterPairs(10);
    $visualFacets = $visualService->facets();
} catch (Throwable) {
    $visualItems = [];
    $visualPairs = [];
    $visualFacets = [];
}

if (!$visualItems) {
    $visualItems = visualFallbackItems($crmLeads ?? [], $crmUploads ?? []);
}

if (!$visualPairs) {
    $visualPairs = visualFallbackPairs();
}

$visualFacets = array_replace_recursive(visualFallbackFacets(), array_filter($visualFacets));
?>

<div class="visual-bank-page" data-visual-bank>
    <section class="card visual-bank-hero">
        <div>
            <span class="eyebrow">Banco visual operacional</span>
            <h2>Biblioteca tecnica de superficies, clientes e transformacoes</h2>
            <p>Imagens centralizadas por upload unico, conectadas por referencias a clientes, superficies, servicos, produtos e historico tecnico.</p>
        </div>
        <div class="visual-bank-hero__stats">
            <strong><?= count($visualItems); ?></strong>
            <span>referencias visuais</span>
        </div>
    </section>

    <section class="card visual-filter-bar">
        <label class="field visual-search">
            <span>Pesquisa rapida</span>
            <input class="input" data-visual-search placeholder="Cliente, bairro, superficie, produto...">
        </label>
        <?= visualSelect('surface', 'Superficie', $visualFacets['surfaces']); ?>
        <?= visualSelect('neighborhood', 'Bairro', $visualFacets['neighborhoods']); ?>
        <?= visualSelect('tag', 'Tag', $visualFacets['tags']); ?>
        <?= visualSelect('dirt', 'Sujeira', $visualFacets['dirt']); ?>
        <?= visualSelect('difficulty', 'Acesso', $visualFacets['difficulties']); ?>
        <button class="btn btn--ghost" type="button" data-visual-clear>Limpar</button>
    </section>

    <section class="visual-bank-layout">
        <main class="visual-masonry" data-visual-grid>
            <?php foreach ($visualItems as $index => $item): ?>
                <?php
                $tags = visualTags($item['tags'] ?? '');
                $dirt = visualTags($item['dirt_types'] ?? '');
                $products = visualTags($item['products'] ?? '');
                $surface = $item['surface_type'] ?: ($item['surface_name'] ?? 'Superficie tecnica');
                $fullImage = visualImageUrl($item['upload_id'] ?? null);
                $thumb = visualImageUrl($item['upload_id'] ?? null, 'thumb');
                $tone = $index % 5;
                ?>
                <article
                    class="card visual-card visual-card--tone-<?= $tone; ?>"
                    data-visual-card
                    data-search="<?= e(strtolower(implode(' ', [
                        $item['client_name'] ?? '',
                        $item['neighborhood'] ?? '',
                        $surface,
                        $item['relation_type'] ?? '',
                        $item['caption'] ?? '',
                        $item['tags'] ?? '',
                        $item['dirt_types'] ?? '',
                        $item['products'] ?? '',
                    ]))); ?>"
                    data-surface="<?= e(strtolower($surface)); ?>"
                    data-neighborhood="<?= e(strtolower($item['neighborhood'] ?? '')); ?>"
                    data-tags="<?= e(strtolower($item['tags'] ?? '')); ?>"
                    data-dirt="<?= e(strtolower($item['dirt_types'] ?? '')); ?>"
                    data-difficulty="<?= e(strtolower($item['access_difficulty'] ?? '')); ?>"
                    data-full-image="<?= e($fullImage); ?>"
                    data-title="<?= e($surface); ?>"
                    data-client="<?= e($item['client_name'] ?? 'Cliente nao vinculado'); ?>"
                    data-meta="<?= e(($item['neighborhood'] ?? 'Bairro nao informado') . ' - ' . ($item['relation_type'] ?? 'referencia')); ?>"
                    data-history="<?= e(visualHistoryText($item)); ?>"
                >
                    <button class="visual-card__media" type="button" data-visual-open>
                        <?php if ($thumb): ?>
                            <img src="<?= e($thumb); ?>" alt="<?= e($surface); ?>" loading="lazy" decoding="async">
                        <?php endif; ?>
                        <span><?= e($item['relation_type'] ?? 'visual'); ?></span>
                    </button>
                    <div class="visual-card__body">
                        <div>
                            <strong><?= e($surface); ?></strong>
                            <p><?= e($item['client_name'] ?? 'Cliente nao vinculado'); ?></p>
                        </div>
                        <dl class="visual-meta">
                            <div><dt>Bairro</dt><dd><?= e($item['neighborhood'] ?? 'Nao informado'); ?></dd></div>
                            <div><dt>Data</dt><dd><?= e(visualDate($item['created_at'] ?? null)); ?></dd></div>
                            <div><dt>Servico</dt><dd><?= e($item['service_title'] ?? 'Referencia tecnica'); ?></dd></div>
                        </dl>
                        <div class="crm-tags">
                            <?php foreach (array_slice(array_merge($tags, $dirt), 0, 4) as $tag): ?>
                                <span class="crm-tag" style="--tag-color: #C8A95B;"><?= e($tag); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($products): ?>
                            <small class="visual-products">Produtos: <?= e(implode(', ', array_slice($products, 0, 3))); ?></small>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </main>

        <aside class="visual-side">
            <article class="card visual-compare">
                <div class="panel__header">
                    <div>
                        <span class="eyebrow">Antes e depois</span>
                        <h2>Comparador</h2>
                    </div>
                </div>
                <?php foreach (array_slice($visualPairs, 0, 3) as $pair): ?>
                    <button
                        class="visual-pair"
                        type="button"
                        data-compare-open
                        data-before="<?= e(visualImageUrl($pair['before_upload_id'] ?? null)); ?>"
                        data-after="<?= e(visualImageUrl($pair['after_upload_id'] ?? null)); ?>"
                        data-title="<?= e($pair['surface_name'] ?? 'Transformacao tecnica'); ?>"
                        data-client="<?= e($pair['client_name'] ?? 'Cliente'); ?>"
                    >
                        <span>Antes</span>
                        <span>Depois</span>
                        <strong><?= e($pair['surface_name'] ?? 'Superficie tecnica'); ?></strong>
                        <small><?= e($pair['client_name'] ?? 'Cliente'); ?> · <?= e($pair['neighborhood'] ?? 'Bairro'); ?></small>
                    </button>
                <?php endforeach; ?>
            </article>

            <article class="card visual-history">
                <div class="panel__header">
                    <div>
                        <span class="eyebrow">Historico</span>
                        <h2>Superficie selecionada</h2>
                    </div>
                </div>
                <div data-visual-history>
                    <strong>Selecione uma imagem</strong>
                    <p>O historico tecnico da superficie, servico e produtos aparece aqui.</p>
                </div>
            </article>
        </aside>
    </section>

    <div class="modal visual-modal" data-visual-modal>
        <div class="modal__panel visual-modal__panel">
            <div class="modal__header">
                <div>
                    <span class="eyebrow" data-visual-modal-client>Cliente</span>
                    <h2 data-visual-modal-title>Imagem tecnica</h2>
                    <p class="muted" data-visual-modal-meta></p>
                </div>
                <button class="btn btn--ghost btn--icon" type="button" data-visual-close>x</button>
            </div>
            <div class="visual-modal__body">
                <img data-visual-modal-image alt="Visualizacao fullscreen">
            </div>
        </div>
    </div>

    <div class="modal visual-modal" data-compare-modal>
        <div class="modal__panel visual-modal__panel visual-compare-modal">
            <div class="modal__header">
                <div>
                    <span class="eyebrow" data-compare-client>Cliente</span>
                    <h2 data-compare-title>Comparador antes e depois</h2>
                </div>
                <button class="btn btn--ghost btn--icon" type="button" data-compare-close>x</button>
            </div>
            <div class="visual-before-after">
                <figure>
                    <img data-compare-before alt="Antes">
                    <figcaption>Antes</figcaption>
                </figure>
                <figure>
                    <img data-compare-after alt="Depois">
                    <figcaption>Depois</figcaption>
                </figure>
            </div>
        </div>
    </div>
</div>

<?php
function visualFallbackItems(array $leads, array $uploads): array
{
    $items = [];
    $surfaces = ['Pedra natural', 'Muro externo', 'Garagem', 'Fachada', 'Area gourmet', 'Piscina'];

    foreach ($leads as $index => $lead) {
        $items[] = [
            'upload_id' => null,
            'relation_type' => $index % 2 === 0 ? 'before' : 'diagnostic',
            'surface_type' => $surfaces[$index % count($surfaces)],
            'surface_name' => $lead['surface'] ?? $surfaces[$index % count($surfaces)],
            'client_name' => $lead['client'] ?? 'Cliente',
            'neighborhood' => ['Jardim Europa', 'Alto da Boa Vista', 'Pinheiros', 'Moema'][$index % 4],
            'tags' => implode(', ', $lead['tags'] ?? ['Premium']),
            'dirt_types' => ['Lodo, musgo', 'Manchas', 'Mofo', 'Lodo'][$index % 4],
            'access_difficulty' => ['easy', 'medium', 'hard'][$index % 3],
            'service_title' => 'Revitalizacao tecnica externa',
            'products' => ['Desincrustante neutro', 'Protecao mineral', 'Aplicacao tecnica'][$index % 3],
            'caption' => $lead['ai_summary'] ?? '',
            'created_at' => date('Y-m-d H:i:s', strtotime("-{$index} days")),
        ];
    }

    foreach ($uploads as $index => $upload) {
        $items[] = [
            'upload_id' => null,
            'relation_type' => strtolower($upload['type'] ?? 'attachment'),
            'surface_type' => $upload['label'] ?? 'Superficie tecnica',
            'surface_name' => $upload['label'] ?? 'Superficie tecnica',
            'client_name' => 'Banco visual demo',
            'neighborhood' => 'Nova Friburgo',
            'tags' => 'Historico tecnico',
            'dirt_types' => 'Lodo',
            'access_difficulty' => 'medium',
            'service_title' => 'Historico visual',
            'products' => 'Produto tecnico',
            'caption' => 'Referencia visual preparada para vinculo por IDs.',
            'created_at' => date('Y-m-d H:i:s', strtotime("-{$index} weeks")),
        ];
    }

    return $items;
}

function visualFallbackPairs(): array
{
    return [
        ['before_upload_id' => null, 'after_upload_id' => null, 'surface_name' => 'Pedra natural', 'client_name' => 'Marina Albuquerque', 'neighborhood' => 'Jardim Europa'],
        ['before_upload_id' => null, 'after_upload_id' => null, 'surface_name' => 'Piscina e calcada', 'client_name' => 'Condominio Villa Serena', 'neighborhood' => 'Alto da Boa Vista'],
        ['before_upload_id' => null, 'after_upload_id' => null, 'surface_name' => 'Fachada comercial', 'client_name' => 'Atelier Brava', 'neighborhood' => 'Pinheiros'],
    ];
}

function visualFallbackFacets(): array
{
    return [
        'surfaces' => [
            ['label' => 'Garagem', 'value' => 'garagem'],
            ['label' => 'Muro', 'value' => 'muro'],
            ['label' => 'Pedra', 'value' => 'pedra'],
            ['label' => 'Fachada', 'value' => 'fachada'],
        ],
        'neighborhoods' => [
            ['label' => 'Nova Friburgo', 'value' => 'Nova Friburgo'],
            ['label' => 'Jardim Europa', 'value' => 'Jardim Europa'],
            ['label' => 'Alto da Boa Vista', 'value' => 'Alto da Boa Vista'],
        ],
        'tags' => [
            ['label' => 'Premium', 'value' => 'premium'],
            ['label' => 'Recorrencia', 'value' => 'recorrencia'],
            ['label' => 'IA visual', 'value' => 'ia-visual'],
        ],
        'dirt' => [
            ['label' => 'Lodo', 'value' => 'lodo'],
            ['label' => 'Musgo', 'value' => 'musgo'],
            ['label' => 'Manchas', 'value' => 'manchas'],
        ],
        'difficulties' => [
            ['label' => 'Facil', 'value' => 'easy'],
            ['label' => 'Media', 'value' => 'medium'],
            ['label' => 'Dificil', 'value' => 'hard'],
        ],
    ];
}

function visualSelect(string $name, string $label, array $options): string
{
    $html = '<label class="field"><span>' . e($label) . '</span><select class="select" data-visual-filter="' . e($name) . '">';
    $html .= '<option value="">Todos</option>';

    foreach ($options as $option) {
        $html .= '<option value="' . e(strtolower((string) $option['value'])) . '">' . e((string) $option['label']) . '</option>';
    }

    return $html . '</select></label>';
}

function visualImageUrl(mixed $uploadId, string $size = 'original'): string
{
    if (!$uploadId) {
        return '';
    }

    $url = 'api/image.php?id=' . (int) $uploadId;

    return $size === 'thumb' ? $url . '&size=thumb' : $url;
}

function visualTags(string $value): array
{
    return array_values(array_filter(array_map('trim', explode(',', $value))));
}

function visualDate(?string $value): string
{
    return $value ? date('d/m/Y', strtotime($value)) : 'Nao informado';
}

function visualHistoryText(array $item): string
{
    $parts = [
        'Cliente: ' . ($item['client_name'] ?? 'Nao vinculado'),
        'Superficie: ' . (($item['surface_type'] ?? '') ?: ($item['surface_name'] ?? 'Tecnica')),
        'Servico: ' . ($item['service_title'] ?? 'Nao vinculado'),
        'Produtos: ' . ($item['products'] ?? 'Nao informado'),
        'Observacao: ' . ($item['caption'] ?? 'Referencia visual operacional.'),
    ];

    return implode("\n", $parts);
}
?>
