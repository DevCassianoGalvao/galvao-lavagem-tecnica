<?php

final class AiTextService
{
    public function __construct(
        private PDO $pdo,
        private array $config
    ) {
        try {
            $this->config = array_merge($this->config, (new SettingsService($this->pdo))->configOverrides());
        } catch (Throwable) {
            // Mantem o fallback do env.php quando o banco ainda nao foi migrado.
        }
    }

    public function analyzeLead(int $leadId, bool $force = false): array
    {
        $contextService = new AiLeadContextService($this->pdo);
        $logger = new AiLogger($this->pdo);
        $client = new OpenAiClient($this->config, $logger);

        $context = $contextService->forLead($leadId);

        if ($context === null) {
            $logger->warning('ai_text', 'Lead nao encontrado para analise textual.', [
                'lead_id' => $leadId,
            ]);

            return [
                'success' => false,
                'message' => 'Lead nao encontrado.',
            ];
        }

        $summaryService = new AiSummaryService($this->pdo, $client, $logger, $this->config);
        $tagService = new AiTagService($this->pdo, $client, $logger, $this->config);
        $classificationService = new AiClassificationService($this->pdo, $client, $logger, $this->config);

        return [
            'success' => true,
            'summary' => $summaryService->generateForLead($leadId, $context, $force),
            'tags' => $tagService->suggestForLead($leadId, $context, $force),
            'classification' => $classificationService->classifyLead($leadId, $context, $force),
        ];
    }
}
