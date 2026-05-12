<?php

final class RecurrenceService
{
    private const DEFAULT_RETURN_MONTHS = 6;
    private const NOVA_FRIBURGO_RETURN_MONTHS = 6;

    public function __construct(
        private PDO $pdo,
        private SecurityLogger $logger
    ) {
    }

    public function completeService(int $serviceId, ?int $userId = null): array
    {
        $this->pdo->beginTransaction();

        try {
            $service = $this->serviceContext($serviceId);

            if (!$service) {
                throw new InvalidArgumentException('Servico nao encontrado.');
            }

            if ($service['status'] !== 'completed') {
                $stmt = $this->pdo->prepare(
                    'UPDATE services
                     SET status = "completed", completed_at = COALESCE(completed_at, NOW()), updated_by = :user_id
                     WHERE id = :service_id'
                );
                $stmt->execute([
                    'service_id' => $serviceId,
                    'user_id' => $userId,
                ]);
                $service = $this->serviceContext($serviceId);
            }

            $result = $this->schedulePreventiveReturn($service, $userId);
            $this->pdo->commit();

            return $result;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function schedulePreventiveReturn(array $service, ?int $userId = null): array
    {
        $existing = $this->existingPlan((int) $service['id']);

        if ($existing) {
            return [
                'created' => false,
                'recurrence_plan_id' => (int) $existing['id'],
                'due_at' => $existing['next_due_at'],
            ];
        }

        $months = $this->returnMonthsForCity((string) ($service['city'] ?? ''));
        $completedAt = new DateTimeImmutable($service['completed_at'] ?: 'now');
        $dueAt = $completedAt->modify('+' . $months . ' months')->setTime(9, 0);
        $title = 'Retorno preventivo - ' . $service['client_name'];
        $description = 'Previsao automatica baseada em recorrencia media de lodo para Nova Friburgo: ' . $months . ' meses.';

        $planId = $this->createRecurrencePlan($service, $dueAt, $months, $description, $userId);
        $followUpId = $this->createFollowUp($service, $title, $dueAt, $userId);
        $calendarEventId = $this->createCalendarEvent($service, $title, $dueAt, $description, $userId);
        $this->createNotification($service, $followUpId, $planId, $dueAt, $userId);
        $this->createTimelineNote($service, $dueAt, $months, $userId);
        $this->linkPlan($planId, $followUpId, $calendarEventId);

        $this->logger->log('info', 'recurrence_created', 'Recorrencia preventiva criada apos conclusao de servico.', [
            'service_id' => (int) $service['id'],
            'client_id' => (int) $service['client_id'],
            'due_at' => $dueAt->format('Y-m-d H:i:s'),
        ]);

        return [
            'created' => true,
            'recurrence_plan_id' => $planId,
            'follow_up_id' => $followUpId,
            'calendar_event_id' => $calendarEventId,
            'due_at' => $dueAt->format('Y-m-d H:i:s'),
        ];
    }

    public function upcoming(int $limit = 8): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT rp.id, rp.next_due_at, rp.reason, c.name AS client_name, p.neighborhood, p.city
             FROM recurrence_plans rp
             INNER JOIN clients c ON c.id = rp.client_id
             LEFT JOIN properties p ON p.id = rp.property_id
             WHERE rp.status = "active"
               AND rp.next_due_at >= NOW()
             ORDER BY rp.next_due_at ASC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function scheduleMissingForCompletedServices(?int $userId = null, int $limit = 50): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.id
             FROM services s
             LEFT JOIN recurrence_plans rp ON rp.source_service_id = s.id
             WHERE s.status = "completed"
               AND s.deleted_at IS NULL
               AND rp.id IS NULL
             ORDER BY s.completed_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $created = 0;

        foreach ($stmt->fetchAll() as $row) {
            $service = $this->serviceContext((int) $row['id']);

            if (!$service) {
                continue;
            }

            $result = $this->schedulePreventiveReturn($service, $userId);
            $created += $result['created'] ? 1 : 0;
        }

        return $created;
    }

    private function serviceContext(int $serviceId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.*, c.name AS client_name, c.phone, c.email,
                    p.city, p.neighborhood, p.address_line, p.address_number
             FROM services s
             INNER JOIN clients c ON c.id = s.client_id
             INNER JOIN properties p ON p.id = s.property_id
             WHERE s.id = :service_id
               AND s.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['service_id' => $serviceId]);
        $service = $stmt->fetch();

        return $service ?: null;
    }

    private function existingPlan(int $serviceId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, next_due_at FROM recurrence_plans WHERE source_service_id = :service_id LIMIT 1');
        $stmt->execute(['service_id' => $serviceId]);
        $plan = $stmt->fetch();

        return $plan ?: null;
    }

    private function returnMonthsForCity(string $city): int
    {
        return strtolower(trim($city)) === 'nova friburgo'
            ? self::NOVA_FRIBURGO_RETURN_MONTHS
            : self::DEFAULT_RETURN_MONTHS;
    }

