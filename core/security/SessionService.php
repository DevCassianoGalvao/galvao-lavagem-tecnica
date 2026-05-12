<?php

final class SessionService
{
    private const SESSION_TIMEOUT_SECONDS = 3600;
    private const REGENERATE_INTERVAL_SECONDS = 900;

    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $sessionPath = STORAGE_PATH . '/temp';

        if (is_dir($sessionPath) && is_writable($sessionPath)) {
            session_save_path($sessionPath);
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();
        self::enforceTimeout();
        self::rotatePeriodically();
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['_last_regenerated_at'] = time();
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }

        session_destroy();
    }

    private static function enforceTimeout(): void
    {
        $now = time();
        $lastActivity = $_SESSION['_last_activity_at'] ?? $now;

        if ($now - (int) $lastActivity > self::SESSION_TIMEOUT_SECONDS) {
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['_last_activity_at'] = $now;
            $_SESSION['_last_regenerated_at'] = $now;
            return;
        }

        $_SESSION['_last_activity_at'] = $now;
    }

    private static function rotatePeriodically(): void
    {
        $now = time();
        $lastRegenerated = $_SESSION['_last_regenerated_at'] ?? 0;

        if ($now - (int) $lastRegenerated > self::REGENERATE_INTERVAL_SECONDS) {
            self::regenerate();
        }
    }
}
