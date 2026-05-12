<?php

final class UploadsController extends ApiController
{
    public function index(ApiRequest $request): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, original_name, storage_path, mime_type, size_bytes, width_px, height_px, image_role, status, created_at
             FROM uploads
             WHERE deleted_at IS NULL
             ORDER BY created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $this->limit($request), PDO::PARAM_INT);
        $stmt->execute();

        ApiResponse::success(['uploads' => $stmt->fetchAll()]);
    }

    public function store(ApiRequest $request): void
    {
        if (!isset($request->files['file'])) {
            ApiResponse::validation(['file' => ['Arquivo obrigatorio.']]);
        }

        $upload = (new ImageUploadService($this->pdo, auth_id(), new UploadSecurityService($this->logger)))
            ->storeUploadedFile($request->files['file']);

        (new QueueService($this->pdo, $this->audit))->enqueueImageOptimization((int) $upload['upload_id']);
        $this->audit->write('admin', 'info', 'api_upload_created', 'Upload criado via API interna.', [], auth_id(), 'upload', (int) $upload['upload_id']);

        ApiResponse::success(['upload' => $upload], 'Upload recebido.', 201);
    }
}
