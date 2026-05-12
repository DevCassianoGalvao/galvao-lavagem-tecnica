<?php

final class AiVisualService
{
    public function __construct(
        private PDO $pdo,
        private array $config,
        private AiLogger $logger
    ) {
        try {
            $this->config = array_merge($this->config, (new SettingsService($this->pdo))->configOverrides());
        } catch (Throwable) {
            // Mantem a configuracao local quando o banco ainda nao esta disponivel.
        }
    }

    public function simulateRevitalization(array $sourceUpload, array $relations = []): array
    {
        $prompt = $this->prompt();
        $bytes = $this->generateWithOpenAi($sourceUpload['path'], $prompt);
        $mimeType = 'image/png';

        if ($bytes === null) {
            $this->logger->info('ai_image_fallback', 'Gerando simulacao visual local por falta de resposta da API.');
            $bytes = $this->generateFallbackImage($sourceUpload['path']);
            $mimeType = function_exists('imagecreatefromstring') ? 'image/png' : $sourceUpload['mime_type'];
        }

        $bytes = $this->addWatermark($bytes);
        $uploadService = new ImageUploadService($this->pdo, $relations['created_by'] ?? null);
        $resultUpload = $uploadService->storeBinary($bytes, $mimeType, 'ai-images/results', 'ai_generated');

        $history = new ImageHistoryService($this->pdo);
        $history->linkUpload($sourceUpload['upload_id'], $relations, 'ai_source');
        $history->linkUpload($resultUpload['upload_id'], $relations, 'ai_result');

        $historyId = $history->createAiImageRecord([
            'source_upload_id' => $sourceUpload['upload_id'],
            'result_upload_id' => $resultUpload['upload_id'],
            'lead_id' => $relations['lead_id'] ?? null,
            'client_id' => $relations['client_id'] ?? null,
            'property_id' => $relations['property_id'] ?? null,
            'surface_id' => $relations['surface_id'] ?? null,
            'service_id' => $relations['service_id'] ?? null,
            'model_name' => $this->config['openai_image_model'] ?? 'gpt-image-1.5',
            'prompt_text' => $prompt,
            'status' => 'completed',
            'analysis' => [
                'intent' => 'revitalizacao_visual',
                'watermark' => true,
                'architecture_preserved' => true,
            ],
        ]);

        return [
            'history_id' => $historyId,
            'source_upload_id' => $sourceUpload['upload_id'],
            'result_upload_id' => $resultUpload['upload_id'],
            'result_data_url' => 'data:image/png;base64,' . base64_encode($bytes),
        ];
    }

    public function inspectImage(string $imagePath): array
    {
        return [
            'confidence' => 0.75,
            'diagnosis' => 'Imagem preparada para simulacao de revitalizacao externa.',
            'recommended_service' => 'Revitalizacao tecnica de area externa',
        ];
    }

    private function generateWithOpenAi(string $imagePath, string $prompt): ?string
    {
        $apiKey = (string) ($this->config['openai_api_key'] ?? '');

        if ($apiKey === '') {
            return null;
        }

        if (!function_exists('curl_init')) {
            throw new RuntimeException('Extensao cURL indisponivel para chamar a OpenAI.');
        }

        $model = (string) ($this->config['openai_image_model'] ?? 'gpt-image-1.5');
        $mimeType = mime_content_type($imagePath) ?: 'image/png';
        $imageDataUrl = 'data:' . $mimeType . ';base64,' . base64_encode((string) file_get_contents($imagePath));

        $payload = [
            'model' => $model,
            'prompt' => $prompt,
            'images' => [
                ['image_url' => $imageDataUrl],
            ],
            'size' => '1024x1024',
            'output_format' => 'png',
        ];

        $ch = curl_init('https://api.openai.com/v1/images/edits');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 120,
        ]);

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $status >= 400) {
            $this->logger->error('ai_image_generation_failed', 'Falha ao chamar API de imagem.', [
                'status' => $status,
                'error' => $error,
                'body' => $raw,
            ]);

            throw new RuntimeException('Falha na API de imagem: ' . $this->extractOpenAiError((string) $raw, $status));
        }

        $decoded = json_decode($raw, true);
        $base64 = $decoded['data'][0]['b64_json'] ?? null;

        if (!is_string($base64)) {
            throw new RuntimeException('A API de imagem nao retornou uma imagem em base64.');
        }

        return base64_decode($base64);
    }

    private function extractOpenAiError(string $raw, int $status): string
    {
        $decoded = json_decode($raw, true);
        $message = $decoded['error']['message'] ?? null;

        return is_string($message) ? '[' . $status . '] ' . $message : '[' . $status . '] ' . substr($raw, 0, 220);
    }

    private function prompt(): string
    {
        return implode(' ', [
            'Edit the uploaded image and create a clearly improved AFTER version of the same external surface after professional pressure washing with a high-pressure washer.',
            'The final image must show the surface clean: remove nearly all visible moss, algae, green organic growth, mildew, slime, dark grime, black stains, mud and slippery buildup.',
            'If the image shows a mossy stone wall, transform it into a clean natural stone wall: visible gray and beige stones, cleaner joints, dry-looking surfaces and only very minimal natural aging.',
            'Make the cleaning transformation strong and obvious, comparable to a before-and-after pressure washing advertisement.',
            'Use the original photo only as structural reference. You may regenerate surface details where moss covered the material so the clean material underneath becomes visible.',
            'Keep the same general camera angle, wall layout, stone pattern, perspective, scale and composition so it still feels like the same place.',
            'Do not paint the wall, replace stones, add decoration, add water puddles, add shine, add plants, add furniture, change architecture or make it look like a 3D render.',
            'The result must be photorealistic, natural, premium and believable as a real lavagem de alta pressao / pressure washing result.',
        ]);
    }

    private function generateFallbackImage(string $imagePath): string
    {
        if (!function_exists('imagecreatefromjpeg')) {
            return file_get_contents($imagePath);
        }

        $image = $this->createImageResource($imagePath);
        $width = imagesx($image);
        $height = imagesy($image);

        imagefilter($image, IMG_FILTER_BRIGHTNESS, 12);
        imagefilter($image, IMG_FILTER_CONTRAST, -10);
        imagefilter($image, IMG_FILTER_SMOOTH, 4);

        $overlay = imagecolorallocatealpha($image, 212, 175, 55, 112);
        imagefilledrectangle($image, 0, 0, $width, $height, $overlay);

        ob_start();
        imagepng($image);
        imagedestroy($image);

        return (string) ob_get_clean();
    }

    private function addWatermark(string $pngBytes): string
    {
        if (!function_exists('imagecreatefromstring')) {
            return $pngBytes;
        }

        $image = imagecreatefromstring($pngBytes);

        if (!$image) {
            return $pngBytes;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $bar = imagecolorallocatealpha($image, 15, 15, 15, 58);
        $gold = imagecolorallocatealpha($image, 212, 175, 55, 18);
        $text = (string) ($this->config['ai_watermark_text'] ?? 'Simulacao IA - Galvao Lavagem Tecnica');

        imagefilledrectangle($image, 0, $height - 42, $width, $height, $bar);
        imagestring($image, 3, 18, $height - 28, $text, $gold);

        ob_start();
        imagepng($image);
        imagedestroy($image);

        return (string) ob_get_clean();
    }

    private function createImageResource(string $path): mixed
    {
        $mime = mime_content_type($path);

        return match ($mime) {
            'image/png' => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            default => imagecreatefromjpeg($path),
        };
    }
}
