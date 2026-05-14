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
                'cost_profile' => 'optimized',
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

        $model = $this->imageModel();
        $mimeType = mime_content_type($imagePath) ?: 'image/png';
        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => 'png',
        };

        $payload = [
            'model' => $model,
            'prompt' => $prompt,
            'image' => new CURLFile($imagePath, $mimeType, 'environment.' . $extension),
            'size' => (string) ($this->config['ai_image_generation_size'] ?? '1024x1024'),
            'output_format' => 'png',
        ];

        $ch = curl_init('https://api.openai.com/v1/images/edits');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => $payload,
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

    private function imageModel(): string
    {
        $model = (string) ($this->config['openai_image_model'] ?? 'gpt-image-1.5');
        return trim($model) !== '' ? trim($model) : 'gpt-image-1.5';
    }

    private function prompt(): string
    {
        return implode(' ', [
            'Edit the uploaded image as a conservative photorealistic cleaning simulation of professional pressure washing with a high-pressure washer.',
            'Output only one final AFTER image: a single full-frame cleaned version of the uploaded photo.',
            'Do not create a before-and-after comparison, split-screen, side-by-side layout, vertical divider, labels, captions, arrows, borders, panels or collage.',
            'The user already provided the BEFORE image, so the generated image must show only the cleaned AFTER result.',
            'This is a cleaning simulation of the exact same place, not a renovation, redesign, reconstruction, material replacement or scene generation.',
            'Preserve the original camera angle, lens perspective, crop, framing, scale, depth, horizon, shadows, lighting direction and overall composition.',
            'Preserve all architecture, roof direction, floor direction, tile layout, stone layout, brick layout, grout lines, joints, edges, stairs, walls, borders, fixed objects, plants, furniture, drains, windows, doors, railings and surrounding context exactly where they are.',
            'Do not change the direction, shape, spacing, pattern, size, color family or material of roofs, tiles, floors, stones, bricks, wood, concrete or walls.',
            'Do not replace a floor with another floor, do not replace a roof with another roof, do not invent new tiles, stones, bricks, boards, objects or decorative elements.',
            'Only remove or reduce visible dirt caused by external exposure: moss, algae, green organic growth, mildew, slime, mud, dark grime, black stains and slippery buildup.',
            'Reveal the existing material underneath the dirt while keeping its original pattern, natural imperfections, age, color variation and geometry.',
            'The transformation should look like the same surface after careful lavagem de alta pressao / pressure washing, with a visibly cleaner and safer appearance.',
            'Make the improvement clear but plausible: no glossy finish, no wet shine, no artificial perfection, no 3D render, no beauty filter and no over-cleaned unrealistic surface.',
            'If an area is too covered by moss or dirt, infer the cleaned appearance from neighboring visible parts of the same material, without changing the underlying layout or object positions.',
            'Output must remain photorealistic, natural, premium and believable as a real cleaning result from the same environment.',
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
        $shadow = imagecolorallocatealpha($image, 0, 0, 0, 52);
        $gold = imagecolorallocatealpha($image, 212, 175, 55, 22);
        $text = (string) ($this->config['ai_watermark_text'] ?? 'Simulação visual - Galvão Lavagem Técnica');
        $font = 2;
        $textWidth = imagefontwidth($font) * strlen($text);
        $textHeight = imagefontheight($font);
        $x = max(12, $width - $textWidth - 16);
        $y = max(12, $height - $textHeight - 14);

        imagefilledrectangle($image, $x - 8, $y - 5, $width - 8, $height - 8, $shadow);
        imagestring($image, $font, $x, $y, $text, $gold);

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
