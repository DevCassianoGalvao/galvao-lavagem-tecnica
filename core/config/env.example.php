<?php

return [
    'app_name' => galvao_env('APP_NAME', 'Galvao Lavagem Tecnica'),
    'app_url' => galvao_env('APP_URL', 'http://localhost/galvao-lavagem-tecnica'),
    'app_env' => galvao_env('APP_ENV', 'local'),
    'app_debug' => galvao_env('APP_DEBUG', true),
    'app_timezone' => galvao_env('APP_TIMEZONE', 'America/Sao_Paulo'),

    'db_host' => galvao_env('DB_HOST', '127.0.0.1'),
    'db_name' => galvao_env('DB_NAME', 'galvao_lavagem_tecnica'),
    'db_user' => galvao_env('DB_USER', 'root'),
    'db_pass' => galvao_env('DB_PASS', ''),
    'db_charset' => galvao_env('DB_CHARSET', 'utf8mb4'),

    'openai_api_key' => galvao_env('OPENAI_API_KEY', ''),
    'openai_text_model' => galvao_env('OPENAI_TEXT_MODEL', 'gpt-5.4-mini'),
    'openai_image_model' => galvao_env('OPENAI_IMAGE_MODEL', 'gpt-image-1.5'),
    'google_calendar_client_id' => galvao_env('GOOGLE_CALENDAR_CLIENT_ID', ''),
    'google_calendar_client_secret' => galvao_env('GOOGLE_CALENDAR_CLIENT_SECRET', ''),

    'upload_max_mb' => galvao_env('UPLOAD_MAX_MB', 10),
    'upload_allowed_mimes' => galvao_env('UPLOAD_ALLOWED_MIMES', 'image/jpeg,image/png,image/webp,image/gif'),
    'storage_disk' => galvao_env('STORAGE_DISK', 'local'),
    'storage_public_proxy' => galvao_env('STORAGE_PUBLIC_PROXY', '/admin/api/image.php'),

    'session_timeout_seconds' => galvao_env('SESSION_TIMEOUT_SECONDS', 3600),
    'remember_me_days' => galvao_env('REMEMBER_ME_DAYS', 30),
    'csrf_rotate_seconds' => galvao_env('CSRF_ROTATE_SECONDS', 7200),
    'security_rate_limit_enabled' => galvao_env('SECURITY_RATE_LIMIT_ENABLED', true),

    'ai_cache_enabled' => galvao_env('AI_CACHE_ENABLED', true),
    'ai_image_cooldown_seconds' => galvao_env('AI_IMAGE_COOLDOWN_SECONDS', 45),
    'ai_image_pre_quiz_limit' => galvao_env('AI_IMAGE_PRE_QUIZ_LIMIT', 1),
    'ai_image_post_quiz_limit' => galvao_env('AI_IMAGE_POST_QUIZ_LIMIT', 3),

    'backup_retention_daily' => galvao_env('BACKUP_RETENTION_DAILY', 14),
    'backup_retention_weekly' => galvao_env('BACKUP_RETENTION_WEEKLY', 8),
];
