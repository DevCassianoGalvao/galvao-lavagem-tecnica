<?php

final class OpenAiClient
{
    private string $apiKey;
    private string $model;

    public function __construct(
        private array $config,
        private AiLogger $logger
    ) {
        $this->apiKey = (string) ($config['openai_api_key'] ?? '');
        $this->model = (string) ($config['openai_text_model'] ?? 'gpt-5.4-mini');
    }

    public function completeJson(string $systemPrompt, string $userPrompt, array $fallback): array
    {
        if ($this->apiKey === '') {
            $this->logger->info('ai_text', 'OPENAI_API_KEY ausente. Usando fallback deterministico.', [
                'model' => $this->model,
            ]);

            return $fallback;
        }

        if (!function_exists('curl_init')) {
            $this->logger->warning('ai_text', 'Extensao cURL indisponivel. Usando fallback deterministico.');
            return $fallback;
        }

        $payload = [
            'model' => $this->model,
            'input' => [
                ['role' => 'system', 'content' => [['type' => 'input_text', 'text' => $systemPrompt]]],
                ['role' => 'user', 'content' => [['type' => 'input_text', 'text' => $userPrompt]]],
            ],
            'text' => [
                'format' => ['type' => 'json_object'],
            ],
        ];

        $ch = curl_init('https://api.openai.com/v1/responses');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 45,
        ]);

        $raw = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($raw === false || $status >= 400) {
            $this->logger->error('ai_text', 'Falha na chamada OpenAI. Usando fallback.', [
                'status' => $status,
                'error' => $error,
                'body' => $raw,
            ]);

            return $fallback;
        }

        $decoded = json_decode($raw, true);
        $text = $decoded['output_text'] ?? $this->extractText($decoded);
        $json = json_decode((string) $text, true);

        if (!is_array($json)) {
            $this->logger->error('ai_text', 'Resposta OpenAI nao retornou JSON valido. Usando fallback.', [
                'body' => $raw,
            ]);

            return $fallback;
        }

        return $json;
    }

    private function extractText(?array $decoded): string
    {
        foreach (($decoded['output'] ?? []) as $item) {
            foreach (($item['content'] ?? []) as $content) {
                if (isset($content['text'])) {
                    return (string) $content['text'];
                }
            }
        }

        return '';
    }

    public function model(): string
    {
        return $this->model;
    }
}
