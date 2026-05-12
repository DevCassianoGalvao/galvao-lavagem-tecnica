<?php

$tagName = $tagName ?? 'Tag';
$tagColor = $tagColor ?? '#C8A95B';
?>
<span class="crm-tag" style="--tag-color: <?= e($tagColor); ?>"><?= e($tagName); ?></span>
