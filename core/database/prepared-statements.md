# Prepared Statements

Use PDO com placeholders nomeados em toda operacao de banco.

## Criar lead vindo do quiz

```php
$stmt = $pdo->prepare(
    'INSERT INTO leads (
        pipeline_stage_id, name, email, phone, source, priority,
        area_size, approximate_area_m2, access_difficulty,
        has_elevated_height, cleaning_frequency, status, created_by
    ) VALUES (
        :pipeline_stage_id, :name, :email, :phone, :source, :priority,
        :area_size, :approximate_area_m2, :access_difficulty,
        :has_elevated_height, :cleaning_frequency, :status, :created_by
    )'
);

$stmt->execute([
    'pipeline_stage_id' => $stageId,
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'source' => 'quiz',
    'priority' => $priority,
    'area_size' => $areaSize,
    'approximate_area_m2' => $areaM2,
    'access_difficulty' => $accessDifficulty,
    'has_elevated_height' => $hasElevatedHeight,
    'cleaning_frequency' => $cleaningFrequency,
    'status' => 'new',
    'created_by' => $userId,
]);
```

## Registrar upload sem duplicar imagem

```php
$stmt = $pdo->prepare(
    'INSERT INTO uploads (
        uploaded_by, original_name, stored_name, storage_path, mime_type,
        extension, size_bytes, width_px, height_px, sha256_hash, image_role
    ) VALUES (
        :uploaded_by, :original_name, :stored_name, :storage_path, :mime_type,
        :extension, :size_bytes, :width_px, :height_px, :sha256_hash, :image_role
    )
    ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
);

$stmt->execute([
    'uploaded_by' => $userId,
    'original_name' => $originalName,
    'stored_name' => $storedName,
    'storage_path' => $storagePath,
    'mime_type' => $mimeType,
    'extension' => $extension,
    'size_bytes' => $sizeBytes,
    'width_px' => $width,
    'height_px' => $height,
    'sha256_hash' => $sha256,
    'image_role' => 'original',
]);

$uploadId = (int) $pdo->lastInsertId();
```

## Vincular imagem a uma entidade

```php
$stmt = $pdo->prepare(
    'INSERT INTO upload_links (
        upload_id, client_id, lead_id, property_id, surface_id,
        service_id, relation_type, caption, created_by
    ) VALUES (
        :upload_id, :client_id, :lead_id, :property_id, :surface_id,
        :service_id, :relation_type, :caption, :created_by
    )'
);
```
