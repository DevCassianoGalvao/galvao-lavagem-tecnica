<?php

final class AuthService
{
    private const REMEMBER_COOKIE = 'galvao_remember';
    private const REMEMBER_DAYS = 30;
    private const PASSWORD_RESET_MINUTES = 45;

    public function __construct(
        private PDO $pdo,
        private SecurityLogger $logger,
        private RateLimitService $rateLimiter
    ) {
    }

    public function attempt(string $email, string $password, bool $remember = false): bool
    {
        $identity = 'login|' . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0') . '|' . strtolower($email);

        if (!$this->rateLimiter->hit('login', 5, 900, $identity)) {
            $this->recordLogin(null, $email, 'failed', 'rate_limited');
            return false;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, name, email, password_hash, role, status
             FROM users
             WHERE email = :email
               AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
            $this->logger->log('warning', 'login_failed', 'Falha de login.', ['email' => $email]);
            $this->recordLogin($user ? (int) $user['id'] : null, $email, 'failed', !$user ? 'user_not_found' : 'invalid_password_or_status');
            return false;
        }

        $this->loginUser($user);

        $update = $this->pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $update->execute(['id' => (int) $user['id']]);
        $this->recordLogin((int) $user['id'], $email, 'success');
        $this->logger->log('info', 'login_success', 'Login realizado com sucesso.', ['user_id' => (int) $user['id']]);

        if ($remember) {
            $this->issueRememberToken((int) $user['id']);
        }

        return true;
    }

    public function attemptRememberedLogin(): bool
    {
        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? '';

        if (!$cookie || !str_contains($cookie, ':')) {
            return false;
        }

        [$selector, $token] = explode(':', $cookie, 2);

        if (!preg_match('/^[a-f0-9]{24}$/', $selector) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            $this->clearRememberCookie();
            return false;
        }

        $stmt = $this->pdo->prepare(
            'SELECT rt.id AS token_id, rt.user_id, rt.token_hash, u.name, u.email, u.role, u.status
             FROM remember_tokens rt
             INNER JOIN users u ON u.id = rt.user_id
             WHERE rt.selector = :selector
               AND rt.revoked_at IS NULL
               AND rt.expires_at > NOW()
               AND u.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['selector' => $selector]);
        $row = $stmt->fetch();

        if (!$row || $row['status'] !== 'active' || !hash_equals($row['token_hash'], hash('sha256', $token))) {
            $this->clearRememberCookie();
            $this->logger->log('warning', 'remember_login_failed', 'Token persistente invalido.', ['selector' => $selector]);
            return false;
        }

        $this->loginUser($row);
        $this->rotateRememberToken((int) $row['token_id'], (int) $row['user_id']);
        $this->recordLogin((int) $row['user_id'], $row['email'], 'remembered');

        return true;
    }

    public function createPasswordReset(string $email): ?string
    {
        $identity = 'password_reset|' . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0') . '|' . strtolower($email);

        if (!$this->rateLimiter->hit('password_reset', 4, 3600, $identity)) {
            $this->logger->log('warning', 'password_reset_rate_limited', 'Recuperacao de senha limitada.', ['email' => $email]);
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, email, status
             FROM users
             WHERE email = :email
               AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || $user['status'] !== 'active') {
            $this->logger->log('info', 'password_reset_requested_unknown', 'Recuperacao solicitada sem usuario ativo.', ['email' => $email]);
            return null;
        }

        $selector = bin2hex(random_bytes(12));
        $token = bin2hex(random_bytes(32));
        $stmt = $this->pdo->prepare(
            'INSERT INTO password_resets (user_id, selector, token_hash, expires_at)
             VALUES (:user_id, :selector, :token_hash, :expires_at)'
        );
        $stmt->execute([
            'user_id' => (int) $user['id'],
            'selector' => $selector,
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + self::PASSWORD_RESET_MINUTES * 60),
        ]);

        $this->logger->log('info', 'password_reset_requested', 'Recuperacao de senha solicitada.', ['user_id' => (int) $user['id']]);

