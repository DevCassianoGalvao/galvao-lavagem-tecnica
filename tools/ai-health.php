<?php

declare(strict_types=1);

$config = require __DIR__ . '/../core/config/app.php';

header('Content-Type: text/plain; charset=utf-8');

$installKey = (string) galvao_env('CPANEL_INSTALL_KEY', '');
$providedKey = (string) ($_GET['key'] ?? '');

if ($installKey === '' || !hash_equals($installKey, $providedKey)) {
    http_response_code(403);
    echo "Acesso negado.\n";
    exit;
}

$apiKey = (string) ($config['openai_api_key'] ?? '');
$model = (string) ($config['openai_image_model'] ?? '');
$storageChecks = [
    'storage' => STORAGE_PATH,
    'storage/temp' => STORAGE_PATH . '/temp',
    'storage/ai-images' => STORAGE_PATH . '/ai-images',
    'storage/ai-images/landing-originals' => STORAGE_PATH . '/ai-images/landing-originals',
    'storage/ai-images/results' => STORAGE_PATH . '/ai-images/results',
    'storage/thumbnails' => STORAGE_PATH . '/thumbnails',
];

echo "Galvao AI Health\n";
echo "PHP: " . PHP_VERSION . "\n";
echo "APP_ENV: " . (string) ($config['app_env'] ?? '') . "\n";
echo "APP_URL: " . (string) ($config['app_url'] ?? '') . "\n";
echo "OPENAI_IMAGE_MODEL: " . ($model !== '' ? $model : 'VAZIO') . "\n";
echo "OPENAI_API_KEY: " . ($apiKey !== '' ? 'presente (' . strlen($apiKey) . ' caracteres)' : 'VAZIA') . "\n";
echo "cURL: " . (function_exists('curl_init') ? 'ok' : 'indisponivel') . "\n";
echo "GD/Image: " . (function_exists('imagecreatefromstring') ? 'ok' : 'indisponivel') . "\n";
echo "\nPastas:\n";

foreach ($storageChecks as $label => $path) {
    if (!is_dir($path)) {
        @mkdir($path, 0755, true);
    }

    @chmod($path, 0755);
    echo "- {$label}: " . (is_dir($path) ? 'existe' : 'nao existe') . ' / ' . (is_writable($path) ? 'gravavel' : 'sem escrita') . "\n";
}

echo "\nBanco:\n";

try {
    require_once __DIR__ . '/../core/database/Connection.php';
    $pdo = Connection::get($config);
    echo "- conexao: ok\n";

    foreach (['uploads', 'upload_links', 'ai_images', 'ai_image_usages', 'settings'] as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
        echo "- tabela {$table}: " . ($stmt->fetchColumn() ? 'ok' : 'ausente') . "\n";
    }
} catch (Throwable $exception) {
    echo "- conexao: erro - " . $exception->getMessage() . "\n";
}

