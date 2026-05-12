<?php

require_once __DIR__ . '/../core/config/app.php';

$dirs = [
    STORAGE_PATH . '/uploads',
    STORAGE_PATH . '/thumbnails',
    STORAGE_PATH . '/ai-images',
    STORAGE_PATH . '/temp',
    STORAGE_PATH . '/backups/daily',
    STORAGE_PATH . '/backups/weekly',
    LOG_PATH . '/local',
    LOG_PATH . '/staging',
    LOG_PATH . '/production',
    ENV_LOG_PATH,
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $gitkeep = rtrim($dir, '/\\') . '/.gitkeep';
    if (!is_file($gitkeep)) {
        file_put_contents($gitkeep, '');
    }

    echo 'Preparado: ' . $dir . PHP_EOL;
}

echo 'Estrutura de deploy preparada para ' . APP_ENV . PHP_EOL;
