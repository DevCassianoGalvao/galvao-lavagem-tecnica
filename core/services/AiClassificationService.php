<?php

final class AiClassificationService
{
    public function __construct(
        private PDO $pdo,
        private OpenAiClient $client,
        private AiLogger $logger,
        private array $config
    ) {
    }

    public function classifyLead(int $leadId, array $context, bool $force = false): array
    {
        $promptHash = $this->hash($context);

        if (!$force && ($cached = $this->cached($leadId, $promptHash))) {
            return $cached + ['cached' => true];
        }

        $fallback = $this->fallbackClassification($context);
        $result = $this->client->completeJson(
            'Voce classifica complexidade operacional para lavagem tecnica. Responda apenas JSON valido.',
            "Classifique este lead como simples, medio ou pesado. Considere acesso, sujeira, altura e quantidade de superficies. JSON esperado: {\"classification\":\"medio\",\"reason\":\"...\",\"confidence\":0.0}. Contexto:\n" . json_encode($context, JSON_UNESCAPED_UNICODE),
            $fallback
        );

        $classification = clean_text($result['classification'] ?? $fallback['classification']);

        if (!in_array($classification, ['simples', 'medio', 'pesado'], true)) {
            $classification = 'medio';
        }

        $reason = clean_text($result['reason'] ?? $fallback['reason']);
        $confidence = (float) ($result['confidence'] ?? $fallback['confidence']);

        $stmt = $this->pdo->prepare(
            'INSERT INTO ai_classifications (lead_id, classification, reason, confidence, model_name, prompt_hash)
             VALUES (:lead_id, :classification, :reason, :confidence, :model_name, :prompt_hash)'
        );
        $stmt->execute([
            'lead_id' => $leadId,
            'classification' => $classification,
            'reason' => $reason,
            'confidence' => $confidence,
            'model_name' => $this->client->model(),
            'prompt_hash' => $promptHash,
        ]);

        $this->logger->info('ai_classification_created', 'Classificacao operacional criada para lead.', [
            'lead_id' => $leadId,
            'classification' => $classification,
            'prompt_hash' => $promptHash,
        ]);

        return [
            'classification' => $classification,
            'reason' => $reason,
            'confidence' => $confidence,
            'prompt_hash' => $promptHash,
            'cached' => false,
        ];
    }

    private function cached(int $leadId, string $promptHash): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT classification, reason, confidence, prompt_hash
             FROM ai_classifications
             WHERE lead_id = :lead_id
               AND prompt_hash = :prompt_hash
             ORDER BY created_at DESC
             LIMIT 1'
        );
        $stmt->execute(['lead_id' => $leadId, 'prompt_hash' => $promptHash]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return [
            'classification' => $row['classification'],
            'reason' => $row['reason'],
            'confidence' => (float) $row['confidence'],
            'prompt_hash' => $row['prompt_hash'],
        ];
    }

    private function fallbackClassification(array $context): array
    {
        $lead = $context['lead'];
        $score = 0;
        $score += count($context['surfaces']) >= 4 ? 2 : count($context['surfaces']);
        $score += count(array_intersect(array_map('mb_strtolower', $context['dirt_types']), ['lodo', 'musgo', 'mofo'])) >= 2 ? 1 : 0;
        $score += ($lead['access_difficulty'] ?? '') === 'hard' ? 2 : (($lead['access_difficulty'] ?? '') === 'medium' ? 1 : 0);
        $score += (int) ($lead['has_elevated_height'] ?? 0) === 1 ? 2 : 0;

        $classification = $score >= 5 ? 'pesado' : ($score >= 3 ? 'medio' : 'simples');

        return [
            'classification' => $classification,
            'reason' => 'Classificacao estimada por quantidade de superficies, sujeiras, acesso e altura informados.',
            'confidence' => 0.6,
        ];
    }

    private function hash(array $context): string
    {
        return hash('sha256', json_encode($context, JSON_UNESCAPED_UNICODE));
    }
}
