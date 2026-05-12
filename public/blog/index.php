<?php
require_once __DIR__ . '/../../core/bootstrap.php';

$title = 'Blog | Galvao Lavagem Tecnica';
$landingCssBundle = AssetService::landingBundle('css');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, follow">
    <title><?= e($title); ?></title>
    <?php if ($landingCssBundle): ?>
        <link rel="stylesheet" href="<?= e($landingCssBundle); ?>">
    <?php else: ?>
        <link rel="stylesheet" href="../assets/css/global.css">
        <link rel="stylesheet" href="../assets/css/landing.css">
    <?php endif; ?>
</head>
<body>
    <main class="section">
        <div class="container section-heading">
            <span class="eyebrow">Conteudo tecnico</span>
            <h1 class="section-title">Blog em preparacao.</h1>
            <p class="section-copy">Espaco reservado para guias sobre lavagem tecnica, manutencao preventiva, lodo, musgo e conservacao de areas externas em Nova Friburgo.</p>
            <a class="btn btn--primary" href="../landing/">Voltar para a landing</a>
        </div>
    </main>
</body>
</html>