        return $selector . ':' . $token;
    }

    public function resetPassword(string $selector, string $token, string $password): bool
    {
        if (strlen($password) < 10 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            throw new InvalidArgumentException('Use uma senha com pelo menos 10 caracteres, letras e numeros.');
        }

        $stmt = $this->pdo->prepare(
            'SELECT pr.id, pr.user_id, pr.token_hash, u.email
             FROM password_resets pr
             INNER JOIN users u ON u.id = pr.user_id
             WHERE pr.selector = :selector
               AND pr.used_at IS NULL
               AND pr.expires_at > NOW()
               AND u.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['selector' => $selector]);
        $reset = $stmt->fetch();

        if (!$reset || !hash_equals($reset['token_hash'], hash('sha256', $token))) {
            $this->logger->log('warning', 'password_reset_invalid', 'Tentativa invalida de reset de senha.', ['selector' => $selector]);
            return false;
        }

        $this->pdo->beginTransaction();

        try {
            $update = $this->pdo->prepare(
                'UPDATE users
                 SET password_hash = :password_hash, updated_at = NOW()
                 WHERE id = :id'
            );
            $update->execute([
                'id' => (int) $reset['user_id'],
                'password_hash' => self::hashPassword($password),
            ]);

            $mark = $this->pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = :id');
            $mark->execute(['id' => (int) $reset['id']]);

            $revoke = $this->pdo->prepare('UPDATE remember_tokens SET revoked_at = NOW() WHERE user_id = :user_id AND revoked_at IS NULL');
            $revoke->execute(['user_id' => (int) $reset['user_id']]);

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        $this->logger->log('warning', 'password_reset_completed', 'Senha redefinida por recuperacao.', ['user_id' => (int) $reset['user_id']]);

        return true;
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function logout(): void
    {
        $user = auth_user();
        if ($user) {
            $this->recordLogin((int) $user['id'], $user['email'] ?? null, 'logout');
        }
        $this->revokeCurrentRememberToken();
        $this->logger->log('info', 'logout', 'Logout realizado.', ['user_id' => $user['id'] ?? null]);
        SessionService::destroy();
    }

    public static function roleCan(string $role, string $permission): bool
    {
        $matrix = [
            'owner' => ['*'],
            'admin' => ['*'],
            'manager' => ['dashboard', 'crm', 'kanban', 'calendar', 'uploads', 'products', 'queues', 'logs'],
            'operator' => ['dashboard', 'crm', 'kanban', 'calendar', 'uploads', 'products'],
            'commercial' => ['dashboard', 'crm', 'kanban', 'calendar', 'notes'],
            'viewer' => ['dashboard'],
        ];

        return in_array('*', $matrix[$role] ?? [], true) || in_array($permission, $matrix[$role] ?? [], true);
    }

    private function loginUser(array $user): void
    {
        SessionService::regenerate();
        $_SESSION['auth_user'] = [
            'id' => (int) ($user['id'] ?? $user['user_id']),
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        $_SESSION['_auth_login_at'] = time();
    }

    private function issueRememberToken(int $userId): void
    {
        $selector = bin2hex(random_bytes(12));
        $token = bin2hex(random_bytes(32));
        $expires = time() + self::REMEMBER_DAYS * 86400;

        $stmt = $this->pdo->prepare(
            'INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at)
             VALUES (:user_id, :selector, :token_hash, :expires_at)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'selector' => $selector,
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', $expires),
        ]);

        $this->setRememberCookie($selector . ':' . $token, $expires);
    }

    private function rotateRememberToken(int $tokenId, int $userId): void
    {
        $selector = bin2hex(random_bytes(12));
        $token = bin2hex(random_bytes(32));
        $expires = time() + self::REMEMBER_DAYS * 86400;

        $this->pdo->beginTransaction();

        try {
            $revoke = $this->pdo->prepare('UPDATE remember_tokens SET revoked_at = NOW(), last_used_at = NOW() WHERE id = :id');
            $revoke->execute(['id' => $tokenId]);
            $insert = $this->pdo->prepare(
                'INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at)
                 VALUES (:user_id, :selector, :token_hash, :expires_at)'
            );
            $insert->execute([
                'user_id' => $userId,
                'selector' => $selector,
                'token_hash' => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', $expires),
            ]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        $this->setRememberCookie($selector . ':' . $token, $expires);
    }

    private function revokeCurrentRememberToken(): void
    {
        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? '';

        if ($cookie && str_contains($cookie, ':')) {
            [$selector] = explode(':', $cookie, 2);

            if (preg_match('/^[a-f0-9]{24}$/', $selector)) {
                $stmt = $this->pdo->prepare('UPDATE remember_tokens SET revoked_at = NOW() WHERE selector = :selector');
                $stmt->execute(['selector' => $selector]);
            }
        }

        $this->clearRememberCookie();
    }

    private function setRememberCookie(string $value, int $expires): void
    {
        setcookie(self::REMEMBER_COOKIE, $value, [
            'expires' => $expires,
            'path' => '/admin',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function clearRememberCookie(): void
    {
        setcookie(self::REMEMBER_COOKIE, '', [
            'expires' => time() - 3600,
            'path' => '/admin',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function recordLogin(?int $userId, ?string $email, string $status, ?string $reason = null): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO login_history (user_id, email, status, ip_address, user_agent, failure_reason)
                 VALUES (:user_id, :email, :status, :ip_address, :user_agent, :failure_reason)'
            );
            $stmt->execute([
                'user_id' => $userId,
                'email' => $email,
                'status' => $status,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                'failure_reason' => $reason,
            ]);
        } catch (Throwable) {
            // Login nao pode depender da tabela de historico durante instalacao/migracao.
        }
    }
}
