<?php

final class UploadSecurityService
{
    private const ALLOWED = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
    ];

    private const MAX_BYTES = 10485760;

    public function __construct(private ?SecurityLogger $logger = null)
    {
    }

    public function validateImageUpload(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload invalido.');
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new RuntimeException('Imagem acima do limite permitido.');
        }

        $tmpPath = (string) $file['tmp_name'];
        $original = (string) ($file['name'] ?? '');
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $mimeType = mime_content_type($tmpPath) ?: '';

        if (!isset(self::ALLOWED[$mimeType]) || !in_array($extension, self::ALLOWED[$mimeType], true)) {
            $this->logger?->log('warning', 'upload_rejected', 'Upload rejeitado por MIME/extensao.', [
                'mime' => $mimeType,
                'extension' => $extension,
            ]);
            throw new RuntimeException('Formato de imagem nao permitido.');
        }

        if (getimagesize($tmpPath) === false) {
            throw new RuntimeException('Arquivo de imagem invalido.');
        }

        $head = file_get_contents($tmpPath, false, null, 0, 512) ?: '';
        if (preg_match('/<\\?php|<script|<html/i', $head)) {
            $this->logger?->log('critical', 'malicious_upload_blocked', 'Assinatura maliciosa detectada em upload.');
            throw new RuntimeException('Arquivo recusado.');
        }

        return [
            'mime_type' => $mimeType,
            'extension' => self::ALLOWED[$mimeType][0],
        ];
    }

    public function safeDirectory(string $directory): string
    {
        $directory = trim(str_replace(['..', '\\'], ['', '/'], $directory), '/');
        return $directory === '' ? 'uploads' : $directory;
    }
}