    private function createRecurrencePlan(array $service, DateTimeImmutable $dueAt, int $months, string $reason, ?int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO recurrence_plans (
                client_id, property_id, lead_id, source_service_id, interval_months,
                next_due_at, status, reason, created_by
            ) VALUES (
                :client_id, :property_id, :lead_id, :source_service_id, :interval_months,
                :next_due_at, "active", :reason, :created_by
            )'
        );
        $stmt->execute([
            'client_id' => (int) $service['client_id'],
            'property_id' => (int) $service['property_id'],
            'lead_id' => $service['lead_id'] ? (int) $service['lead_id'] : null,
            'source_service_id' => (int) $service['id'],
            'interval_months' => $months,
            'next_due_at' => $dueAt->format('Y-m-d H:i:s'),
            'reason' => $reason,
            'created_by' => $userId,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function createFollowUp(array $service, string $title, DateTimeImmutable $dueAt, ?int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO follow_ups (lead_id, client_id, service_id, assigned_user_id, title, due_at, status)
             VALUES (:lead_id, :client_id, :service_id, :assigned_user_id, :title, :due_at, "pending")'
        );
        $stmt->execute([
            'lead_id' => $service['lead_id'] ? (int) $service['lead_id'] : null,
            'client_id' => (int) $service['client_id'],
            'service_id' => (int) $service['id'],
            'assigned_user_id' => $service['assigned_user_id'] ? (int) $service['assigned_user_id'] : $userId,
            'title' => $title,
            'due_at' => $dueAt->format('Y-m-d H:i:s'),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function createCalendarEvent(array $service, string $title, DateTimeImmutable $dueAt, string $description, ?int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO calendar_events (
                service_id, lead_id, client_id, assigned_user_id, title, description,
                event_type, status, starts_at, ends_at, location_text, created_by
            ) VALUES (
                :service_id, :lead_id, :client_id, :assigned_user_id, :title, :description,
                "follow_up", "pending", :starts_at, :ends_at, :location_text, :created_by
            )'
        );
        $stmt->execute([
            'service_id' => (int) $service['id'],
            'lead_id' => $service['lead_id'] ? (int) $service['lead_id'] : null,
            'client_id' => (int) $service['client_id'],
            'assigned_user_id' => $service['assigned_user_id'] ? (int) $service['assigned_user_id'] : $userId,
            'title' => $title,
            'description' => $description,
            'starts_at' => $dueAt->format('Y-m-d H:i:s'),
            'ends_at' => $dueAt->modify('+30 minutes')->format('Y-m-d H:i:s'),
            'location_text' => trim(($service['neighborhood'] ?? '') . ' - ' . ($service['city'] ?? ''), ' -'),
            'created_by' => $userId,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function createNotification(array $service, int $followUpId, int $planId, DateTimeImmutable $dueAt, ?int $userId): void
    {
        (new NotificationService($this->pdo, $this->logger))->create([
            'user_id' => $service['assigned_user_id'] ? (int) $service['assigned_user_id'] : $userId,
            'client_id' => (int) $service['client_id'],
            'lead_id' => $service['lead_id'] ? (int) $service['lead_id'] : null,
            'service_id' => (int) $service['id'],
            'follow_up_id' => $followUpId,
            'recurrence_plan_id' => $planId,
            'notification_type' => 'preventive_return',
            'priority' => 'normal',
            'title' => 'Cliente proximo do retorno preventivo',
            'body' => 'Preparar contato consultivo para ' . $service['client_name'] . '.',
            'action_url' => '?page=clientes',
            'channel' => 'in_app',
            'notify_at' => $dueAt->modify('-14 days')->format('Y-m-d H:i:s'),
        ]);
    }

    private function createTimelineNote(array $service, DateTimeImmutable $dueAt, int $months, ?int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO notes (client_id, lead_id, property_id, service_id, author_user_id, note_type, title, body)
             VALUES (:client_id, :lead_id, :property_id, :service_id, :author_user_id, "timeline", :title, :body)'
        );
        $stmt->execute([
            'client_id' => (int) $service['client_id'],
            'lead_id' => $service['lead_id'] ? (int) $service['lead_id'] : null,
            'property_id' => (int) $service['property_id'],
            'service_id' => (int) $service['id'],
            'author_user_id' => $userId,
            'title' => 'Recorrencia preventiva criada',
            'body' => 'Retorno previsto para ' . $dueAt->format('d/m/Y') . ' com base no ciclo medio de lodo de ' . $months . ' meses.',
        ]);
    }

    private function linkPlan(int $planId, int $followUpId, int $calendarEventId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE recurrence_plans
             SET follow_up_id = :follow_up_id, calendar_event_id = :calendar_event_id
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $planId,
            'follow_up_id' => $followUpId,
            'calendar_event_id' => $calendarEventId,
        ]);
    }
}
