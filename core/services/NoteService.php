<?php

final class NoteService
{
    public const TYPES = [
        'attendance' => 'Atendimento',
        'operational' => 'Operacional',
        'financial' => 'Financeiro',
        'technical' => 'Tecnico',
        'commercial' => 'Comercial',
        'general' => 'Geral',
    ];

    public function __construct(private PDO $pdo)
    {
    }

    public function timeline(array $filters = [], int $limit = 80): array
    {
        $sql = 'SELECT n.*, u.name AS author_name, c.name AS client_name,
                       l.name AS lead_name, s.title AS service_title
                FROM notes n
                LEFT JOIN users u ON u.id = n.author_user_id
                LEFT JOIN clients c ON c.id = n.client_id
                LEFT JOIN leads l ON l.id = n.lead_id
                LEFT JOIN services s ON s.id = n.service_id
                WHERE n.deleted_at IS NULL';
        $params = [];

        if (($filters['query'] ?? '') !== '') {
            $sql .= ' AND (n.title LIKE :query OR n.body LIKE :query OR c.name LIKE :query OR l.name LIKE :query OR s.title LIKE :query)';
            $params['query'] = '%' . $filters['query'] . '%';
        }

        if (($filters['note_type'] ?? '') !== '') {
            $sql .= ' AND n.note_type = :note_type';
            $params['note_type'] = $filters['note_type'];
        }

        if (($filters['client_id'] ?? 0) > 0) {
            $sql .= ' AND n.client_id = :client_id';
            $params['client_id'] = (int) $filters['client_id'];
        }

        if (($filters['service_id'] ?? 0) > 0) {
            $sql .= ' AND n.service_id = :service_id';
            $params['service_id'] = (int) $filters['service_id'];
        }

        if (($filters['lead_id'] ?? 0) > 0) {
            $sql .= ' AND n.lead_id = :lead_id';
            $params['lead_id'] = (int) $filters['lead_id'];
        }

        $sql .= ' ORDER BY n.is_pinned DESC, n.created_at DESC LIMIT :limit';
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function create(array $data, ?int $userId = null): int
    {
        $body = clean_text($data['body'] ?? '');

        if ($body === '') {
            throw new InvalidArgumentException('A observacao nao pode ficar vazia.');
        }

        $type = clean_text($data['note_type'] ?? 'general');

        if (!array_key_exists($type, self::TYPES) && !in_array($type, ['timeline', 'system'], true)) {
            $type = 'general';
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO notes (
                client_id, lead_id, property_id, service_id, surface_id, author_user_id,
                note_type, title, body, tags_json, is_pinned
            ) VALUES (
                :client_id, :lead_id, :property_id, :service_id, :surface_id, :author_user_id,
                :note_type, :title, :body, :tags_json, :is_pinned
            )'
        );
        $stmt->execute([
            'client_id' => $this->nullableInt($data['client_id'] ?? null),
            'lead_id' => $this->nullableInt($data['lead_id'] ?? null),
            'property_id' => $this->nullableInt($data['property_id'] ?? null),
            'service_id' => $this->nullableInt($data['service_id'] ?? null),
            'surface_id' => $this->nullableInt($data['surface_id'] ?? null),
            'author_user_id' => $userId,
            'note_type' => $type,
            'title' => clean_text($data['title'] ?? ''),
            'body' => $body,
            'tags_json' => json_encode($this->tags($data['tags'] ?? ''), JSON_UNESCAPED_UNICODE),
            'is_pinned' => isset($data['is_pinned']) ? 1 : 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function togglePinned(int $noteId, ?int $userId = null): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE notes
             SET is_pinned = IF(is_pinned = 1, 0, 1)
             WHERE id = :id
               AND deleted_at IS NULL'
        );
        $stmt->execute(['id' => $noteId]);
    }

    public function clients(): array
    {
        return $this->pdo->query('SELECT id, name FROM clients WHERE deleted_at IS NULL ORDER BY name LIMIT 140')->fetchAll();
    }

    public function services(): array
    {
        return $this->pdo->query('SELECT id, title FROM services WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 140')->fetchAll();
    }

    public function leads(): array
    {
        return $this->pdo->query('SELECT id, name FROM leads WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 140')->fetchAll();
    }

    private function tags(string|array $tags): array
    {
        if (is_array($tags)) {
            return array_values(array_filter(array_map('clean_text', $tags)));
        }

        return array_values(array_filter(array_map('clean_text', explode(',', $tags))));
    }

    private function nullableInt(mixed $value): ?int
    {
        $value = (int) $value;

        return $value > 0 ? $value : null;
    }
}
