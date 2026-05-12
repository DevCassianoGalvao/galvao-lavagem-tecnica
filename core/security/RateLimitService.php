<?php

final class RateLimitService
{
    public function __construct(
        private ?PDO $pdo = null,
        private ?SecurityLogger $logger = null
    ) {
    }

    public function hit(string $action, int $maxAttempts, int $windowSeconds, ?string $identity = null): bool
    {
        $identity ??= $this->identity();
        $key = hash('sha256', $action . '|' . $identity);
        $now = time();
        $bucket = $_SESSION['_rate_limits'][$key] ?? ['count' => 0, 'reset_at' => $now + $windowSeconds];

        if ($now > (int) $bucket['reset_at']) {
            $bucket = ['count' => 0, 'reset_at' => $now + $windowSeconds];
        }

        $bucket['count']++;
        $_SESSION['_rate_limits'][$key] = $bucket;
        $allowed = (int) $bucket['count'] <= $maxAttempts;

        if ($this->pdo instanceof PDO) {
            $this->persist($action, $identity, $bucket, $allowed);
        }

        if (!$allowed) {
            $this->logger?->log('warning', 'rate_limited', 'Rate limit excedido.', [
                'action' => $action,
                'identity_hash' => hash('sha256', $identity),
            ]);
        }

        return $allowed;
    }

    public function requireAllowed(string $action, int $maxAttempts, int $windowSeconds, ?string $identity = null): void
    {
        if ($this->hit($action, $maxAttempts, $windowSeconds, $identity)) {
            return;
        }

        http_response_code(429);
        exit('Muitas tentativas. Aguarde alguns instantes.');
    }

    private function identity(): string
    {
        return ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0') . '|' . session_id();
    }

    private function persist(string $action, string $identity, array $bucket, bool $allowed): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO rate_limits (rate_key, action, identity_hash, attempts, reset_at, last_attempt_at)
                 VALUES (:rate_key, :action, :identity_hash, :attempts_insert, FROM_UNIXTIME(:reset_at_insert), NOW())
                 ON DUPLICATE KEY UPDATE attempts = :attempts_update, reset_at = FROM_UNIXTIME(:reset_at_update), last_attempt_at = NOW()'
            );
            $stmt->execute([
                'rate_key' => hash('sha256', $action . '|' . $identity),
                'action' => $action,
                'identity_hash' => hash('sha256', $identity),
                'attempts_insert' => (int) $bucket['count'],
                'reset_at_insert' => (int) $bucket['reset_at'],
                'attempts_update' => (int) $bucket['count'],
                'reset_at_update' => (int) $bucket['reset_at'],
            ]);
        } catch (Throwable) {
            // Session bucket remains active even if DB table is not migrated yet.
        }
    }
}
