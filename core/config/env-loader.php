<?php

if (!function_exists('galvao_env_load')) {
    function galvao_env_load(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        $lastKey = null;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                if ($lastKey === 'OPENAI_API_KEY' && str_starts_with($line, '-')) {
                    $_ENV[$lastKey] = (string) ($_ENV[$lastKey] ?? '') . $line;
                    $_SERVER[$lastKey] = $_ENV[$lastKey];
                    putenv($lastKey . '=' . $_ENV[$lastKey]);
                }

                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = galvao_env_normalize(trim($value));

            if ($key === '') {
                continue;
            }

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key . '=' . $value);
            $lastKey = $key;
        }
    }
}

if (!function_exists('galvao_env')) {
    function galvao_env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            default => is_numeric($value) && preg_match('/^-?\d+$/', (string) $value) ? (int) $value : $value,
        };
    }
}

if (!function_exists('galvao_env_normalize')) {
    function galvao_env_normalize(string $value): string
    {
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}
