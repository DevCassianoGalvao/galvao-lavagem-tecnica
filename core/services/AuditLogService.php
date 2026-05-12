<?php

final class AuditLogService
{
    private const CHANNEL_LABELS = [
        'admin' => 'Administrativo',
        'ai' => 'IA',
        'security' => 'Seguranca',
        'operational' => 'Operacional',
        'backup' => 'Backups',
        'app' => 'Sistema',
    ];

    public function __construct(private PDO $pdo)
    {
    }

    public function write(string $channel, string $level, string $action, string $message, array $context = [], ?int $userId = null, ?string $entityType = null, ?int $entityId = null): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO logs (
                user_id, level, channel, action, entity_type, entity_id, message,
                context_json, ip_address, user_agent, created_at
            ) VALUES (
                :user_id, :level, :channel, :action, :entity_type, :entity_id, :message,
                :context_json, :ip_address, :user_agent, NOW()
            )'
        );

        $stmt->execute([
            'user_id' => $userId,
            'level' => $level,
            'channel' => $channel,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'message' => $message,
            'context_json' => json_encode($context, JSON_UNESCAPED_UNICODE),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    }

    public function search(array $filters = [], int $limit = 120): array
    {
        $sql = 'SELECT l.*, u.name AS user_name, u.email AS user_email
                FROM logs l
                LEFT JOIN users u ON u.id = l.user_id
                WHERE 1 = 1';
        $params = [];

        if (($filters['channel'] ?? '') !== '') {
            $sql .= ' AND l.channel = :channel';
            $params['channel'] = $filters['channel'];
        }

        if (($filters['level'] ?? '') !== '') {
            $sql .= ' AND l.level = :level';
            $params['level'] = $filters['level'];
        }

        if (($filters['user_id'] ?? '') !== '') {
            $sql .= ' AND l.user_id = :user_id';
            $params['user_id'] = (int) $filters['user_id'];
        }

        if (($filters['date_from'] ?? '') !== '') {
            $sql .= ' AND l.created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (($filters['date_to'] ?? '') !== '') {
            $sql .= ' AND l.created_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        if (($filters['query'] ?? '') !== '') {
            $sql .= ' AND (l.action LIKE :query OR l.message LIKE :query OR l.context_json LIKE :query OR u.name LIKE :query OR u.email LIKE :query)';
            $params['query'] = '%' . $filters['query'] . '%';
        }

        $sql .= ' ORDER BY l.created_at DESC LIMIT :limit';
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function stats(): array
    {
        return [
            'total_today' => (int) $this->scalar('SELECT COUNT(*) FROM logs WHERE created_at >= CURDATE()'),
            'security_today' => (int) $this->scalar('SELECT COUNT(*) FROM logs WHERE channel = "security" AND created_at >= CURDATE()'),
            'ai_today' => (int) $this->scalar('SELECT COUNT(*) FROM logs WHERE channel = "ai" AND created_at >= CURDATE()'),
            'critical_week' => (int) $this->scalar('SELECT COUNT(*) FROM logs WHERE level IN ("error", "critical") AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'),
        ];
    }

    public function users(): array
    {
        return $this->pdo->query(
            'SELECT id, name, email
             FROM users
             WHERE deleted_at IS NULL
             ORDER BY name'
        )->fetchAll();
    }

    public static function channelLabels(): array
    {
        return self::CHANNEL_LABELS;
    }

    public static function channelLabel(string $channel): string
    {
        return self::CHANNEL_LABELS[$channel] ?? ucfirst($channel);
    }

    private function scalar(string $sql): mixed
    {
        try {
            return $this->pdo->query($sql)->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }
}
