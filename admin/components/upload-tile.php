<?php

$label = $label ?? 'Imagem';
$type = $type ?? 'Preview';
$tone = $tone ?? 'stone';
?>
<article class="upload-tile upload-tile--<?= e($tone); ?>">
    <div class="upload-tile__visual">
        <span><?= e($type); ?></span>
    </div>
    <strong><?= e($label); ?></strong>
</article>
