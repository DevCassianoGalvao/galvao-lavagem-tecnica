<?php

final class LeadsController extends ApiController
{
    public function index(ApiRequest $request): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT l.id, l.name, l.email, l.phone, l.priority, l.status, l.score, l.created_at,
                    ps.name AS stage_name, c.name AS client_name
             FROM leads l
             LEFT JOIN pipeline_stages ps ON ps.id = l.pipeline_stage_id
             LEFT JOIN clients c ON c.id = l.client_id
             WHERE l.deleted_at IS NULL
             ORDER BY l.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $this->limit($request), PDO::PARAM_INT);
        $stmt->execute();

        ApiResponse::success(['leads' => $stmt->fetchAll()]);
    }

    public function show(ApiRequest $request, int $id): void
    {
        $stmt = $this->pdo->prepare('SELECT * FROM leads WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        $lead = $stmt->fetch();

        if (!$lead) {
            ApiResponse::error('Lead nao encontrado.', 404);
        }

        ApiResponse::success(['lead' => $lead]);
    }

    public function store(ApiRequest $request): void
    {
        $data = $this->cleanPayload($request->input());
        ApiValidator::assert($data, [
            'name' => 'required|max:140',
            'phone' => 'required|max:40',
            'email' => 'email|max:160',
        ]);

        $stageId = $this->defaultStageId();
        $stmt = $this->pdo->prepare(
            'INSERT INTO leads (
                pipeline_stage_id, assigned_user_id, name, email, phone, source,
                priority, area_size, access_difficulty, status, created_by
            ) VALUES (
                :pipeline_stage_id, :assigned_user_id, :name, :email, :phone, :source,
                :priority, :area_size, :access_difficulty, "new", :created_by
            )'
        );
        $stmt->execute([
            'pipeline_stage_id' => $stageId,
            'assigned_user_id' => auth_id(),
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'source' => $data['source'] ?? 'api',
            'priority' => $data['priority'] ?? null,
            'area_size' => $data['area_size'] ?? null,
            'access_difficulty' => $data['access_difficulty'] ?? null,
            'created_by' => auth_id(),
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $this->audit->write('operational', 'info', 'api_lead_created', 'Lead criado via API interna.', [], auth_id(), 'lead', $id);

        ApiResponse::success(['lead_id' => $id], 'Lead criado.', 201);
    }

    public function update(ApiRequest $request, int $id): void
    {
        $data = $this->cleanPayload($request->input());
        $fields = [];
        $params = ['id' => $id, 'updated_by' => auth_id()];

        foreach (['status', 'priority', 'area_size', 'access_difficulty', 'next_follow_up_at'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = $field . ' = :' . $field;
                $params[$field] = $data[$field] ?: null;
            }
        }

        if (!$fields) {
            ApiResponse::validation(['lead' => ['Nenhum campo enviado para atualizacao.']]);
        }

        $sql = 'UPDATE leads SET ' . implode(', ', $fields) . ', updated_by = :updated_by WHERE id = :id AND deleted_at IS NULL';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $this->audit->write('operational', 'info', 'api_lead_updated', 'Lead atualizado via API interna.', array_keys($params), auth_id(), 'lead', $id);

        ApiResponse::success(['lead_id' => $id], 'Lead atualizado.');
    }

    private function defaultStageId(): ?int
    {
        $stage = $this->pdo->query('SELECT id FROM pipeline_stages ORDER BY position ASC, id ASC LIMIT 1')->fetchColumn();

        return $stage ? (int) $stage : null;
    }
}
