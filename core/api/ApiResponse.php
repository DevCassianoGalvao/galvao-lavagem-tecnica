<?php

final class ApiResponse
{
    public static function success(array $data = [], string $message = 'OK', int $status = 200): void
    {
        self::send(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    public static function error(string $message, int $status = 400, array $context = []): void
    {
        self::send(['success' => false, 'message' => $message, 'error' => $context], $status);
    }

    public static function validation(array $errors, string $message = 'Dados invalidos.'): void
    {
        self::send(['success' => false, 'message' => $message, 'errors' => $errors], 422);
    }

    public static function send(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
