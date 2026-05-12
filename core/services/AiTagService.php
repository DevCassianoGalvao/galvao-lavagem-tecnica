<?php

final class AiTagService
{
    public function __construct(
        private PDO $pdo,
        private OpenAiClient $client,
        private AiLogger $logger,
        private array $config
    ) {
    }

    public function suggestForLead(int $leadId, array $context, bool $force = false): array
    {
        $promptHash = $this->hash($context);

        if (!$force && ($cached = $this->cached($leadId, $promptHash))) {
            return $cached + ['cached' => true];
        }

        $fallback = ['tags' => $this->fallbackTags($context), 'confidence' => 0.58];
        $result = $this->client->completeJson(
            'Voce sugere tags operacionais curtas para CRM premium. Responda apenas JSON valido.',
            "Sugira tags para este lead. Use tags curtas, sem duplicatas. JSON esperado: {\"tags\":[\"cliente premium\"],\"confidence\":0.0}. Contexto:\n" . json_encode($context, JSON_UNESCAPED_UNICODE),
            $fallback
        );

        $tags = array_values(array_unique(array_filter(array_map('clean_text', $result['tags'] ?? $fallback['tags']))));
        $confidence = (float) ($result['confidence'] ?? $fallback['confidence']);

        foreach ($tags as $tag) {
            $this->saveSuggestion($leadId, $tag, $confidence, $promptHash);
        }

        $this->logger->info('ai_tags_created', 'Tags automaticas sugeridas para lead.', [
            'lead_id' => $leadId,
            'tags' => $tags,
            'prompt_hash' => $promptHash,
        ]);

        return [
            'tags' => $tags,
            'confidence' => $confidence,
            'prompt_hash' => $promptHash,
            'cached' => false,
        ];
    }

    private function saveSuggestion(int $leadId, string $tag, float $confidence, string $promptHash): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO ai_tag_suggestions (lead_id, tag_name, confidence, model_name, prompt_hash, status)
             VALUES (:lead_id, :tag_name, :confidence, :model_name, :prompt_hash, :status)'
        );

        $stmt->execute([
            'lead_id' => $leadId,
            'tag_name' => $tag,
            'confidence' => $confidence,
            'model_name' => $this->client->model(),
            'prompt_hash' => $promptHash,
            'status' => 'suggested',
        ]);
    }

    private function cached(int $leadId, string $promptHash): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT tag_name, confidence, prompt_hash
             FROM ai_tag_suggestions
             WHERE lead_id = :lead_id
               AND prompt_hash = :prompt_hash
             ORDER BY created_at DESC'
        );
        $stmt->execute(['lead_id' => $leadId, 'prompt_hash' => $promptHash]);
        $rows = $stmt->fetchAll();

        if (!$rows) {
            return null;
        }

        return [
            'tags' => array_values(array_unique(array_column($rows, 'tag_name'))),
            'confidence' => (float) ($rows[0]['confidence'] ?? 0),
            'prompt_hash' => $promptHash,
        ];
    }

    private function fallbackTags(array $context): array
    {
        $lead = $context['lead'];
        $tags = [];

        foreach ($context['surfaces'] as $surface) {
            $tags[] = mb_strtolower($surface);
        }

        foreach ($context['dirt_types'] as $dirt) {
            $tags[] = mb_strtolower($dirt);
        }

        if (($lead['access_difficulty'] ?? '') === 'hard') {
            $tags[] = 'dificil acesso';
        }

        if ((int) ($lead['has_elevated_height'] ?? 0) === 1) {
            $tags[] = 'altura elevada';
        }

        if (($lead['cleaning_frequency'] ?? '') === 'frequently') {
            $tags[] = 'recorrente';
        }

        if (($lead['score'] ?? 0) >= 80) {
            $tags[] = 'cliente premium';
        }

        return array_values(array_unique(array_filter($tags ?: ['diagnostico tecnico'])));
    }

    private function hash(array $context): string
    {
        return hash('sha256', json_encode($context, JSON_UNESCAPED_UNICODE));
    }
}
