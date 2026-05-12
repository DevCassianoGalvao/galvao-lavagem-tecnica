<?php

require_once __DIR__ . '/env-loader.php';

define('BASE_PATH', dirname(__DIR__, 2));
define('PUBLIC_PATH', BASE_PATH . '/public');
define('ADMIN_PATH', BASE_PATH . '/admin');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('LOG_PATH', BASE_PATH . '/logs');

galvao_env_load(BASE_PATH . '/.env');

$envFile = __DIR__ . '/env.php';
$fallbackFile = __DIR__ . '/env.example.php';

$localConfig = file_exists($envFile) ? require $envFile : null;
$config = is_array($localConfig) ? $localConfig : require $fallbackFile;

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
