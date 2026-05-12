<?php

final class QueueWorker
{
    public function __construct(
        private PDO $pdo,
        private array $config,
        private QueueService $queue
    ) {
    }

    public function run(int $limit = 5): array
    {
        $processed = [];

        for ($i = 0; $i < $limit; $i++) {
            $job = $this->queue->reserveNext();

            if (!$job) {
                break;
            }

            $processed[] = $this->process($job);
        }

        return $processed;
    }

    public function process(array $job): array
    {
        $jobId = (int) $job['id'];
        $payload = json_decode($job['payload_json'] ?? '{}', true) ?: [];

        try {
            $result = match ($job['type']) {
                'ai_text' => $this->processAiText($payload),
                'ai_visual' => $this->processAiVisual($payload),
                'thumbnail' => $this->processThumbnail($payload),
                'compression' => $this->processCompression($payload),
                'notification' => $this->processNotifications(),
                default => throw new InvalidArgumentException('Tipo de job sem worker.'),
            };

            $this->queue->complete($jobId, $result);

            return ['job_id' => $jobId, 'status' => 'completed', 'result' => $result];
        } catch (Throwable $exception) {
            $this->queue->fail($jobId, $exception->getMessage(), (int) $job['attempts'], (int) $job['max_attempts']);

            return ['job_id' => $jobId, 'status' => 'failed', 'error' => $exception->getMessage()];
        }
    }

    private function processAiText(array $payload): array
    {
        $leadId = (int) ($payload['lead_id'] ?? 0);

        if ($leadId <= 0) {
            throw new InvalidArgumentException('Lead invalido para IA textual.');
        }

        return (new AiTextService($this->pdo, $this->config))->analyzeLead($leadId, (bool) ($payload['force'] ?? false));
    }

    private function processAiVisual(array $payload): array
    {
        $uploadId = (int) ($payload['source_upload_id'] ?? 0);
        $source = $this->uploadById($uploadId);

        if (!$source) {
            throw new InvalidArgumentException('Upload fonte nao encontrado para IA visual.');
        }

        $path = STORAGE_PATH . '/' . ltrim((string) $source['storage_path'], '/');

        $result = (new AiVisualService($this->pdo, $this->config, new AiLogger($this->pdo)))->simulateRevitalization([
            'upload_id' => $uploadId,
            'path' => $path,
            'relative_path' => $source['storage_path'],
            'mime_type' => $source['mime_type'],
            'hash' => $source['sha256_hash'],
        ], is_array($payload['relations'] ?? null) ? $payload['relations'] : []);

        if (isset($payload['lead_id'], $payload['ip_address'], $payload['session_id'], $result['result_upload_id'])) {
            (new AiImageUsageService($this->pdo, $this->config))->register(
                (int) $payload['lead_id'] ?: null,
                (string) $payload['ip_address'],
                (string) $payload['session_id'],
                $uploadId,
                (int) $result['result_upload_id']
            );
        }

        return $result;
    }

    private function processThumbnail(array $payload): array
    {
        $uploadId = (int) ($payload['upload_id'] ?? 0);
        $upload = $this->uploadById($uploadId);

        if (!$upload) {
            throw new InvalidArgumentException('Upload nao encontrado para thumbnail.');
        }

        $path = STORAGE_PATH . '/' . ltrim((string) $upload['storage_path'], '/');
        $thumbnail = (new ImageOptimizationService())->thumbnail($path, (string) $upload['mime_type'], (string) $upload['sha256_hash']);

        if (!$thumbnail) {
            throw new RuntimeException('Nao foi possivel gerar thumbnail.');
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO uploads (
                uploaded_by, original_name, stored_name, storage_path, mime_type,
                extension, size_bytes, width_px, height_px, sha256_hash, image_role
            ) VALUES (
                :uploaded_by, :original_name, :stored_name, :storage_path, :mime_type,
                :extension, :size_bytes, :width_px, :height_px, :sha256_hash, "thumbnail"
            )
            ON DUPLICATE KEY UPDATE updated_at = NOW()'
        );
        $stmt->execute([
            'uploaded_by' => $upload['uploaded_by'] ?? null,
            'original_name' => $upload['original_name'] ?? basename($thumbnail['relative_path']),
            'stored_name' => basename($thumbnail['relative_path']),
            'storage_path' => $thumbnail['relative_path'],
            'mime_type' => $thumbnail['mime_type'],
            'extension' => $thumbnail['extension'],
            'size_bytes' => $thumbnail['size'],
            'width_px' => $thumbnail['width'],
            'height_px' => $thumbnail['height'],
            'sha256_hash' => $thumbnail['hash'],
        ]);

        return ['thumbnail_path' => $thumbnail['relative_path']];
    }

    private function processCompression(array $payload): array
    {
        $uploadId = (int) ($payload['upload_id'] ?? 0);
        $upload = $this->uploadById($uploadId);

        if (!$upload) {
            throw new InvalidArgumentException('Upload nao encontrado para compressao.');
        }

        $path = STORAGE_PATH . '/' . ltrim((string) $upload['storage_path'], '/');
        $before = is_file($path) ? filesize($path) : 0;
        (new ImageOptimizationService())->optimizeOriginal($path, (string) $upload['mime_type']);
        $after = is_file($path) ? filesize($path) : 0;

        $stmt = $this->pdo->prepare(
            'UPDATE uploads
             SET size_bytes = :size_bytes, width_px = :width_px, height_px = :height_px, updated_at = NOW()
             WHERE id = :id'
        );
        [$width, $height] = getimagesize($path) ?: [null, null];
        $stmt->execute([
            'id' => $uploadId,
            'size_bytes' => $after,
            'width_px' => $width,
            'height_px' => $height,
        ]);

        return ['before_bytes' => $before, 'after_bytes' => $after];
    }

    private function processNotifications(): array
    {
        $created = (new NotificationService($this->pdo, new SecurityLogger($this->pdo)))->generateOperationalAlerts();

        return ['created' => $created];
    }

    private function uploadById(int $uploadId): ?array
    {
        if ($uploadId <= 0) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM uploads
             WHERE id = :id
               AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['id' => $uploadId]);
        $upload = $stmt->fetch();

        return $upload ?: null;
    }
}
