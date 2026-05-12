<?php

final class ImageOptimizationService
{
    public function optimizeOriginal(string $path, string $mimeType, int $maxWidth = 1800): void
    {
        if (!function_exists('imagecreatefromstring') || !is_file($path)) {
            return;
        }

        $image = $this->load($path);

        if (!$image) {
            return;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $target = $width > $maxWidth
            ? $this->resize($image, $maxWidth, (int) round($height * ($maxWidth / $width)))
            : $image;

        $this->save($target, $path, $mimeType, 82);

        if ($target !== $image) {
            imagedestroy($target);
        }

        imagedestroy($image);
    }

    public function thumbnail(string $path, string $mimeType, string $hash, int $maxWidth = 520): ?array
    {
        if (!function_exists('imagecreatefromstring') || !is_file($path)) {
            return null;
        }

        $image = $this->load($path);

        if (!$image) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $targetWidth = min($maxWidth, $width);
        $targetHeight = (int) round($height * ($targetWidth / max(1, $width)));
        $thumb = $this->resize($image, $targetWidth, $targetHeight);
        $extension = $this->extension($mimeType);
        $relativePath = 'thumbnails/' . $hash . '-' . $targetWidth . 'w.' . $extension;
        $absolutePath = STORAGE_PATH . '/' . $relativePath;

        if (!is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0775, true);
        }

        $this->save($thumb, $absolutePath, $mimeType, 78);
        imagedestroy($thumb);
        imagedestroy($image);

        [$thumbWidth, $thumbHeight] = getimagesize($absolutePath) ?: [null, null];

        return [
            'path' => $absolutePath,
            'relative_path' => $relativePath,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'width' => $thumbWidth,
            'height' => $thumbHeight,
            'hash' => hash_file('sha256', $absolutePath),
            'size' => filesize($absolutePath),
        ];
    }

    private function load(string $path): mixed
    {
        $bytes = file_get_contents($path);

        return $bytes === false ? null : imagecreatefromstring($bytes);
    }

    private function resize(mixed $image, int $width, int $height): mixed
    {
        $resized = imagecreatetruecolor($width, $height);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));

        return $resized;
    }

    private function save(mixed $image, string $path, string $mimeType, int $quality): void
    {
        match ($mimeType) {
            'image/png' => imagepng($image, $path, 7),
            'image/webp' => function_exists('imagewebp') ? imagewebp($image, $path, $quality) : imagejpeg($image, $path, $quality),
            default => imagejpeg($image, $path, $quality),
        };
    }

    private function extension(string $mimeType): string
    {
        return match ($mimeType) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }
}
