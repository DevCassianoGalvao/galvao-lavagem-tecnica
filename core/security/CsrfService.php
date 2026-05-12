<?php

final class CsrfService
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . e(self::token()) . '">';
    }

    public static function validate(?string $token): bool
    {
        return is_string($token)
            && isset($_SESSION['_csrf_token'])
            && hash_equals($_SESSION['_csrf_token'], $token);
    }

    public static function requireValid(?string $token, ?SecurityLogger $logger = null): void
    {
        if (self::validate($token)) {
            return;
        }

        $logger?->log('warning', 'csrf_failed', 'Tentativa com token CSRF invalido.');
        http_response_code(419);
        exit('Token de seguranca invalido.');
    }
}
