<?php

final class ImageHistoryService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function createAiImageRecord(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO ai_images (
                source_upload_id, result_upload_id, client_id, lead_id, property_id,
                surface_id, service_id, model_name, prompt_text, status, analysis_json, error_message, created_by
            ) VALUES (
                :source_upload_id, :result_upload_id, :client_id, :lead_id, :property_id,
                :surface_id, :service_id, :model_name, :prompt_text, :status, :analysis_json, :error_message, :created_by
            )'
        );

        $stmt->execute([
            'source_upload_id' => $data['source_upload_id'],
            'result_upload_id' => $data['result_upload_id'] ?? null,
            'client_id' => $data['client_id'] ?? null,
            'lead_id' => $data['lead_id'] ?? null,
            'property_id' => $data['property_id'] ?? null,
            'surface_id' => $data['surface_id'] ?? null,
            'service_id' => $data['service_id'] ?? null,
            'model_name' => $data['model_name'] ?? null,
            'prompt_text' => $data['prompt_text'] ?? null,
            'status' => $data['status'] ?? 'completed',
            'analysis_json' => json_encode($data['analysis'] ?? [], JSON_UNESCAPED_UNICODE),
            'error_message' => $data['error_message'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function linkUpload(int $uploadId, array $relations, string $relationType): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO upload_links (
                upload_id, client_id, lead_id, property_id, surface_id, service_id, relation_type, caption, created_by
            ) VALUES (
                :upload_id, :client_id, :lead_id, :property_id, :surface_id, :service_id, :relation_type, :caption, :created_by
            )'
        );

        $stmt->execute([
            'upload_id' => $uploadId,
            'client_id' => $relations['client_id'] ?? null,
            'lead_id' => $relations['lead_id'] ?? null,
            'property_id' => $relations['property_id'] ?? null,
            'surface_id' => $relations['surface_id'] ?? null,
            'service_id' => $relations['service_id'] ?? null,
            'relation_type' => $relationType,
            'caption' => $relations['caption'] ?? null,
            'created_by' => $relations['created_by'] ?? null,
        ]);
    }
}
