<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$bundles = [
    [
        'type' => 'css',
        'output' => $root . '/public/assets/dist/landing.min.css',
        'files' => [
            $root . '/public/assets/css/variables.css',
            $root . '/public/assets/css/reset.css',
            $root . '/public/assets/css/components.css',
            $root . '/public/assets/css/global.css',
            $root . '/public/assets/css/landing.css',
        ],
    ],
    [
        'type' => 'js',
        'output' => $root . '/public/assets/dist/landing.min.js',
        'files' => [
            $root . '/public/assets/js/main.js',
            $root . '/public/assets/js/landing.js',
            $root . '/public/assets/js/quiz.js',
        ],
    ],
    [
        'type' => 'css',
        'output' => $root . '/admin/assets/dist/admin.min.css',
        'files' => [
            $root . '/public/assets/css/variables.css',
            $root . '/public/assets/css/reset.css',
            $root . '/public/assets/css/components.css',
            $root . '/public/assets/css/global.css',
            $root . '/admin/assets/css/admin.css',
        ],
    ],
    [
        'type' => 'js',
        'output' => $root . '/admin/assets/dist/admin.min.js',
        'files' => [
            $root . '/admin/assets/js/admin.js',
        ],
    ],
];

foreach ($bundles as $bundle) {
    $content = '';

    foreach ($bundle['files'] as $file) {
        $content .= PHP_EOL . file_get_contents($file);
    }

    $content = $bundle['type'] === 'css'
        ? minifyCss($content)
        : minifyJs($content);

    if (!is_dir(dirname($bundle['output']))) {
        mkdir(dirname($bundle['output']), 0775, true);
    }

    file_put_contents($bundle['output'], $content);
    echo 'Built ' . str_replace($root . '/', '', $bundle['output']) . ' (' . strlen($content) . " bytes)" . PHP_EOL;
}

function minifyCss(string $css): string
{
    $css = preg_replace('/@import\s+url\([^)]+\);\s*/', '', $css) ?? $css;
    $css = preg_replace('!/\*.*?\*/!s', '', $css) ?? $css;
    $css = preg_replace('/\s+/', ' ', $css) ?? $css;
    $css = preg_replace('/\s*([{}:;,>])\s*/', '$1', $css) ?? $css;
    $css = str_replace(';}', '}', $css);

    return trim($css);
}

function minifyJs(string $js): string
{
    $lines = preg_split('/\R/', $js) ?: [];
    $compact = [];

    foreach ($lines as $line) {
        $line = rtrim($line);

        if ($line === '' || preg_match('/^\s*\/\/[^:]/', $line)) {
            continue;
        }

        $compact[] = $line;
    }

    // Mantem o JS semanticamente identico. A compactacao agressiva deve entrar
    // futuramente com uma ferramenta dedicada no pipeline de deploy.
    return trim(implode(PHP_EOL, $compact));
}
