<?php

final class KanbanController extends ApiController
{
    public function move(ApiRequest $request): void
    {
        $data = $request->input();
        $leadId = (int) ($data['lead_id'] ?? $data['card_id'] ?? 0);
        $stageId = (int) ($data['stage_id'] ?? $data['column_id'] ?? 0);

        if ($leadId <= 0 || $stageId <= 0) {
            ApiResponse::validation(['lead_id' => ['Lead obrigatorio.'], 'stage_id' => ['Etapa obrigatoria.']]);
        }

        $current = $this->pdo->prepare('SELECT pipeline_stage_id FROM leads WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $current->execute(['id' => $leadId]);
        $fromStageId = $current->fetchColumn();

        $this->pdo->beginTransaction();

        try {
            $update = $this->pdo->prepare('UPDATE leads SET pipeline_stage_id = :stage_id, updated_by = :user_id WHERE id = :id AND deleted_at IS NULL');
            $update->execute(['id' => $leadId, 'stage_id' => $stageId, 'user_id' => auth_id()]);
            $history = $this->pdo->prepare(
                'INSERT INTO lead_stage_history (lead_id, from_stage_id, to_stage_id, changed_by, note)
                 VALUES (:lead_id, :from_stage_id, :to_stage_id, :changed_by, :note)'
            );
            $history->execute([
                'lead_id' => $leadId,
                'from_stage_id' => $fromStageId ?: null,
                'to_stage_id' => $stageId,
                'changed_by' => auth_id(),
                'note' => clean_text($data['note'] ?? 'Movido via API interna'),
            ]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        $this->audit->write('operational', 'info', 'api_kanban_moved', 'Lead movido no kanban via API interna.', [
            'from_stage_id' => $fromStageId,
            'to_stage_id' => $stageId,
        ], auth_id(), 'lead', $leadId);

        ApiResponse::success(['lead_id' => $leadId, 'stage_id' => $stageId], 'Kanban atualizado.');
    }
}
