<?php

final class ImageUploadService
{
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(
        private PDO $pdo,
        private ?int $userId = null,
        private ?UploadSecurityService $security = null
    ) {
    }

    public function storeUploadedFile(array $file, string $directory = 'ai-images/originals'): array
    {
        $security = $this->security ?? new UploadSecurityService();
        $safe = $security->validateImageUpload($file);
        $directory = $security->safeDirectory($directory);
        $tmpPath = (string) $file['tmp_name'];
        $mimeType = $safe['mime_type'];

        $hash = hash_file('sha256', $tmpPath);
        $extension = $safe['extension'];
        $storedName = $hash . '.' . $extension;
        $relativePath = trim($directory, '/') . '/' . $storedName;
        $absoluteDir = STORAGE_PATH . '/' . trim($directory, '/');
        $absolutePath = STORAGE_PATH . '/' . $relativePath;

        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0775, true);
        }

        if (!file_exists($absolutePath)) {
            if (!move_uploaded_file($tmpPath, $absolutePath)) {
                throw new RuntimeException('Nao foi possivel salvar a imagem.');
            }
        }

        $optimizer = new ImageOptimizationService();
        $optimizer->optimizeOriginal($absolutePath, $mimeType);
        $optimizedHash = hash_file('sha256', $absolutePath);

        if ($optimizedHash !== $hash) {
            $hash = $optimizedHash;
            $storedName = $hash . '.' . $extension;
            $relativePath = trim($directory, '/') . '/' . $storedName;
            $newAbsolutePath = STORAGE_PATH . '/' . $relativePath;

            if (!file_exists($newAbsolutePath)) {
                rename($absolutePath, $newAbsolutePath);
            } elseif ($absolutePath !== $newAbsolutePath && is_file($absolutePath)) {
                unlink($absolutePath);
            }

            $absolutePath = $newAbsolutePath;
        }

        [$width, $height] = getimagesize($absolutePath) ?: [null, null];

        $stmt = $this->pdo->prepare(
            'INSERT INTO uploads (
                uploaded_by, original_name, stored_name, storage_path, mime_type,
                extension, size_bytes, width_px, height_px, sha256_hash, image_role
            ) VALUES (
                :uploaded_by, :original_name, :stored_name, :storage_path, :mime_type,
                :extension, :size_bytes, :width_px, :height_px, :sha256_hash, :image_role
            )
            ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), updated_at = NOW()'
        );

        $stmt->execute([
            'uploaded_by' => $this->userId,
            'original_name' => clean_text($file['name'] ?? $storedName),
            'stored_name' => $storedName,
            'storage_path' => $relativePath,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'size_bytes' => filesize($absolutePath),
            'width_px' => $width,
            'height_px' => $height,
            'sha256_hash' => $hash,
            'image_role' => 'original',
        ]);

        $uploadId = (int) $this->pdo->lastInsertId();
        $this->storeThumbnail($optimizer, $absolutePath, $mimeType, $hash, $file['name'] ?? $storedName);

        return [
            'upload_id' => $uploadId,
            'path' => $absolutePath,
            'relative_path' => $relativePath,
            'mime_type' => $mimeType,
            'hash' => $hash,
        ];
    }

    public function storeBinary(string $bytes, string $mimeType, string $directory, string $role): array
    {
        $hash = hash('sha256', $bytes);
        $extension = $this->extensionFromMime($mimeType);
        $storedName = $hash . '.' . $extension;
        $relativePath = trim($directory, '/') . '/' . $storedName;
        $absoluteDir = STORAGE_PATH . '/' . trim($directory, '/');
        $absolutePath = STORAGE_PATH . '/' . $relativePath;

        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0775, true);
        }

        if (!file_exists($absolutePath)) {
            file_put_contents($absolutePath, $bytes);
        }

        $optimizer = new ImageOptimizationService();
        $optimizer->optimizeOriginal($absolutePath, $mimeType);
        $optimizedHash = hash_file('sha256', $absolutePath);

        if ($optimizedHash !== $hash) {
            $hash = $optimizedHash;
            $storedName = $hash . '.' . $extension;
            $relativePath = trim($directory, '/') . '/' . $storedName;
            $newAbsolutePath = STORAGE_PATH . '/' . $relativePath;

            if (!file_exists($newAbsolutePath)) {
                rename($absolutePath, $newAbsolutePath);
            } elseif ($absolutePath !== $newAbsolutePath && is_file($absolutePath)) {
                unlink($absolutePath);
            }

            $absolutePath = $newAbsolutePath;
        }

        [$width, $height] = getimagesize($absolutePath) ?: [null, null];

        $stmt = $this->pdo->prepare(
            'INSERT INTO uploads (
                uploaded_by, original_name, stored_name, storage_path, mime_type,
                extension, size_bytes, width_px, height_px, sha256_hash, image_role
            ) VALUES (
                :uploaded_by, :original_name, :stored_name, :storage_path, :mime_type,
                :extension, :size_bytes, :width_px, :height_px, :sha256_hash, :image_role
            )
            ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), updated_at = NOW()'
        );

        $stmt->execute([
            'uploaded_by' => $this->userId,
            'original_name' => $storedName,
            'stored_name' => $storedName,
            'storage_path' => $relativePath,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'size_bytes' => strlen($bytes),
            'width_px' => $width,
            'height_px' => $height,
            'sha256_hash' => $hash,
            'image_role' => $role,
        ]);

        $uploadId = (int) $this->pdo->lastInsertId();
        $this->storeThumbnail($optimizer, $absolutePath, $mimeType, $hash, $storedName);

        return [
            'upload_id' => $uploadId,
            'path' => $absolutePath,
            'relative_path' => $relativePath,
            'mime_type' => $mimeType,
            'hash' => $hash,
        ];
    }

    private function extensionFromMime(string $mimeType): string
    {
        return match ($mimeType) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }

    private function storeThumbnail(ImageOptimizationService $optimizer, string $path, string $mimeType, string $sourceHash, string $originalName): void
    {
        $thumbnail = $optimizer->thumbnail($path, $mimeType, $sourceHash);

        if ($thumbnail === null) {
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO uploads (
                uploaded_by, original_name, stored_name, storage_path, mime_type,
                extension, size_bytes, width_px, height_px, sha256_hash, image_role
            ) VALUES (
                :uploaded_by, :original_name, :stored_name, :storage_path, :mime_type,
                :extension, :size_bytes, :width_px, :height_px, :sha256_hash, "thumbnail"
            )
            ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id), updated_at = NOW()'
        );

        $stmt->execute([
            'uploaded_by' => $this->userId,
            'original_name' => clean_text($originalName),
            'stored_name' => basename($thumbnail['relative_path']),
            'storage_path' => $thumbnail['relative_path'],
            'mime_type' => $thumbnail['mime_type'],
            'extension' => $thumbnail['extension'],
            'size_bytes' => $thumbnail['size'],
            'width_px' => $thumbnail['width'],
            'height_px' => $thumbnail['height'],
            'sha256_hash' => $thumbnail['hash'],
        ]);
    }
}
