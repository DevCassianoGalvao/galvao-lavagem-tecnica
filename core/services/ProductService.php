<?php

final class ProductService
{
    private const CATEGORIES = [
        'detergente alcalino',
        'desengordurante',
        'limpeza neutra',
        'removedor de lodo',
        'removedor de manchas',
        'protector de superficie',
        'acabamento tecnico',
    ];

    public function __construct(private PDO $pdo)
    {
    }

    public static function categories(): array
    {
        return self::CATEGORIES;
    }

    public function catalog(string $query = ''): array
    {
        $sql = 'SELECT p.*,
                       GROUP_CONCAT(DISTINCT st.name ORDER BY st.name SEPARATOR ", ") AS surfaces,
                       COUNT(DISTINCT pu.id) AS usage_count,
                       MAX(pu.used_at) AS last_used_at
                FROM products p
                LEFT JOIN product_surface_compatibilities psc ON psc.product_id = p.id
                LEFT JOIN surface_types st ON st.id = psc.surface_type_id
                LEFT JOIN product_usages pu ON pu.product_id = p.id
                WHERE p.deleted_at IS NULL';
        $params = [];

        if ($query !== '') {
            $sql .= ' AND (p.name LIKE :query OR p.category LIKE :query OR p.description LIKE :query)';
            $params['query'] = '%' . $query . '%';
        }

        $sql .= ' GROUP BY p.id ORDER BY p.is_active DESC, p.name ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function recentUsages(int $limit = 12): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT pu.*, p.name AS product_name, p.category, c.name AS client_name,
                    srv.title AS service_title, s.name AS surface_name, st.name AS surface_type
             FROM product_usages pu
             INNER JOIN products p ON p.id = pu.product_id
             INNER JOIN services srv ON srv.id = pu.service_id
             LEFT JOIN clients c ON c.id = pu.client_id
             LEFT JOIN surfaces s ON s.id = pu.surface_id
             LEFT JOIN surface_types st ON st.id = s.surface_type_id
             ORDER BY pu.used_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function surfaceTypes(): array
    {
        return $this->pdo->query('SELECT id, name, slug FROM surface_types ORDER BY name')->fetchAll();
    }

    public function clients(): array
    {
        return $this->pdo->query('SELECT id, name FROM clients WHERE deleted_at IS NULL ORDER BY name LIMIT 120')->fetchAll();
    }

    public function services(): array
    {
        return $this->pdo->query('SELECT id, title, client_id FROM services WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 120')->fetchAll();
    }

    public function surfaces(): array
    {
        return $this->pdo->query(
            'SELECT s.id, s.name, st.name AS surface_type, p.client_id
             FROM surfaces s
             INNER JOIN surface_types st ON st.id = s.surface_type_id
             INNER JOIN properties p ON p.id = s.property_id
             WHERE s.deleted_at IS NULL
             ORDER BY s.created_at DESC
             LIMIT 160'
        )->fetchAll();
    }

    public function saveProduct(array $data, ?int $userId = null): int
    {
        $name = clean_text($data['name'] ?? '');

        if ($name === '') {
            throw new InvalidArgumentException('Nome do produto e obrigatorio.');
        }

        $productId = (int) ($data['product_id'] ?? 0);
        $payload = [
            'name' => $name,
            'sku' => clean_text($data['sku'] ?? ''),
            'category' => clean_text($data['category'] ?? ''),
            'description' => clean_text($data['description'] ?? ''),
            'dilution' => clean_text($data['dilution'] ?? ''),
            'application_notes' => clean_text($data['application_notes'] ?? ''),
            'unit' => clean_text($data['unit'] ?? 'un') ?: 'un',
            'safety_notes' => clean_text($data['safety_notes'] ?? ''),
            'is_active' => isset($data['is_active']) ? 1 : 0,
        ];

        if ($productId > 0) {
            $stmt = $this->pdo->prepare(
                'UPDATE products
                 SET name = :name, sku = :sku, category = :category, description = :description,
                     dilution = :dilution, application_notes = :application_notes,
                     unit = :unit, safety_notes = :safety_notes, is_active = :is_active
                 WHERE id = :id AND deleted_at IS NULL'
            );
            $stmt->execute($payload + ['id' => $productId]);
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO products (
                    name, sku, category, description, dilution, application_notes,
                    unit, safety_notes, is_active
                ) VALUES (
                    :name, :sku, :category, :description, :dilution, :application_notes,
                    :unit, :safety_notes, :is_active
                )'
            );
            $stmt->execute($payload);
            $productId = (int) $this->pdo->lastInsertId();
        }

        $this->syncSurfaces($productId, $data['surface_type_ids'] ?? []);

        return $productId;
    }

    public function registerUsage(array $data, ?int $userId = null): int
    {
        $productId = (int) ($data['product_id'] ?? 0);
        $serviceId = (int) ($data['service_id'] ?? 0);

        if ($productId <= 0 || $serviceId <= 0) {
            throw new InvalidArgumentException('Produto e servico sao obrigatorios.');
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO product_usages (
                product_id, service_id, client_id, surface_id, quantity, unit,
                dilution_used, result_summary, notes, used_by
            ) VALUES (
                :product_id, :service_id, :client_id, :surface_id, :quantity, :unit,
                :dilution_used, :result_summary, :notes, :used_by
            )'
        );
        $stmt->execute([
            'product_id' => $productId,
            'service_id' => $serviceId,
            'client_id' => (int) ($data['client_id'] ?? 0) ?: null,
            'surface_id' => (int) ($data['surface_id'] ?? 0) ?: null,
            'quantity' => (float) ($data['quantity'] ?? 0),
            'unit' => clean_text($data['unit'] ?? 'ml') ?: 'ml',
            'dilution_used' => clean_text($data['dilution_used'] ?? ''),
            'result_summary' => clean_text($data['result_summary'] ?? ''),
            'notes' => clean_text($data['notes'] ?? ''),
            'used_by' => $userId,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function syncSurfaces(int $productId, array|string $surfaceIds): void
    {
        $surfaceIds = is_array($surfaceIds) ? $surfaceIds : [$surfaceIds];
        $surfaceIds = array_values(array_unique(array_filter(array_map('intval', $surfaceIds))));

        $delete = $this->pdo->prepare('DELETE FROM product_surface_compatibilities WHERE product_id = :product_id');
        $delete->execute(['product_id' => $productId]);

        if (!$surfaceIds) {
            return;
        }

        $insert = $this->pdo->prepare(
            'INSERT INTO product_surface_compatibilities (product_id, surface_type_id, recommendation)
             VALUES (:product_id, :surface_type_id, "allowed")'
        );

        foreach ($surfaceIds as $surfaceTypeId) {
            $insert->execute([
                'product_id' => $productId,
                'surface_type_id' => $surfaceTypeId,
            ]);
        }
    }
}
