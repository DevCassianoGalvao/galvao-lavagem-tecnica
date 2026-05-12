<?php

require_once __DIR__ . '/../core/config/app.php';

$checks = [];

$add = static function (string $label, bool $ok, string $detail = '') use (&$checks): void {
    $checks[] = ['label' => $label, 'ok' => $ok, 'detail' => $detail];
};

$requiredDirs = [
    STORAGE_PATH,
    STORAGE_PATH . '/uploads',
    STORAGE_PATH . '/thumbnails',
    STORAGE_PATH . '/ai-images',
    STORAGE_PATH . '/temp',
    STORAGE_PATH . '/backups/daily',
    STORAGE_PATH . '/backups/weekly',
    LOG_PATH,
    ENV_LOG_PATH,
];

foreach ($requiredDirs as $dir) {
    $add('Diretorio gravavel: ' . str_replace(BASE_PATH . '/', '', $dir), is_dir($dir) && is_writable($dir), $dir);
}

$add('.env presente', is_file(BASE_PATH . '/.env'), 'Copie .env.example e ajuste as credenciais.');
$add('APP_ENV configurado', in_array(APP_ENV, ['local', 'staging', 'production'], true), APP_ENV);
$add('Debug desligado em producao', APP_ENV !== 'production' || APP_DEBUG === false, APP_DEBUG ? 'APP_DEBUG=true' : 'APP_DEBUG=false');
$add('Banco configurado', (string) ($config['db_name'] ?? '') !== '' && (string) ($config['db_user'] ?? '') !== '', 'DB_NAME/DB_USER');
$add('OpenAI configuravel', array_key_exists('openai_api_key', $config), 'OPENAI_API_KEY pode ficar vazio em homologacao.');
$add('Protecao core', is_file(BASE_PATH . '/core/.htaccess'), 'core/.htaccess');
$add('Protecao storage', is_file(STORAGE_PATH . '/.htaccess'), 'storage/.htaccess');
$add('Protecao logs', is_file(LOG_PATH . '/.htaccess'), 'logs/.htaccess');
$add('Assets admin gerados', is_file(ADMIN_PATH . '/assets/dist/admin.min.css') && is_file(ADMIN_PATH . '/assets/dist/admin.min.js'), 'tools/build-assets.php');
$add('Assets landing gerados', is_file(PUBLIC_PATH . '/assets/dist/landing.min.css') && is_file(PUBLIC_PATH . '/assets/dist/landing.min.js'), 'tools/build-assets.php');

$failed = array_filter($checks, static fn (array $check): bool => !$check['ok']);

foreach ($checks as $check) {
    echo ($check['ok'] ? '[OK] ' : '[ERRO] ') . $check['label'];
    echo $check['detail'] !== '' ? ' - ' . $check['detail'] : '';
    echo PHP_EOL;
}

echo PHP_EOL . (count($failed) === 0 ? 'Deploy check concluido sem erros.' : count($failed) . ' pendencia(s) encontrada(s).') . PHP_EOL;
exit(count($failed) === 0 ? 0 : 1);
