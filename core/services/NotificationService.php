<?php

final class NotificationService
{
    public function __construct(
        private PDO $pdo,
        private ?SecurityLogger $logger = null
    ) {
    }

    public function unreadCount(?int $userId = null): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM internal_notifications
             WHERE status IN ("pending", "sent")
               AND notify_at <= NOW()
               AND (user_id = :user_id OR user_id IS NULL)'
        );
        $stmt->execute(['user_id' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    public function latest(?int $userId = null, int $limit = 8): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, notification_type, priority, title, body, action_url, channel,
                    status, notify_at, created_at
             FROM internal_notifications
             WHERE notify_at <= NOW()
               AND status <> "dismissed"
               AND (user_id = :user_id OR user_id IS NULL)
             ORDER BY FIELD(status, "pending", "sent", "read", "failed"),
                      FIELD(priority, "urgent", "high", "normal", "low"),
                      notify_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue('user_id', $userId, $userId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function timeline(?int $userId = null, int $limit = 60): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT n.*, c.name AS client_name, l.name AS lead_name, s.title AS service_title
             FROM internal_notifications n
             LEFT JOIN clients c ON c.id = n.client_id
             LEFT JOIN leads l ON l.id = n.lead_id
             LEFT JOIN services s ON s.id = n.service_id
             WHERE (n.user_id = :user_id OR n.user_id IS NULL)
             ORDER BY n.notify_at DESC, n.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue('user_id', $userId, $userId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO internal_notifications (
                user_id, client_id, lead_id, service_id, calendar_event_id, follow_up_id, recurrence_plan_id,
                notification_type, priority, title, body, action_url, channel, status, notify_at
            ) VALUES (
                :user_id, :client_id, :lead_id, :service_id, :calendar_event_id, :follow_up_id, :recurrence_plan_id,
                :notification_type, :priority, :title, :body, :action_url, :channel, "pending", :notify_at
            )'
        );
        $stmt->execute([
            'user_id' => $data['user_id'] ?? null,
            'client_id' => $data['client_id'] ?? null,
            'lead_id' => $data['lead_id'] ?? null,
            'service_id' => $data['service_id'] ?? null,
            'calendar_event_id' => $data['calendar_event_id'] ?? null,
            'follow_up_id' => $data['follow_up_id'] ?? null,
            'recurrence_plan_id' => $data['recurrence_plan_id'] ?? null,
            'notification_type' => $data['notification_type'] ?? 'system',
            'priority' => $data['priority'] ?? 'normal',
            'title' => clean_text($data['title'] ?? 'Notificacao interna'),
            'body' => clean_text($data['body'] ?? ''),
            'action_url' => clean_text($data['action_url'] ?? ''),
            'channel' => $data['channel'] ?? 'in_app',
            'notify_at' => $data['notify_at'] ?? date('Y-m-d H:i:s'),
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $this->logger?->log('info', 'notification_created', 'Notificacao interna criada.', [
            'notification_id' => $id,
            'type' => $data['notification_type'] ?? 'system',
        ]);

        return $id;
    }

    public function markRead(int $notificationId, ?int $userId = null): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE internal_notifications
             SET status = "read", read_at = COALESCE(read_at, NOW())
             WHERE id = :id
               AND (user_id = :user_id OR user_id IS NULL)'
        );
        $stmt->execute([
            'id' => $notificationId,
            'user_id' => $userId,
        ]);
    }

    public function markAllRead(?int $userId = null): int
    {
        $stmt = $this->pdo->prepare(
            'UPDATE internal_notifications
             SET status = "read", read_at = COALESCE(read_at, NOW())
             WHERE status IN ("pending", "sent")
               AND (user_id = :user_id OR user_id IS NULL)'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->rowCount();
    }

    public function dismiss(int $notificationId, ?int $userId = null): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE internal_notifications
             SET status = "dismissed"
             WHERE id = :id
               AND (user_id = :user_id OR user_id IS NULL)'
        );
        $stmt->execute([
            'id' => $notificationId,
            'user_id' => $userId,
        ]);
    }

    public function generateOperationalAlerts(): int
    {
        $created = 0;
        $created += $this->generateFollowUpAlerts();
        $created += $this->generateEventAlerts();
        $created += $this->generateStalledLeadAlerts();
        $created += $this->generateProposalAlerts();
        $created += $this->generateNewLeadAlerts();

        return $created;
    }

    private function generateFollowUpAlerts(): int
    {
        $rows = $this->pdo->query(
            'SELECT f.id, f.client_id, f.lead_id, f.assigned_user_id, f.title, f.due_at, c.name AS client_name
             FROM follow_ups f
             LEFT JOIN clients c ON c.id = f.client_id
             WHERE f.status = "pending"
               AND f.due_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)'
        )->fetchAll();

        $created = 0;

        foreach ($rows as $row) {
            if ($this->exists('follow_up', 'follow_up_id', (int) $row['id'])) {
                continue;
            }

            $this->create([
                'user_id' => $row['assigned_user_id'] ? (int) $row['assigned_user_id'] : null,
                'client_id' => $row['client_id'] ? (int) $row['client_id'] : null,
                'lead_id' => $row['lead_id'] ? (int) $row['lead_id'] : null,
                'follow_up_id' => (int) $row['id'],
                'notification_type' => 'follow_up',
                'priority' => 'high',
                'title' => 'Follow-up proximo',
                'body' => ($row['client_name'] ?: 'Cliente') . ': ' . $row['title'],
                'action_url' => '?page=dashboard',
                'notify_at' => date('Y-m-d H:i:s'),
            ]);
            $created++;
        }

        return $created;
    }

    private function generateEventAlerts(): int
    {
        $rows = $this->pdo->query(
            'SELECT id, client_id, lead_id, service_id, assigned_user_id, title, starts_at
             FROM calendar_events
             WHERE deleted_at IS NULL
               AND status IN ("pending", "confirmed")
               AND starts_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR)'
        )->fetchAll();

        $created = 0;

        foreach ($rows as $row) {
            if ($this->exists('scheduled_event', 'calendar_event_id', (int) $row['id'])) {
                continue;
            }

            $this->create([
                'user_id' => $row['assigned_user_id'] ? (int) $row['assigned_user_id'] : null,
                'client_id' => $row['client_id'] ? (int) $row['client_id'] : null,
                'lead_id' => $row['lead_id'] ? (int) $row['lead_id'] : null,
                'service_id' => $row['service_id'] ? (int) $row['service_id'] : null,
                'calendar_event_id' => (int) $row['id'],
                'notification_type' => 'scheduled_event',
                'priority' => 'normal',
                'title' => 'Evento agendado proximo',
                'body' => $row['title'],
                'action_url' => '?page=agenda',
                'notify_at' => date('Y-m-d H:i:s'),
            ]);
            $created++;
        }

        return $created;
    }

    private function generateStalledLeadAlerts(): int
    {
        $rows = $this->pdo->query(
            'SELECT id, assigned_user_id, name, status, updated_at
             FROM leads
             WHERE deleted_at IS NULL
               AND status IN ("new", "qualified", "proposal")
               AND updated_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
             LIMIT 30'
        )->fetchAll();

        $created = 0;

        foreach ($rows as $row) {
            if ($this->exists('stalled_lead', 'lead_id', (int) $row['id'])) {
                continue;
            }

            $this->create([
                'user_id' => $row['assigned_user_id'] ? (int) $row['assigned_user_id'] : null,
                'lead_id' => (int) $row['id'],
                'notification_type' => 'stalled_lead',
                'priority' => 'high',
                'title' => 'Lead parado no pipeline',
                'body' => $row['name'] . ' esta sem movimentacao recente.',
                'action_url' => '?page=kanban',
                'notify_at' => date('Y-m-d H:i:s'),
            ]);
            $created++;
        }

        return $created;
    }

    private function generateProposalAlerts(): int
    {
        $rows = $this->pdo->query(
            'SELECT id, assigned_user_id, name, updated_at
             FROM leads
             WHERE deleted_at IS NULL
               AND status = "proposal"
               AND updated_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)
             LIMIT 30'
        )->fetchAll();

        $created = 0;

        foreach ($rows as $row) {
            if ($this->exists('proposal', 'lead_id', (int) $row['id'])) {
                continue;
            }

            $this->create([
                'user_id' => $row['assigned_user_id'] ? (int) $row['assigned_user_id'] : null,
                'lead_id' => (int) $row['id'],
                'notification_type' => 'proposal',
                'priority' => 'normal',
                'title' => 'Nova proposta enviada',
                'body' => 'Acompanhar retorno de ' . $row['name'] . '.',
                'action_url' => '?page=kanban',
                'notify_at' => date('Y-m-d H:i:s'),
            ]);
            $created++;
        }

        return $created;
    }

    private function generateNewLeadAlerts(): int
    {
        $rows = $this->pdo->query(
            'SELECT id, assigned_user_id, name, created_at
             FROM leads
             WHERE deleted_at IS NULL
               AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             LIMIT 40'
        )->fetchAll();

        $created = 0;

        foreach ($rows as $row) {
            if ($this->exists('new_lead', 'lead_id', (int) $row['id'])) {
                continue;
            }

            $this->create([
                'user_id' => $row['assigned_user_id'] ? (int) $row['assigned_user_id'] : null,
                'lead_id' => (int) $row['id'],
                'notification_type' => 'new_lead',
                'priority' => 'high',
                'title' => 'Novo lead recebido',
                'body' => $row['name'] . ' enviou um diagnostico tecnico.',
                'action_url' => '?page=leads',
                'notify_at' => date('Y-m-d H:i:s'),
            ]);
            $created++;
        }

        return $created;
    }

    private function exists(string $type, string $column, int $id, ?int $fallbackId = null): bool
    {
        if ($id <= 0 && $fallbackId !== null) {
            $id = $fallbackId;
        }

        if ($id <= 0) {
            return false;
        }

        $allowedColumns = ['follow_up_id', 'service_id', 'calendar_event_id', 'lead_id', 'recurrence_plan_id'];

        if (!in_array($column, $allowedColumns, true)) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'SELECT id
             FROM internal_notifications
             WHERE notification_type = :type
               AND ' . $column . ' = :id
             LIMIT 1'
        );
        $stmt->execute([
            'type' => $type,
            'id' => $id,
        ]);

        return (bool) $stmt->fetchColumn();
    }
}
