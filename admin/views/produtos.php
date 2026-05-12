<?php

$productService = null;
$products = [];
$productUsages = [];
$surfaceTypes = [];
$clients = [];
$services = [];
$surfaces = [];

try {
    $productService = new ProductService(Connection::get($GLOBALS['config']));
    $products = $productService->catalog();
    $productUsages = $productService->recentUsages();
    $surfaceTypes = $productService->surfaceTypes();
    $clients = $productService->clients();
    $services = $productService->services();
    $surfaces = $productService->surfaces();
} catch (Throwable) {
    $products = productFallbackCatalog();
    $productUsages = productFallbackUsages();
    $surfaceTypes = productFallbackSurfaces();
}

$categories = ProductService::categories();
?>

<div class="products-page" data-products-page>
    <section class="card products-hero">
        <div>
            <span class="eyebrow">Operacao tecnica</span>
            <h2>Produtos, diluicoes e aplicacoes por superficie</h2>
            <p>Catalogo operacional para padronizar insumos, compatibilidades, historico de uso e resultado tecnico por cliente e servico.</p>
        </div>
        <div class="products-hero__metric">
            <strong><?= count($products); ?></strong>
            <span>produtos catalogados</span>
        </div>
    </section>

    <section class="products-layout">
        <main class="products-main">
            <section class="card product-toolbar">
                <label class="field">
                    <span>Busca rapida</span>
                    <input class="input" data-product-search placeholder="Produto, categoria, diluicao, superficie...">
                </label>
                <label class="field">
                    <span>Categoria</span>
                    <select class="select" data-product-category>
                        <option value="">Todas</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= e($category); ?>"><?= e(ucfirst($category)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="btn btn--primary" type="button" data-product-new>Novo produto</button>
            </section>

            <section class="product-grid" data-product-grid>
                <?php foreach ($products as $product): ?>
                    <article
                        class="card product-card"
                        data-product-card
                        data-category="<?= e(strtolower($product['category'] ?? '')); ?>"
                        data-search="<?= e(strtolower(implode(' ', [
                            $product['name'] ?? '',
                            $product['category'] ?? '',
                            $product['dilution'] ?? '',
                            $product['description'] ?? '',
                            $product['surfaces'] ?? '',
                        ]))); ?>"
                    >
                        <div class="product-card__header">
                            <span class="badge badge--gold"><?= e($product['category'] ?? 'tecnico'); ?></span>
                            <small><?= ((int) ($product['is_active'] ?? 1)) === 1 ? 'Ativo' : 'Inativo'; ?></small>
                        </div>
                        <h3><?= e($product['name']); ?></h3>
                        <p><?= e($product['description'] ?? 'Produto preparado para aplicacao tecnica controlada.'); ?></p>
                        <dl class="product-specs">
                            <div><dt>Diluicao</dt><dd><?= e($product['dilution'] ?? 'A definir'); ?></dd></div>
                            <div><dt>Unidade</dt><dd><?= e($product['unit'] ?? 'un'); ?></dd></div>
                            <div><dt>Usos</dt><dd><?= e((string) ($product['usage_count'] ?? 0)); ?></dd></div>
                        </dl>
                        <div class="crm-tags">
                            <?php foreach (array_slice(productTags($product['surfaces'] ?? ''), 0, 4) as $surface): ?>
                                <span class="crm-tag" style="--tag-color: #C8A95B;"><?= e($surface); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <button class="btn btn--ghost" type="button" data-product-apply data-product-id="<?= (int) ($product['id'] ?? 0); ?>" data-product-name="<?= e($product['name']); ?>" data-product-dilution="<?= e($product['dilution'] ?? ''); ?>">Registrar aplicacao</button>
                    </article>
                <?php endforeach; ?>
            </section>
        </main>

        <aside class="products-side">
            <form class="card product-form" data-product-form>
                <input type="hidden" name="action" value="save_product">
                <input type="hidden" name="product_id" value="">
                <header>
                    <span class="eyebrow">Cadastro</span>
                    <h2>Produto tecnico</h2>
                </header>

                <label class="field"><span>Nome</span><input class="input" name="name" required></label>
                <label class="field"><span>Categoria</span><?= productCategorySelect($categories); ?></label>
                <label class="field"><span>Descricao</span><textarea class="textarea" name="description"></textarea></label>
                <div class="product-form-grid">
                    <label class="field"><span>Diluicao</span><input class="input" name="dilution" placeholder="1:10"></label>
                    <label class="field"><span>Unidade</span><input class="input" name="unit" value="ml"></label>
                </div>
                <label class="field"><span>Aplicacao</span><textarea class="textarea" name="application_notes" placeholder="Pulverizacao, tempo de acao, enxague..."></textarea></label>
                <label class="field"><span>Observacoes de seguranca</span><textarea class="textarea" name="safety_notes"></textarea></label>

                <div class="product-surface-checks">
                    <strong>Superficies permitidas</strong>
                    <?php foreach ($surfaceTypes as $surfaceType): ?>
                        <label>
                            <input type="checkbox" name="surface_type_ids[]" value="<?= (int) $surfaceType['id']; ?>">
                            <span><?= e($surfaceType['name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <label class="product-toggle">
                    <input type="checkbox" name="is_active" checked>
                    <span>Produto ativo</span>
                </label>

                <button class="btn btn--primary" type="submit">Salvar produto</button>
            </form>
        </aside>
    </section>

    <section class="card product-history">
        <div class="panel__header">
            <div>
                <span class="eyebrow">Historico operacional</span>
                <h2>Aplicacoes recentes</h2>
            </div>
            <span class="badge">cliente · servico · superficie</span>
        </div>
        <div class="product-usage-list">
            <?php foreach ($productUsages as $usage): ?>
                <article>
                    <div>
                        <strong><?= e($usage['product_name']); ?></strong>
                        <p><?= e(($usage['client_name'] ?? 'Cliente') . ' · ' . ($usage['service_title'] ?? 'Servico tecnico')); ?></p>
                    </div>
                    <span><?= e($usage['dilution_used'] ?? 'diluicao padrao'); ?></span>
                    <small><?= e($usage['result_summary'] ?? 'Resultado registrado'); ?></small>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="modal product-modal" data-product-usage-modal>
        <form class="modal__panel product-usage-form" data-product-usage-form>
            <input type="hidden" name="action" value="register_usage">
            <input type="hidden" name="product_id" data-usage-product-id>
            <div class="modal__header">
                <div>
                    <span class="eyebrow">Aplicacao tecnica</span>
                    <h2 data-usage-product-title>Registrar produto</h2>
                </div>
                <button class="btn btn--ghost btn--icon" type="button" data-product-usage-close>x</button>
            </div>
            <div class="modal__body">
                <div class="product-form-grid">
                    <label class="field"><span>Cliente</span><?= productEntitySelect('client_id', $clients, 'name'); ?></label>
                    <label class="field"><span>Servico</span><?= productEntitySelect('service_id', $services, 'title'); ?></label>
                    <label class="field"><span>Superficie</span><?= productSurfaceSelect($surfaces); ?></label>
                    <label class="field"><span>Diluicao usada</span><input class="input" name="dilution_used" data-usage-dilution></label>
                    <label class="field"><span>Quantidade</span><input class="input" type="number" step="0.001" name="quantity" value="0"></label>
                    <label class="field"><span>Unidade</span><input class="input" name="unit" value="ml"></label>
                </div>
                <label class="field"><span>Resultado</span><input class="input" name="result_summary" placeholder="Remocao uniforme de lodo, acabamento preservado..."></label>
                <label class="field"><span>Observacoes</span><textarea class="textarea" name="notes"></textarea></label>
            </div>
            <div class="modal__footer">
                <button class="btn btn--primary" type="submit">Registrar aplicacao</button>
            </div>
        </form>
    </div>

    <div class="kanban-save-status" data-product-status hidden>Salvando produto...</div>
</div>

<?php
function productTags(?string $value): array
{
    return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
}

function productCategorySelect(array $categories): string
{
    $html = '<select class="select" name="category">';

    foreach ($categories as $category) {
        $html .= '<option value="' . e($category) . '">' . e(ucfirst($category)) . '</option>';
    }

    return $html . '</select>';
}

function productEntitySelect(string $name, array $items, string $labelKey): string
{
    $html = '<select class="select" name="' . e($name) . '"><option value="">Selecionar</option>';

    foreach ($items as $item) {
        $html .= '<option value="' . (int) $item['id'] . '">' . e($item[$labelKey]) . '</option>';
    }

    return $html . '</select>';
}

function productSurfaceSelect(array $items): string
{
    $html = '<select class="select" name="surface_id"><option value="">Selecionar</option>';

    foreach ($items as $item) {
        $html .= '<option value="' . (int) $item['id'] . '">' . e($item['name'] . ' · ' . $item['surface_type']) . '</option>';
    }

    return $html . '</select>';
}

function productFallbackCatalog(): array
{
    return [
        ['id' => 1, 'name' => 'GLT Removedor de Lodo', 'category' => 'removedor de lodo', 'description' => 'Produto tecnico para lodo aderido em area externa.', 'dilution' => '1:8', 'unit' => 'ml', 'surfaces' => 'Muro, Pedra, Calcada', 'usage_count' => 18, 'is_active' => 1],
        ['id' => 2, 'name' => 'GLT Neutro Mineral', 'category' => 'limpeza neutra', 'description' => 'Limpeza controlada para superficies sensiveis.', 'dilution' => '1:20', 'unit' => 'ml', 'surfaces' => 'Pedra, Deck, Fachada', 'usage_count' => 9, 'is_active' => 1],
        ['id' => 3, 'name' => 'GLT Desengordurante Tecnico', 'category' => 'desengordurante', 'description' => 'Aplicacao pontual em garagem e area gourmet.', 'dilution' => '1:12', 'unit' => 'ml', 'surfaces' => 'Garagem, Area gourmet', 'usage_count' => 12, 'is_active' => 1],
    ];
}

function productFallbackUsages(): array
{
    return [
        ['product_name' => 'GLT Removedor de Lodo', 'client_name' => 'Marina Albuquerque', 'service_title' => 'Revitalizacao externa', 'dilution_used' => '1:8', 'result_summary' => 'Remocao uniforme de lodo em pedra.'],
        ['product_name' => 'GLT Neutro Mineral', 'client_name' => 'Condominio Villa Serena', 'service_title' => 'Piscina e calcada', 'dilution_used' => '1:20', 'result_summary' => 'Acabamento preservado.'],
    ];
}

function productFallbackSurfaces(): array
{
    return [
        ['id' => 1, 'name' => 'Garagem'],
        ['id' => 2, 'name' => 'Muro'],
        ['id' => 3, 'name' => 'Pedra'],
        ['id' => 4, 'name' => 'Fachada'],
    ];
}
?>
