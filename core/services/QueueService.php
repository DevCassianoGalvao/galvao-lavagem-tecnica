<?php

final class QueueService
{
    public const TYPES = ['ai_text', 'ai_visual', 'thumbnail', 'compression', 'notification'];
    public const STATUSES = ['pending', 'processing', 'completed', 'failed', 'canceled'];

    public function __construct(
        private PDO $pdo,
        private ?AuditLogService $audit = null
    ) {
    }

    public function enqueue(string $type, array $payload = [], int $priority = 5, ?string $availableAt = null, int $maxAttempts = 3): int
    {
        $type = $this->normalizeType($type);
        $stmt = $this->pdo->prepare(
            'INSERT INTO queue_jobs (type, status, priority, max_attempts, payload_json, available_at)
             VALUES (:type, "pending", :priority, :max_attempts, :payload_json, :available_at)'
        );
        $stmt->execute([
            'type' => $type,
            'priority' => max(1, min(10, $priority)),
            'max_attempts' => max(1, min(10, $maxAttempts)),
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'available_at' => $availableAt ?: date('Y-m-d H:i:s'),
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $this->log($id, 'info', 'queued', 'Job enfileirado.', ['type' => $type]);

        return $id;
    }

    public function enqueueAiText(int $leadId, bool $force = false, int $priority = 7): int
    {
        return $this->enqueue('ai_text', ['lead_id' => $leadId, 'force' => $force], $priority);
    }

    public function enqueueAiVisual(int $sourceUploadId, array $relations = [], int $priority = 6): int
    {
        return $this->enqueue('ai_visual', ['source_upload_id' => $sourceUploadId, 'relations' => $relations], $priority, null, 2);
    }

    public function enqueueImageOptimization(int $uploadId, bool $thumbnail = true, bool $compression = true): array
    {
        $jobs = [];

        if ($compression) {
            $jobs[] = $this->enqueue('compression', ['upload_id' => $uploadId], 4);
        }

        if ($thumbnail) {
            $jobs[] = $this->enqueue('thumbnail', ['upload_id' => $uploadId], 4);
        }

        return $jobs;
    }

    public function enqueueNotifications(int $priority = 3): int
    {
        return $this->enqueue('notification', ['scope' => 'operational_alerts'], $priority);
    }

    public function reserveNext(): ?array
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'SELECT *
                 FROM queue_jobs
                 WHERE status = "pending"
                   AND available_at <= NOW()
                   AND attempts < max_attempts
                 ORDER BY priority DESC, available_at ASC, id ASC
                 LIMIT 1
                 FOR UPDATE'
            );
            $stmt->execute();
            $job = $stmt->fetch();

            if (!$job) {
                $this->pdo->commit();
                return null;
            }

            $update = $this->pdo->prepare(
                'UPDATE queue_jobs
                 SET status = "processing", attempts = attempts + 1, reserved_at = NOW(), started_at = NOW(), updated_at = NOW()
                 WHERE id = :id'
            );
            $update->execute(['id' => (int) $job['id']]);
            $this->pdo->commit();

            $job['attempts'] = (int) $job['attempts'] + 1;
            $job['status'] = 'processing';
            $this->log((int) $job['id'], 'info', 'processing', 'Job reservado para processamento.');

            return $job;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function complete(int $jobId, array $result = []): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE queue_jobs
             SET status = "completed", result_json = :result_json, finished_at = NOW(), updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $jobId,
            'result_json' => json_encode($result, JSON_UNESCAPED_UNICODE),
        ]);

        $this->log($jobId, 'info', 'completed', 'Job concluido.', $result);
    }

    public function fail(int $jobId, string $error, int $attempts, int $maxAttempts): void
    {
        $willRetry = $attempts < $maxAttempts;
        $delaySeconds = min(3600, 60 * max(1, $attempts));

        if ($willRetry) {
            $stmt = $this->pdo->prepare(
                'UPDATE queue_jobs
                 SET status = "pending",
                     error_message = :error_message,
                     available_at = :available_at,
                     reserved_at = NULL,
                     started_at = NULL,
                     finished_at = NULL,
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                'id' => $jobId,
                'error_message' => $error,
                'available_at' => date('Y-m-d H:i:s', time() + $delaySeconds),
            ]);
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE queue_jobs
                 SET status = "failed",
                     error_message = :error_message,
                     finished_at = NOW(),
                     updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                'id' => $jobId,
                'error_message' => $error,
            ]);
        }

        $this->log($jobId, $willRetry ? 'warning' : 'error', $willRetry ? 'retry_scheduled' : 'failed', $error, [
            'attempts' => $attempts,
            'max_attempts' => $maxAttempts,
            'retry_in_seconds' => $willRetry ? $delaySeconds : null,
        ]);
    }

    public function retry(int $jobId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE queue_jobs
             SET status = "pending",
                 error_message = NULL,
                 available_at = NOW(),
                 reserved_at = NULL,
                 started_at = NULL,
                 finished_at = NULL,
                 updated_at = NOW()
             WHERE id = :id
               AND status IN ("failed", "canceled")'
        );
        $stmt->execute(['id' => $jobId]);

        $this->log($jobId, 'info', 'retry_manual', 'Job reenfileirado manualmente.');
    }

    public function cancel(int $jobId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE queue_jobs
             SET status = "canceled", finished_at = NOW(), updated_at = NOW()
             WHERE id = :id
               AND status IN ("pending", "failed")'
        );
        $stmt->execute(['id' => $jobId]);

        $this->log($jobId, 'warning', 'canceled', 'Job cancelado manualmente.');
    }

    public function stats(): array
    {
        $rows = $this->pdo->query(
            'SELECT type, status, COUNT(*) AS total
             FROM queue_jobs
             GROUP BY type, status'
        )->fetchAll();

        $stats = [];

        foreach (self::TYPES as $type) {
            $stats[$type] = array_fill_keys(self::STATUSES, 0);
        }

        foreach ($rows as $row) {
            $stats[$row['type']][$row['status']] = (int) $row['total'];
        }

        return $stats;
    }

    public function list(array $filters = [], int $limit = 120): array
    {
        $sql = 'SELECT q.*, (
                    SELECT message
                    FROM queue_job_logs l
                    WHERE l.job_id = q.id
                    ORDER BY l.created_at DESC, l.id DESC
                    LIMIT 1
                ) AS last_log
                FROM queue_jobs q
                WHERE 1 = 1';
        $params = [];

        if (($filters['type'] ?? '') !== '') {
            $sql .= ' AND q.type = :type';
            $params['type'] = $filters['type'];
        }

        if (($filters['status'] ?? '') !== '') {
            $sql .= ' AND q.status = :status';
            $params['status'] = $filters['status'];
        }

        $sql .= ' ORDER BY FIELD(q.status, "processing", "pending", "failed", "completed", "canceled"), q.priority DESC, q.created_at DESC LIMIT :limit';
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function log(int $jobId, string $level, string $event, string $message, array $context = []): void
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO queue_job_logs (job_id, level, event, message, context_json, created_at)
                 VALUES (:job_id, :level, :event, :message, :context_json, NOW())'
            );
            $stmt->execute([
                'job_id' => $jobId,
                'level' => $level,
                'event' => $event,
                'message' => $message,
                'context_json' => json_encode($context, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (Throwable) {
            // A fila continua operacional mesmo antes da tabela de logs ser migrada.
        }

        try {
            $this->audit?->write('operational', $level, 'queue_' . $event, $message, ['job_id' => $jobId] + $context, null, 'queue_job', $jobId);
        } catch (Throwable) {
            // Sem efeito colateral no processamento.
        }
    }

    private function normalizeType(string $type): string
    {
        $type = trim($type);

        if (!in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('Tipo de job invalido.');
        }

        return $type;
    }
}
