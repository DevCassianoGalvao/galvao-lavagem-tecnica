<?php

final class AiVisualCacheService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findForSession(string $sourceHash, string $sessionId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ai.id AS history_id,
                    usage.source_upload_id,
                    usage.result_upload_id,
                    result.storage_path,
                    result.mime_type
             FROM ai_image_usages usage
             INNER JOIN uploads source ON source.id = usage.source_upload_id
             INNER JOIN uploads result ON result.id = usage.result_upload_id
             LEFT JOIN ai_images ai ON ai.result_upload_id = usage.result_upload_id
             WHERE usage.session_id = :session_id
               AND source.sha256_hash = :source_hash
               AND result.status = "active"
               AND result.storage_path IS NOT NULL
             ORDER BY usage.created_at DESC
             LIMIT 1'
        );

        $stmt->execute([
            'session_id' => $sessionId,
            'source_hash' => $sourceHash,
        ]);

        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $path = STORAGE_PATH . '/' . ltrim((string) $row['storage_path'], '/');

        if (!is_file($path)) {
            return null;
        }

        $mimeType = (string) ($row['mime_type'] ?: 'image/png');
        $bytes = file_get_contents($path);

        if ($bytes === false) {
            return null;
        }

        return [
            'history_id' => isset($row['history_id']) ? (int) $row['history_id'] : null,
            'source_upload_id' => (int) $row['source_upload_id'],
            'result_upload_id' => (int) $row['result_upload_id'],
            'result_data_url' => 'data:' . $mimeType . ';base64,' . base64_encode($bytes),
            'cached' => true,
        ];
    }
}
