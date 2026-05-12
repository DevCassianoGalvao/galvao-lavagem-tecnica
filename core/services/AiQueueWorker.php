<?php

final class AiQueueWorker
{
    public function __construct(
        private PDO $pdo,
        private array $config
    ) {
    }

    public function runOnce(): ?array
    {
        $jobs = new AiJobService($this->pdo);
        $job = $jobs->nextQueuedJob();

        if (!$job) {
            return null;
        }

        $jobs->markProcessing((int) $job['id']);

        try {
            $payload = json_decode($job['payload_json'] ?? '{}', true) ?: [];
            $service = new AiTextService($this->pdo, $this->config);
            $result = $service->analyzeLead((int) ($payload['lead_id'] ?? 0), (bool) ($payload['force'] ?? false));
            $jobs->markCompleted((int) $job['id']);

            return $result + ['job_id' => (int) $job['id']];
        } catch (Throwable $exception) {
            $jobs->markFailed((int) $job['id'], $exception->getMessage());
            throw $exception;
        }
    }
}
