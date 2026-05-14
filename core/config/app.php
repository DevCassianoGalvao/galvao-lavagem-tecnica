<?php

require_once __DIR__ . '/env-loader.php';

define('BASE_PATH', dirname(__DIR__, 2));
define('PUBLIC_PATH', BASE_PATH . '/public');
define('ADMIN_PATH', BASE_PATH . '/admin');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('LOG_PATH', BASE_PATH . '/logs');

galvao_env_load(BASE_PATH . '/.env');

$fallbackFile = __DIR__ . '/env.example.php';

$config = require $fallbackFile;

$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));

if ($host !== '' && !str_contains($host, 'localhost') && !str_contains($host, '127.0.0.1')) {
    $config['app_env'] = galvao_env('APP_ENV', 'production');
    $config['app_url'] = galvao_env('APP_URL', 'https://' . $host);
    $config['app_debug'] = galvao_env('APP_DEBUG', false);
}

foreach ([
    'db_host' => ['DB_HOST', 'localhost'],
    'db_name' => ['DB_NAME', null],
    'db_user' => ['DB_USER', null],
    'db_pass' => ['DB_PASS', ''],
    'db_charset' => ['DB_CHARSET', 'utf8mb4'],
    'openai_api_key' => ['OPENAI_API_KEY', ''],
    'openai_text_model' => ['OPENAI_TEXT_MODEL', 'gpt-5.4-mini'],
    'openai_image_model' => ['OPENAI_IMAGE_MODEL', 'gpt-image-1.5'],
    'brevo_enabled' => ['BREVO_ENABLED', false],
    'brevo_api_key' => ['BREVO_API_KEY', ''],
    'brevo_from_email' => ['BREVO_FROM_EMAIL', ''],
    'brevo_from_name' => ['BREVO_FROM_NAME', 'Galvão Lavagem Técnica'],
    'lead_notification_email' => ['LEAD_NOTIFICATION_EMAIL', ''],
    'admin_email' => ['ADMIN_EMAIL', 'admin@galvao.local'],
    'admin_password' => ['ADMIN_PASSWORD', 'Admin@12345'],
    'storage_public_proxy' => ['STORAGE_PUBLIC_PROXY', '/admin/api/image.php'],
] as $configKey => [$envKey, $default]) {
    $value = galvao_env($envKey, $default ?? ($config[$configKey] ?? null));

    if ($value !== null) {
        $config[$configKey] = $value;
    }
}

$environment = (string) ($config['app_env'] ?? galvao_env('APP_ENV', 'local'));
$environmentFile = __DIR__ . '/environments/' . $environment . '.php';

if (is_file($environmentFile)) {
    $config = array_replace($config, require $environmentFile);
}

$envLogPath = LOG_PATH . '/' . $environment;
if (!is_dir($envLogPath)) {
    @mkdir($envLogPath, 0755, true);
}

define('APP_ENV', $environment);
define('APP_DEBUG', (bool) ($config['app_debug'] ?? false));
define('ENV_LOG_PATH', $envLogPath);

return $config;
