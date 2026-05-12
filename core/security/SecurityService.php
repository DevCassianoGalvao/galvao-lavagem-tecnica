<?php

final class SecurityService
{
    public static function applyHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Permissions-Policy: geolocation=(self), camera=(self), microphone=()");
    }

    public static function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            exit('Metodo nao permitido.');
        }
    }

    public static function requireJsonPost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            return;
        }

        http_response_code(405);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Metodo nao permitido.']);
        exit;
    }

    public static function requireJsonCsrf(?SecurityLogger $logger = null): void
    {
        if (CsrfService::validate($_POST['_csrf_token'] ?? null)) {
            return;
        }

        $logger?->log('warning', 'csrf_failed_api', 'Token CSRF invalido em API.');
        http_response_code(419);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Token de seguranca invalido.']);
        exit;
    }
}
