<?php

final class AiSummaryService
{
    public function __construct(
        private PDO $pdo,
        private OpenAiClient $client,
        private AiLogger $logger,
        private array $config
    ) {
    }

    public function generateForLead(int $leadId, array $context, bool $force = false): array
    {
        $promptHash = $this->hash($context);

        if (!$force && ($cached = $this->cached($leadId, $promptHash))) {
            return $cached + ['cached' => true];
        }

        $fallback = [
            'summary' => $this->fallbackSummary($context),
            'confidence' => 0.62,
        ];

        $result = $this->client->completeJson(
            'Voce e um analista operacional premium da Galvao Lavagem Tecnica. Responda apenas JSON valido.',
            "Gere um resumo elegante e objetivo em portugues do Brasil para este lead. JSON esperado: {\"summary\":\"...\",\"confidence\":0.0}. Contexto:\n" . json_encode($context, JSON_UNESCAPED_UNICODE),
            $fallback
        );

        $summary = clean_text($result['summary'] ?? $fallback['summary']);
        $confidence = (float) ($result['confidence'] ?? $fallback['confidence']);

        $stmt = $this->pdo->prepare(
            'INSERT INTO ai_summaries (client_id, lead_id, property_id, model_name, summary_type, prompt_hash, summary_text, confidence)
             SELECT client_id, id, property_id, :model_name, :summary_type, :prompt_hash, :summary_text, :confidence
             FROM leads
             WHERE id = :lead_id'
        );
        $stmt->execute([
            'model_name' => $this->client->model(),
            'summary_type' => 'lead',
            'prompt_hash' => $promptHash,
            'summary_text' => $summary,
            'confidence' => $confidence,
            'lead_id' => $leadId,
        ]);

        $this->logger->info('ai_summary_created', 'Resumo textual criado para lead.', [
            'lead_id' => $leadId,
            'prompt_hash' => $promptHash,
        ]);

        return [
            'summary' => $summary,
            'confidence' => $confidence,
            'prompt_hash' => $promptHash,
            'cached' => false,
        ];
    }

    private function cached(int $leadId, string $promptHash): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT summary_text, confidence, prompt_hash, created_at
             FROM ai_summaries
             WHERE lead_id = :lead_id
               AND summary_type = :summary_type
               AND prompt_hash = :prompt_hash
             ORDER BY created_at DESC
             LIMIT 1'
        );
        $stmt->execute([
            'lead_id' => $leadId,
            'summary_type' => 'lead',
            'prompt_hash' => $promptHash,
        ]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return [
            'summary' => $row['summary_text'],
            'confidence' => (float) $row['confidence'],
            'prompt_hash' => $row['prompt_hash'],
        ];
    }

    private function fallbackSummary(array $context): string
    {
        $lead = $context['lead'];
        $location = $lead['property_city'] ?: ($lead['client_city'] ?: 'localizacao nao informada');
        $property = $lead['property_type'] ?: 'imovel';
        $surfaces = implode(', ', $context['surfaces'] ?: ['superficies externas']);
        $dirt = implode(', ', $context['dirt_types'] ?: ['sujeira aderida']);

        return sprintf(
            'Cliente localizado em %s com %s apresentando sinais em %s, incluindo %s. Interesse principal em revitalizacao tecnica, seguranca e valorizacao estetica.',
            $location,
            mb_strtolower($property),
            $surfaces,
            $dirt
        );
    }

    private function hash(array $context): string
    {
        return hash('sha256', json_encode($context, JSON_UNESCAPED_UNICODE));
    }
}
