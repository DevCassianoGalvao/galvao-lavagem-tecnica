<?php

final class MvpService
{
    private const STATUSES = ['novo', 'contato_realizado', 'orcamento_enviado', 'fechado'];

    public function __construct(private PDO $pdo)
    {
        $this->ensureTables();
    }

    public static function neighborhoods(): array
    {
        return [
            'Alto dos 50', 'Amparo', 'Bela Vista', 'Braunes', 'Campo do Coelho', 'Cardinot',
            'Cascatinha', 'Catarcione', 'Centro', 'Chácara do Paraíso', 'Cônego',
            'Conselheiro Paulino', 'Cordoeira', 'Córrego D’Antas', 'Debossan', 'Duas Pedras',
            'Galdinópolis', 'Granja Mimosa', 'Granja Spinelli', 'Jardinlândia', 'Lumiar',
            'Macaé de Cima', 'Maria Teresa', 'Mury', 'Nova Suíça', 'Olaria', 'Paissandu',
            'Parque das Flores', 'Parque São Clemente', 'Perissê', 'Ponte da Saudade',
            'Ponte dos Alemães', 'Ponte Preta', 'Prado', 'Riograndina', 'Rui Sanglard',
            'Salinas', 'Santa Bernadete', 'Santa Cruz', 'Santa Terezinha', 'Santo André',
            'São Cristóvão', 'São Geraldo', 'São Pedro da Serra', 'Suspiro',
            'Theodoro de Oliveira', 'Tinguely', 'Vale dos Pinheiros', 'Vargem Alta',
            'Vargem Grande', 'Varginha', 'Vila Amélia', 'Vila Guarani', 'Vila Nova',
            'Vila Rica', 'Vilage', 'Ypu',
        ];
    }

    public static function statuses(): array
    {
        return self::STATUSES;
    }

