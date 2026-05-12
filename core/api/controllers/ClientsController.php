<?php

final class ClientsController extends ApiController
{
    public function index(ApiRequest $request): void
    {
        $search = clean_text($request->query['q'] ?? '');
        $sql = 'SELECT id, name, email, phone, whatsapp, neighborhood, city, state, status, created_at
                FROM clients
                WHERE deleted_at IS NULL';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (name LIKE :q OR phone LIKE :q OR email LIKE :q OR neighborhood LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY created_at DESC LIMIT :limit';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $this->limit($request), PDO::PARAM_INT);
        $stmt->execute();

        ApiResponse::success(['clients' => $stmt->fetchAll()]);
    }

    public function show(ApiRequest $request, int $id): void
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM clients
             WHERE id = :id
               AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $client = $stmt->fetch();

        if (!$client) {
            ApiResponse::error('Cliente nao encontrado.', 404);
        }

        ApiResponse::success(['client' => $client]);
    }

    public function store(ApiRequest $request): void
    {
        $data = $this->cleanPayload($request->input());
        ApiValidator::assert($data, [
            'name' => 'required|max:140',
            'phone' => 'required|max:40',
            'email' => 'email|max:160',
        ]);

        $stmt = $this->pdo->prepare(
            'INSERT INTO clients (
                owner_user_id, name, email, phone, whatsapp, preferred_contact,
                address_line, neighborhood, city, state, source, status, created_by
            ) VALUES (
                :owner_user_id, :name, :email, :phone, :whatsapp, :preferred_contact,
                :address_line, :neighborhood, :city, :state, :source, :status, :created_by
            )'
        );
        $stmt->execute([
            'owner_user_id' => auth_id(),
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'whatsapp' => $data['whatsapp'] ?? $data['phone'],
            'preferred_contact' => $data['preferred_contact'] ?? 'whatsapp',
            'address_line' => $data['address_line'] ?? null,
            'neighborhood' => $data['neighborhood'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'source' => $data['source'] ?? 'api',
            'status' => $data['status'] ?? 'prospect',
            'created_by' => auth_id(),
        ]);

        $id = (int) $this->pdo->lastInsertId();
        $this->audit->write('admin', 'info', 'api_client_created', 'Cliente criado via API interna.', [], auth_id(), 'client', $id);

        ApiResponse::success(['client_id' => $id], 'Cliente criado.', 201);
    }

    public function update(ApiRequest $request, int $id): void
    {
        $data = $this->cleanPayload($request->input());
        ApiValidator::assert($data, [
            'name' => 'required|max:140',
            'phone' => 'required|max:40',
            'email' => 'email|max:160',
        ]);

        $stmt = $this->pdo->prepare(
            'UPDATE clients
             SET name = :name, email = :email, phone = :phone, whatsapp = :whatsapp,
                 neighborhood = :neighborhood, city = :city, state = :state,
                 status = :status, updated_by = :updated_by
             WHERE id = :id
               AND deleted_at IS NULL'
        );
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'whatsapp' => $data['whatsapp'] ?? $data['phone'],
            'neighborhood' => $data['neighborhood'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'status' => $data['status'] ?? 'prospect',
            'updated_by' => auth_id(),
        ]);

        $this->audit->write('admin', 'info', 'api_client_updated', 'Cliente atualizado via API interna.', [], auth_id(), 'client', $id);

        ApiResponse::success(['client_id' => $id], 'Cliente atualizado.');
    }
}
