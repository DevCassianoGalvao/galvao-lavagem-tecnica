<?php

final class AiJobService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function enqueueLeadAnalysis(int $leadId, int $priority = 5): int
    {
        $payload = json_encode(['lead_id' => $leadId, 'force' => false], JSON_UNESCAPED_UNICODE);

        $stmt = $this->pdo->prepare(
            'INSERT INTO ai_jobs (job_type, entity_type, entity_id, payload_json, priority, status, available_at)
             VALUES (:job_type, :entity_type, :entity_id, :payload_json, :priority, :status, NOW())'
        );
        $stmt->execute([
            'job_type' => 'text_analysis',
            'entity_type' => 'lead',
            'entity_id' => $leadId,
            'payload_json' => $payload,
            'priority' => $priority,
            'status' => 'queued',
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function nextQueuedJob(): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM ai_jobs
             WHERE status = :status
               AND available_at <= NOW()
             ORDER BY priority DESC, available_at ASC, id ASC
             LIMIT 1'
        );
        $stmt->execute(['status' => 'queued']);
        $job = $stmt->fetch();

        return $job ?: null;
    }

    public function markProcessing(int $jobId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE ai_jobs
             SET status = :status, attempts = attempts + 1, started_at = NOW(), updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute(['status' => 'processing', 'id' => $jobId]);
    }

    public function markCompleted(int $jobId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE ai_jobs
             SET status = :status, finished_at = NOW(), updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute(['status' => 'completed', 'id' => $jobId]);
    }

    public function markFailed(int $jobId, string $error): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE ai_jobs
             SET status = :status, error_message = :error_message, finished_at = NOW(), updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'status' => 'failed',
            'error_message' => $error,
            'id' => $jobId,
        ]);
    }
}
