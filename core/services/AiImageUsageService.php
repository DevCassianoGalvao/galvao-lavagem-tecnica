<?php

final class AiImageUsageService
{
    public function __construct(
        private PDO $pdo,
        private array $config
    ) {
    }

    public function assertAllowed(?int $leadId, string $ipAddress, string $sessionId): void
    {
        $limit = $leadId ? (int) ($this->config['ai_image_post_quiz_limit'] ?? 3) : (int) ($this->config['ai_image_pre_quiz_limit'] ?? 1);
        $cooldown = (int) ($this->config['ai_image_cooldown_seconds'] ?? 45);

        $stmt = $this->pdo->prepare(
            'SELECT created_at
             FROM ai_image_usages
             WHERE (session_id = :session_id OR ip_address = :ip_address OR (:lead_id IS NOT NULL AND lead_id = :lead_id))
             ORDER BY created_at DESC'
        );
        $stmt->execute([
            'session_id' => $sessionId,
            'ip_address' => $ipAddress,
            'lead_id' => $leadId,
        ]);
        $rows = $stmt->fetchAll();

        if ($rows) {
            $last = strtotime($rows[0]['created_at']);

            if ($last !== false && time() - $last < $cooldown) {
                throw new RuntimeException('cooldown');
            }
        }

        if (count($rows) >= $limit) {
            throw new RuntimeException('limit');
        }
    }

    public function register(?int $leadId, string $ipAddress, string $sessionId, int $sourceUploadId, ?int $resultUploadId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO ai_image_usages (lead_id, ip_address, session_id, source_upload_id, result_upload_id, created_at)
             VALUES (:lead_id, :ip_address, :session_id, :source_upload_id, :result_upload_id, NOW())'
        );

        $stmt->execute([
            'lead_id' => $leadId,
            'ip_address' => $ipAddress,
            'session_id' => $sessionId,
            'source_upload_id' => $sourceUploadId,
            'result_upload_id' => $resultUploadId,
        ]);
    }
}
