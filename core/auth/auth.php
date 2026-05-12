<?php

function auth_user(): ?array
{
    return $_SESSION['auth_user'] ?? null;
}

function auth_check(): bool
{
    return auth_user() !== null;
}

function require_auth(): void
{
    if (!auth_check()) {
        if (isset($GLOBALS['config'])) {
            try {
                $pdo = Connection::get($GLOBALS['config']);
                $auth = new AuthService($pdo, new SecurityLogger($pdo), new RateLimitService($pdo, new SecurityLogger($pdo)));

                if ($auth->attemptRememberedLogin()) {
                    return;
                }
            } catch (Throwable) {
                // Falhas de remember me nao devem revelar detalhes nem bloquear o redirect seguro.
            }
        }

        header('Location: /admin/login.php');
        exit;
    }
}

function auth_id(): ?int
{
    $user = auth_user();

    return $user ? (int) $user['id'] : null;
}

function auth_role(): string
{
    return auth_user()['role'] ?? 'viewer';
}

function auth_can(string $permission): bool
{
    return AuthService::roleCan(auth_role(), $permission);
}

function require_permission(string $permission): void
{
    require_auth();

    if (!auth_can($permission)) {
        http_response_code(403);
        exit('Acesso negado.');
    }
}
