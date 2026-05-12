<?php

final class AiController extends ApiController
{
    public function text(ApiRequest $request): void
    {
        $data = $request->input();
        $leadId = (int) ($data['lead_id'] ?? 0);

        if ($leadId <= 0) {
            ApiResponse::validation(['lead_id' => ['Lead obrigatorio.']]);
        }

        $jobId = (new QueueService($this->pdo, $this->audit))->enqueueAiText($leadId, (bool) ($data['force'] ?? false));

        ApiResponse::success(['job_id' => $jobId], 'IA textual enfileirada.', 202);
    }

    public function visual(ApiRequest $request): void
    {
        $data = $request->input();
        $uploadId = (int) ($data['upload_id'] ?? 0);

        if ($uploadId <= 0) {
            ApiResponse::validation(['upload_id' => ['Upload fonte obrigatorio.']]);
        }

        $jobId = (new QueueService($this->pdo, $this->audit))->enqueueAiVisual($uploadId, [
            'lead_id' => (int) ($data['lead_id'] ?? 0) ?: null,
            'caption' => 'Simulacao IA - Galvao Lavagem Tecnica',
            'created_by' => auth_id(),
        ]);

        ApiResponse::success(['job_id' => $jobId], 'IA visual enfileirada.', 202);
    }
}
