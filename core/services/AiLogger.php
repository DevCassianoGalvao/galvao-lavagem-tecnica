<?php

final class AiLogger
{
    public function __construct(private PDO $pdo)
    {
    }

    public function info(string $action, string $message, array $context = []): void
    {
        $this->write('info', $action, $message, $context);
    }

    public function warning(string $action, string $message, array $context = []): void
    {
        $this->write('warning', $action, $message, $context);
    }

    public function error(string $action, string $message, array $context = []): void
    {
        $this->write('error', $action, $message, $context);
    }

    private function write(string $level, string $action, string $message, array $context): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO logs (user_id, level, channel, action, message, context_json, ip_address, user_agent, created_at)
                 VALUES (:user_id, :level, :channel, :action, :message, :context_json, :ip_address, :user_agent, NOW())'
            );

            $stmt->execute([
                'user_id' => $_SESSION['auth_user']['id'] ?? null,
                'level' => $level,
                'channel' => 'ai',
                'action' => $action,
                'message' => $message,
                'context_json' => json_encode($context, JSON_UNESCAPED_UNICODE),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ]);
        } catch (Throwable $exception) {
            $line = sprintf(
                "[%s] %s.%s %s %s\n",
                date('Y-m-d H:i:s'),
                $level,
                $action,
                $message,
                json_encode($context, JSON_UNESCAPED_UNICODE)
            );

            file_put_contents(LOG_PATH . '/ai.log', $line, FILE_APPEND);
        }
    }
}
