<?php

declare(strict_types=1);

ob_start();

function ai_json_response(array $payload, int $status = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function ai_ensure_tables(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS uploads (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        uploaded_by BIGINT UNSIGNED NULL,
        original_name VARCHAR(255) NOT NULL,
        stored_name VARCHAR(255) NOT NULL,
        storage_path VARCHAR(500) NOT NULL,
        mime_type VARCHAR(120) NOT NULL,
        extension VARCHAR(20) NOT NULL,
        size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
        width_px INT UNSIGNED NULL,
        height_px INT UNSIGNED NULL,
        sha256_hash CHAR(64) NOT NULL,
        image_role VARCHAR(40) NOT NULL DEFAULT "original",
        status VARCHAR(40) NOT NULL DEFAULT "active",
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_uploads_hash_role (sha256_hash, image_role),
        KEY idx_uploads_status_role (status, image_role),
        KEY idx_uploads_uploaded_by (uploaded_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $pdo->exec('CREATE TABLE IF NOT EXISTS upload_links (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        upload_id BIGINT UNSIGNED NOT NULL,
        client_id BIGINT UNSIGNED NULL,
        lead_id BIGINT UNSIGNED NULL,
        property_id BIGINT UNSIGNED NULL,
        surface_id BIGINT UNSIGNED NULL,
        service_id BIGINT UNSIGNED NULL,
        relation_type VARCHAR(60) NOT NULL,
        caption VARCHAR(255) NULL,
        created_by BIGINT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_upload_links_upload (upload_id),
        KEY idx_upload_links_lead (lead_id),
        KEY idx_upload_links_relation (relation_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $pdo->exec('CREATE TABLE IF NOT EXISTS ai_images (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        source_upload_id BIGINT UNSIGNED NOT NULL,
        result_upload_id BIGINT UNSIGNED NULL,
        client_id BIGINT UNSIGNED NULL,
        lead_id BIGINT UNSIGNED NULL,
        property_id BIGINT UNSIGNED NULL,
        surface_id BIGINT UNSIGNED NULL,
        service_id BIGINT UNSIGNED NULL,
        model_name VARCHAR(120) NULL,
        prompt_text TEXT NULL,
        status VARCHAR(40) NOT NULL DEFAULT "completed",
        analysis_json JSON NULL,
        error_message TEXT NULL,
        created_by BIGINT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_ai_images_source (source_upload_id),
        KEY idx_ai_images_result (result_upload_id),
        KEY idx_ai_images_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $pdo->exec('CREATE TABLE IF NOT EXISTS ai_image_usages (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        lead_id BIGINT UNSIGNED NULL,
        ip_address VARCHAR(45) NULL,
        session_id VARCHAR(128) NULL,
        source_upload_id BIGINT UNSIGNED NOT NULL,
        result_upload_id BIGINT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_ai_usage_session_created (session_id, created_at),
        KEY idx_ai_usage_ip_created (ip_address, created_at),
        KEY idx_ai_usage_lead_created (lead_id, created_at),
        KEY idx_ai_usage_source (source_upload_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $pdo->exec('CREATE TABLE IF NOT EXISTS settings (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(120) NOT NULL,
        setting_value TEXT NULL,
        value_type VARCHAR(30) NOT NULL DEFAULT "string",
        scope VARCHAR(40) NOT NULL DEFAULT "global",
        user_id BIGINT UNSIGNED NULL,
        is_private TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_settings_scope_key_user (scope, setting_key, user_id),
        KEY idx_settings_scope (scope)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

function ai_ensure_storage(): void
{
    $dirs = [
        STORAGE_PATH,
        STORAGE_PATH . '/temp',
        STORAGE_PATH . '/ai-images',
        STORAGE_PATH . '/ai-images/landing-originals',
        STORAGE_PATH . '/ai-images/results',
        STORAGE_PATH . '/thumbnails',
    ];

    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        @chmod($dir, 0755);
    }
}

register_shutdown_function(static function (): void {
    $error = error_get_last();

    if ($error === null) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

    if (!in_array((int) $error['type'], $fatalTypes, true)) {
        return;
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Não foi possível gerar a prévia agora.',
        'debug' => defined('APP_DEBUG') && APP_DEBUG ? $error['message'] : null,
    ], JSON_UNESCAPED_UNICODE);
});

try {
    $config = require __DIR__ . '/../../core/config/app.php';

    require_once __DIR__ . '/../../core/helpers/sanitize.php';
    require_once __DIR__ . '/../../core/database/Connection.php';
    require_once __DIR__ . '/../../core/security/SessionService.php';
    require_once __DIR__ . '/../../core/security/CsrfService.php';
    require_once __DIR__ . '/../../core/security/csrf.php';
    require_once __DIR__ . '/../../core/security/SecurityLogger.php';
    require_once __DIR__ . '/../../core/security/UploadSecurityService.php';
    require_once __DIR__ . '/../../core/services/AiLogger.php';
    require_once __DIR__ . '/../../core/services/SettingsService.php';
    require_once __DIR__ . '/../../core/services/ImageOptimizationService.php';
    require_once __DIR__ . '/../../core/services/ImageUploadService.php';
    require_once __DIR__ . '/../../core/services/ImageHistoryService.php';
    require_once __DIR__ . '/../../core/services/AiImageUsageService.php';
    require_once __DIR__ . '/../../core/services/AiVisualCacheService.php';
    require_once __DIR__ . '/../../core/services/AiVisualService.php';

    date_default_timezone_set((string) ($config['app_timezone'] ?? 'America/Sao_Paulo'));
    ai_ensure_storage();
    SessionService::start();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        ai_json_response(['success' => false, 'message' => 'Método não permitido.'], 405);
    }

    $pdo = Connection::get($config);
    ai_ensure_tables($pdo);
    $logger = new SecurityLogger($pdo);
    $csrfToken = $_POST['_csrf_token'] ?? null;
    $isSameOrigin = static function (): bool {
        $currentHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $source = (string) ($_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '');

        if ($currentHost === '' || $source === '') {
            return false;
        }

        $sourceHost = strtolower((string) parse_url($source, PHP_URL_HOST));

        return $sourceHost !== '' && $sourceHost === $currentHost;
    };

    if (!csrf_validate(is_string($csrfToken) ? $csrfToken : null) && !$isSameOrigin()) {
        $logger->log('warning', 'csrf_failed_landing_ai', 'Token CSRF inválido na simulação visual.');
        ai_json_response(['success' => false, 'message' => 'Atualize a página e tente novamente.'], 419);
    }

    if (empty($_FILES['environment_image'])) {
        ai_json_response(['success' => false, 'message' => 'Envie uma foto para gerar a prévia.'], 422);
    }

    $uploadService = new ImageUploadService($pdo, null, new UploadSecurityService($logger));
    $sourceUpload = $uploadService->storeUploadedFile($_FILES['environment_image'], 'ai-images/landing-originals');
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $sessionId = session_id();

    if (($config['ai_cache_enabled'] ?? true) && isset($sourceUpload['hash'])) {
        $cached = (new AiVisualCacheService($pdo))->findForSession((string) $sourceUpload['hash'], $sessionId);

        if ($cached) {
            ai_json_response([
                'success' => true,
                'cached' => true,
                'message' => 'Prévia visual recuperada. Use como referência; a avaliação final depende do diagnóstico no local.',
                'simulation' => $cached,
            ]);
        }
    }

    (new AiImageUsageService($pdo, $config))->assertAllowed(null, $ipAddress, $sessionId);

    $result = (new AiVisualService($pdo, $config, new AiLogger($pdo)))->simulateRevitalization($sourceUpload, [
        'caption' => 'Simulação visual - Landing Galvão Lavagem Técnica',
        'created_by' => null,
    ]);

    (new AiImageUsageService($pdo, $config))->register(null, $ipAddress, $sessionId, $sourceUpload['upload_id'], $result['result_upload_id'] ?? null);

    ai_json_response([
        'success' => true,
        'message' => 'Prévia visual gerada. Use como referência; a avaliação final depende do diagnóstico no local.',
        'simulation' => $result,
    ]);
} catch (RuntimeException $exception) {
    $softBlock = in_array($exception->getMessage(), ['limit', 'cooldown'], true);
    ai_json_response([
        'success' => false,
        'soft_block' => $softBlock,
        'message' => $softBlock
            ? 'Para seguir sem espera, continue para o orçamento e envie as fotos do ambiente.'
            : $exception->getMessage(),
    ], $softBlock ? 200 : 422);
} catch (Throwable $exception) {
    ai_json_response([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 422);
}
