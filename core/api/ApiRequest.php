<?php

final class ApiRequest
{
    private array $json = [];

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        private array $post,
        public readonly array $files
    ) {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode(file_get_contents('php://input') ?: '{}', true);
            $this->json = is_array($decoded) ? $decoded : [];
        }
    }

    public static function capture(): self
    {
        $route = $_GET['route'] ?? ($_SERVER['PATH_INFO'] ?? '');
        $route = '/' . trim((string) $route, '/');

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $route === '/' ? '/' : $route,
            $_GET,
            $_POST,
            $_FILES
        );
    }

    public function input(): array
    {
        return $this->json ?: $this->post;
    }

    public function value(string $key, mixed $default = null): mixed
    {
        $input = $this->input();

        return $input[$key] ?? $default;
    }
}