    public function createLead(array $data, array $files): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO mvp_leads (
                name, phone, email, address, street, address_number, address_complement, neighborhood, city, cep, property_type, surfaces_json,
                dirt_json, area_size, square_meters, access_difficulty, height_type,
                height_approx, cleaning_frequency, priority_json, notes, status, created_at, updated_at
            ) VALUES (
                :name, :phone, :email, :address, :street, :address_number, :address_complement, :neighborhood, :city, :cep, :property_type, :surfaces_json,
                :dirt_json, :area_size, :square_meters, :access_difficulty, :height_type,
                :height_approx, :cleaning_frequency, :priority_json, :notes, "novo", NOW(), NOW()
            )'
        );

        $street = clean_text($data['street'] ?? $data['address_street'] ?? '');
        $number = clean_text($data['address_number'] ?? $data['number'] ?? '');
        $complement = clean_text($data['address_complement'] ?? $data['complement'] ?? '');
        $neighborhood = clean_text($data['neighborhood'] ?? $data['city'] ?? '');
        $city = clean_text($data['city_name'] ?? 'Nova Friburgo');
        $cep = clean_text($data['cep'] ?? $data['postal_code'] ?? '');
        $address = clean_text($data['address'] ?? '');

        if ($address === '') {
            $address = implode(', ', array_filter([$street, $number, $complement, $neighborhood, $city, $cep]));
        }

        $stmt->execute([
            'name' => clean_text($data['name'] ?? ''),
            'phone' => clean_text($data['phone'] ?? ''),
            'email' => filter_var((string) ($data['email'] ?? ''), FILTER_SANITIZE_EMAIL),
            'address' => $address,
            'street' => $street,
            'address_number' => $number,
            'address_complement' => $complement,
            'neighborhood' => $neighborhood,
            'city' => $city,
            'cep' => $cep,
            'property_type' => clean_text($data['property_type'] ?? $data['property'] ?? ''),
            'surfaces_json' => $this->jsonList($data['surfaces'] ?? []),
            'dirt_json' => $this->jsonList($data['dirt_types'] ?? $data['dirt'] ?? []),
            'area_size' => clean_text($data['area_size'] ?? $data['size'] ?? ''),
            'square_meters' => clean_text($data['square_meters'] ?? $data['area'] ?? ''),
            'access_difficulty' => clean_text($data['access_difficulty'] ?? $data['access'] ?? ''),
            'height_type' => clean_text($data['height'] ?? ''),
            'height_approx' => clean_text($data['height_approx'] ?? $data['heightApprox'] ?? ''),
            'cleaning_frequency' => clean_text($data['cleaning_frequency'] ?? $data['frequency'] ?? ''),
            'priority_json' => $this->jsonList($data['priority'] ?? []),
            'notes' => clean_text($data['notes'] ?? ''),
        ]);

        $leadId = (int) $this->pdo->lastInsertId();
        $this->storeLeadImages($leadId, $files);

        return $leadId;
    }

    public function leads(array $filters = []): array
    {
        [$where, $params] = $this->leadWhere($filters);
        $stmt = $this->pdo->prepare(
            'SELECT * FROM mvp_leads ' . $where . ' ORDER BY created_at DESC LIMIT 250'
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $this->decodeLead($row);
        }

        return $rows;
    }

    public function lead(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM mvp_leads WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $lead = $stmt->fetch();

        if (!$lead) {
            return null;
        }

        $this->decodeLead($lead);
        $lead['images'] = $this->images($id);

        return $lead;
    }

    public function updateLead(int $id, string $status, string $internalNotes): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            $status = 'novo';
        }

        $stmt = $this->pdo->prepare(
            'UPDATE mvp_leads SET status = :status, internal_notes = :internal_notes, updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'status' => $status,
            'internal_notes' => clean_text($internalNotes),
        ]);
    }

    public function dashboard(): array
    {
        return [
            'total_leads' => (int) $this->pdo->query('SELECT COUNT(*) FROM mvp_leads')->fetchColumn(),
            'recent_leads' => $this->leads(),
            'neighborhoods' => $this->topFromColumn('neighborhood'),
            'surfaces' => $this->topFromJson('surfaces_json'),
            'dirt' => $this->topFromJson('dirt_json'),
            'appointments' => $this->appointments(6),
        ];
    }

    public function appointments(int $limit = 100): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, l.name AS lead_name
             FROM mvp_appointments a
             LEFT JOIN mvp_leads l ON l.id = a.lead_id
             ORDER BY a.scheduled_at ASC
             LIMIT ' . max(1, min(200, $limit))
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function appointment(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM mvp_appointments WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $appointment = $stmt->fetch();

        return $appointment ?: null;
    }

    public function saveAppointment(array $data): void
    {
        $id = (int) ($data['id'] ?? 0);
        $payload = [
            'lead_id' => $data['lead_id'] !== '' ? (int) $data['lead_id'] : null,
            'title' => clean_text($data['title'] ?? ''),
            'event_type' => clean_text($data['event_type'] ?? 'visita'),
            'scheduled_at' => str_replace('T', ' ', clean_text($data['scheduled_at'] ?? '')),
            'notes' => clean_text($data['notes'] ?? ''),
        ];

        if ($id > 0) {
            $payload['id'] = $id;
            $stmt = $this->pdo->prepare(
                'UPDATE mvp_appointments
                 SET lead_id = :lead_id, title = :title, event_type = :event_type,
                     scheduled_at = :scheduled_at, notes = :notes, updated_at = NOW()
                 WHERE id = :id'
            );
        } else {
            $stmt = $this->pdo->prepare(
                'INSERT INTO mvp_appointments (lead_id, title, event_type, scheduled_at, notes, created_at, updated_at)
                 VALUES (:lead_id, :title, :event_type, :scheduled_at, :notes, NOW(), NOW())'
            );
        }

        $stmt->execute($payload);
    }

    public function deleteAppointment(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM mvp_appointments WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function settings(): array
    {
        $rows = $this->pdo->query('SELECT setting_key, setting_value FROM mvp_settings')->fetchAll();
        $settings = [];

        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return $settings + [
            'meta_pixel' => '',
            'google_analytics' => '',
            'gtm' => '',
            'custom_head' => '',
            'custom_body' => '',
        ];
    }

    public function saveSettings(array $settings): void
    {
        $allowed = ['meta_pixel', 'google_analytics', 'gtm', 'custom_head', 'custom_body'];
        $stmt = $this->pdo->prepare(
            'INSERT INTO mvp_settings (setting_key, setting_value, updated_at)
             VALUES (:setting_key, :setting_value, NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
        );

        foreach ($allowed as $key) {
            $stmt->execute([
                'setting_key' => $key,
                'setting_value' => (string) ($settings[$key] ?? ''),
            ]);
        }
    }

    public function csvRows(): array
    {
        $rows = $this->leads();
        $csv = [];

        foreach ($rows as $lead) {
            $csv[] = [
                $lead['name'],
                $lead['phone'],
                $lead['neighborhood'],
                implode(', ', $lead['surfaces']),
                $lead['created_at'],
            ];
        }

        return $csv;
    }

    public function imagePath(int $imageId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT storage_path FROM mvp_lead_images WHERE id = :id');
        $stmt->execute(['id' => $imageId]);
        $path = $stmt->fetchColumn();

        if (!$path) {
            return null;
        }

        $absolute = STORAGE_PATH . '/' . ltrim((string) $path, '/');

        return is_file($absolute) ? $absolute : null;
    }

    public function images(int $leadId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM mvp_lead_images WHERE lead_id = :lead_id ORDER BY created_at ASC');
        $stmt->execute(['lead_id' => $leadId]);

        return $stmt->fetchAll();
    }

    private function ensureTables(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS mvp_leads (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(140) NOT NULL,
                phone VARCHAR(40) NOT NULL,
                email VARCHAR(160) NULL,
                address VARCHAR(220) NULL,
                street VARCHAR(160) NULL,
                address_number VARCHAR(30) NULL,
                address_complement VARCHAR(120) NULL,
                neighborhood VARCHAR(120) NULL,
                city VARCHAR(100) NULL,
                cep VARCHAR(20) NULL,
                property_type VARCHAR(80) NULL,
                surfaces_json JSON NULL,
                dirt_json JSON NULL,
                area_size VARCHAR(80) NULL,
                square_meters VARCHAR(80) NULL,
                access_difficulty VARCHAR(80) NULL,
                height_type VARCHAR(80) NULL,
                height_approx VARCHAR(80) NULL,
                cleaning_frequency VARCHAR(80) NULL,
                priority_json JSON NULL,
                notes TEXT NULL,
                internal_notes TEXT NULL,
                status VARCHAR(40) NOT NULL DEFAULT "novo",
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_mvp_leads_created (created_at),
                INDEX idx_mvp_leads_status (status),
                INDEX idx_mvp_leads_neighborhood (neighborhood)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $this->ensureColumn('mvp_leads', 'street', 'VARCHAR(160) NULL AFTER address');
        $this->ensureColumn('mvp_leads', 'address_number', 'VARCHAR(30) NULL AFTER street');
        $this->ensureColumn('mvp_leads', 'address_complement', 'VARCHAR(120) NULL AFTER address_number');
        $this->ensureColumn('mvp_leads', 'city', 'VARCHAR(100) NULL AFTER neighborhood');
        $this->ensureColumn('mvp_leads', 'cep', 'VARCHAR(20) NULL AFTER city');
        $this->ensureColumn('mvp_leads', 'internal_notes', 'TEXT NULL AFTER notes');
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS mvp_lead_images (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                lead_id BIGINT UNSIGNED NOT NULL,
                original_name VARCHAR(180) NOT NULL,
                storage_path VARCHAR(255) NOT NULL,
                mime_type VARCHAR(100) NOT NULL,
                size_bytes INT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                INDEX idx_mvp_images_lead (lead_id),
                CONSTRAINT fk_mvp_images_lead FOREIGN KEY (lead_id) REFERENCES mvp_leads(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS mvp_appointments (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                lead_id BIGINT UNSIGNED NULL,
                title VARCHAR(160) NOT NULL,
                event_type VARCHAR(40) NOT NULL DEFAULT "visita",
                scheduled_at DATETIME NOT NULL,
                notes TEXT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_mvp_appointments_date (scheduled_at),
                CONSTRAINT fk_mvp_appointments_lead FOREIGN KEY (lead_id) REFERENCES mvp_leads(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS mvp_settings (
                setting_key VARCHAR(80) PRIMARY KEY,
                setting_value TEXT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private function ensureColumn(string $table, string $column, string $definition): void
    {
        $safeTable = str_replace('`', '``', $table);
        $quotedColumn = $this->pdo->quote($column);
        $stmt = $this->pdo->query('SHOW COLUMNS FROM `' . $safeTable . '` LIKE ' . $quotedColumn);

        if ($stmt->fetch()) {
            return;
        }

        $this->pdo->exec('ALTER TABLE `' . $safeTable . '` ADD COLUMN `' . str_replace('`', '``', $column) . '` ' . $definition);
    }

    private function storeLeadImages(int $leadId, array $files): void
    {
        if (!isset($files['name']) || !is_array($files['name'])) {
            return;
        }

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $dir = STORAGE_PATH . '/uploads/mvp-leads/' . $leadId;

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO mvp_lead_images (lead_id, original_name, storage_path, mime_type, size_bytes, created_at)
             VALUES (:lead_id, :original_name, :storage_path, :mime_type, :size_bytes, NOW())'
        );

        foreach ($files['name'] as $index => $name) {
            if ((string) $name === '' || (int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }

            $tmp = (string) ($files['tmp_name'][$index] ?? '');
            $mime = mime_content_type($tmp) ?: '';

            if (!isset($allowed[$mime]) || getimagesize($tmp) === false) {
                continue;
            }

            $stored = hash_file('sha256', $tmp) . '.' . $allowed[$mime];
            $relative = 'uploads/mvp-leads/' . $leadId . '/' . $stored;
            $target = STORAGE_PATH . '/' . $relative;

            if (!is_file($target)) {
                move_uploaded_file($tmp, $target);
            }

            $stmt->execute([
                'lead_id' => $leadId,
                'original_name' => clean_text($name),
                'storage_path' => $relative,
                'mime_type' => $mime,
                'size_bytes' => filesize($target) ?: 0,
            ]);
        }
    }

    private function jsonList(mixed $value): string
    {
        $items = is_array($value) ? $value : [$value];
        $items = array_values(array_filter(array_map('clean_text', $items)));

        return json_encode($items, JSON_UNESCAPED_UNICODE);
    }

    private function decodeLead(array &$lead): void
    {
        $lead['surfaces'] = json_decode((string) ($lead['surfaces_json'] ?? '[]'), true) ?: [];
        $lead['dirt'] = json_decode((string) ($lead['dirt_json'] ?? '[]'), true) ?: [];
        $lead['priority'] = json_decode((string) ($lead['priority_json'] ?? '[]'), true) ?: [];
    }

    private function leadWhere(array $filters): array
    {
        $where = [];
        $params = [];

        foreach (['neighborhood', 'status'] as $field) {
            if (!empty($filters[$field])) {
                $where[] = $field . ' = :' . $field;
                $params[$field] = clean_text($filters[$field]);
            }
        }

        if (!empty($filters['date'])) {
            $where[] = 'DATE(created_at) = :date';
            $params['date'] = clean_text($filters['date']);
        }

        foreach (['surface' => 'surfaces_json', 'dirt' => 'dirt_json'] as $key => $column) {
            if (!empty($filters[$key])) {
                $where[] = 'JSON_CONTAINS(' . $column . ', :json_' . $key . ')';
                $params['json_' . $key] = json_encode(clean_text($filters[$key]), JSON_UNESCAPED_UNICODE);
            }
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
    }

    private function topFromColumn(string $column): array
    {
        $stmt = $this->pdo->query(
            'SELECT ' . $column . ' AS label, COUNT(*) AS total
             FROM mvp_leads
             WHERE ' . $column . ' IS NOT NULL AND ' . $column . ' <> ""
             GROUP BY ' . $column . '
             ORDER BY total DESC
             LIMIT 8'
        );

        return $stmt->fetchAll();
    }

    private function topFromJson(string $column): array
    {
        $rows = $this->pdo->query('SELECT ' . $column . ' FROM mvp_leads')->fetchAll();
        $counts = [];

        foreach ($rows as $row) {
            foreach ((json_decode((string) $row[$column], true) ?: []) as $item) {
                $counts[$item] = ($counts[$item] ?? 0) + 1;
            }
        }

        arsort($counts);
        $top = [];

        foreach (array_slice($counts, 0, 8, true) as $label => $total) {
            $top[] = ['label' => $label, 'total' => $total];
        }

        return $top;
    }
}

