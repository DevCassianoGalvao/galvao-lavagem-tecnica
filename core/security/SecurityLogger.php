<?php

final class SecurityLogger
{
    public function __construct(private ?PDO $pdo = null)
    {
    }

    public function log(string $level, string $action, string $message, array $context = []): void
    {
        $context += [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'session_id' => session_id() ?: null,
        ];

        if ($this->pdo instanceof PDO) {
            try {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO logs (user_id, level, channel, action, message, context_json, ip_address, user_agent, created_at)
                     VALUES (:user_id, :level, :channel, :action, :message, :context_json, :ip_address, :user_agent, NOW())'
                );
                $stmt->execute([
                    'user_id' => $_SESSION['auth_user']['id'] ?? null,
                    'level' => $level,
                    'channel' => 'security',
                    'action' => $action,
                    'message' => $message,
                    'context_json' => json_encode($context, JSON_UNESCAPED_UNICODE),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                ]);
                return;
            } catch (Throwable) {
                // File fallback keeps security observability even before DB setup.
            }
        }

        $line = sprintf("[%s] %s.%s %s %s\n", date('Y-m-d H:i:s'), $level, $action, $message, json_encode($context, JSON_UNESCAPED_UNICODE));
        file_put_contents((defined('ENV_LOG_PATH') ? ENV_LOG_PATH : LOG_PATH) . '/security.log', $line, FILE_APPEND);
    }
}
