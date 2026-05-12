<?php

final class CacheService
{
    public function __construct(private string $namespace = 'app')
    {
    }

    public function remember(string $key, int $ttlSeconds, callable $callback): mixed
    {
        $path = $this->path($key);

        if (is_file($path)) {
            $payload = json_decode((string) file_get_contents($path), true);

            if (is_array($payload) && ($payload['expires_at'] ?? 0) >= time()) {
                return $payload['value'];
            }
        }

        $value = $callback();
        $this->put($key, $value, $ttlSeconds);

        return $value;
    }

    public function put(string $key, mixed $value, int $ttlSeconds): void
    {
        $dir = STORAGE_PATH . '/cache/' . $this->namespace;

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($this->path($key), json_encode([
            'expires_at' => time() + $ttlSeconds,
            'value' => $value,
        ], JSON_UNESCAPED_UNICODE));
    }

    public function forget(string $key): void
    {
        $path = $this->path($key);

        if (is_file($path)) {
            unlink($path);
        }
    }

    private function path(string $key): string
    {
        return STORAGE_PATH . '/cache/' . $this->namespace . '/' . hash('sha256', $key) . '.json';
    }
}
