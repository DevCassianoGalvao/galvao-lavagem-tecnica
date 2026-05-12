<?php

final class CalendarController extends ApiController
{
    public function index(ApiRequest $request): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, title, event_type, status, starts_at, ends_at, location_text, client_id, lead_id, service_id
             FROM calendar_events
             WHERE deleted_at IS NULL
             ORDER BY starts_at ASC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $this->limit($request, 60, 200), PDO::PARAM_INT);
        $stmt->execute();

        ApiResponse::success(['events' => $stmt->fetchAll()]);
    }

    public function show(ApiRequest $request, int $id): void
    {
        $stmt = $this->pdo->prepare('SELECT * FROM calendar_events WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        $event = $stmt->fetch();

        if (!$event) {
            ApiResponse::error('Evento nao encontrado.', 404);
        }

        ApiResponse::success(['event' => $event]);
    }

    public function store(ApiRequest $request): void
    {
        $data = $this->cleanPayload($request->input());
        ApiValidator::assert($data, [
            'title' => 'required|max:160',
            'starts_at' => 'required',
            'event_type' => 'in:visit,service,follow_up,internal,delivery',
        ]);

        $stmt = $this->pdo->prepare(
            'INSERT INTO calendar_events (
                title, description, event_type, status, starts_at, ends_at, location_text,
                client_id, lead_id, service_id, assigned_user_id, created_by
            ) VALUES (
                :title, :description, :event_type, :status, :starts_at, :ends_at, :location_text,
                :client_id, :lead_id, :service_id, :assigned_user_id, :created_by
            )'
        );
        $stmt->execute($this->payload($data) + ['created_by' => auth_id()]);

        $id = (int) $this->pdo->lastInsertId();
        $this->audit->write('operational', 'info', 'api_calendar_created', 'Evento criado via API interna.', [], auth_id(), 'calendar_event', $id);

        ApiResponse::success(['event_id' => $id], 'Evento criado.', 201);
    }

    public function update(ApiRequest $request, int $id): void
    {
        $data = $this->cleanPayload($request->input());
        ApiValidator::assert($data, ['title' => 'required|max:160', 'starts_at' => 'required']);
        $stmt = $this->pdo->prepare(
            'UPDATE calendar_events
             SET title = :title, description = :description, event_type = :event_type,
                 status = :status, starts_at = :starts_at, ends_at = :ends_at,
                 location_text = :location_text, client_id = :client_id, lead_id = :lead_id,
                 service_id = :service_id, assigned_user_id = :assigned_user_id, updated_by = :updated_by
             WHERE id = :id
               AND deleted_at IS NULL'
        );
        $stmt->execute($this->payload($data) + ['id' => $id, 'updated_by' => auth_id()]);

        ApiResponse::success(['event_id' => $id], 'Evento atualizado.');
    }

    private function payload(array $data): array
    {
        return [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'event_type' => $data['event_type'] ?? 'service',
            'status' => $data['status'] ?? 'pending',
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'location_text' => $data['location_text'] ?? null,
            'client_id' => (int) ($data['client_id'] ?? 0) ?: null,
            'lead_id' => (int) ($data['lead_id'] ?? 0) ?: null,
            'service_id' => (int) ($data['service_id'] ?? 0) ?: null,
            'assigned_user_id' => (int) ($data['assigned_user_id'] ?? 0) ?: auth_id(),
        ];
    }
}
