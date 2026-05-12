<?php

final class DashboardAnalyticsService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function build(array $fallback = []): array
    {
        $data = [
            'kpis' => $this->kpis(),
            'charts' => [
                'districts' => $this->districts(),
                'surfaces' => $this->surfaces(),
                'dirt' => $this->dirtTypes(),
                'conversion' => $this->conversion(),
                'recurrence' => $this->recurrence(),
            ],
            'timeline' => $this->timeline(),
            'calendar' => $this->calendar(),
            'activities' => $this->activities(),
            'recent_leads' => $this->recentLeads(),
            'followups' => $this->followups(),
            'recurrences' => $this->recurrences(),
        ];

        return $this->withFallbacks($data, $fallback);
    }

    private function kpis(): array
    {
        return [
            [
                'label' => 'Leads do mes',
                'value' => (string) $this->count('SELECT COUNT(*) FROM leads WHERE created_at >= DATE_FORMAT(CURDATE(), "%Y-%m-01") AND deleted_at IS NULL'),
                'delta' => 'entrada qualificada',
                'tone' => 'gold',
            ],
            [
                'label' => 'Servicos concluidos',
                'value' => (string) $this->count('SELECT COUNT(*) FROM services WHERE status = "completed" AND deleted_at IS NULL'),
                'delta' => 'operacao',
                'tone' => 'green',
            ],
            [
                'label' => 'Recorrencia',
                'value' => $this->recurrenceRate(),
                'delta' => 'ciclo preventivo',
                'tone' => 'blue',
            ],
            [
                'label' => 'Clientes ativos',
                'value' => (string) $this->count('SELECT COUNT(*) FROM clients WHERE status = "active" AND deleted_at IS NULL'),
                'delta' => 'base viva',
                'tone' => 'green',
            ],
            [
                'label' => 'Propostas enviadas',
                'value' => (string) $this->count('SELECT COUNT(*) FROM leads WHERE status = "proposal" AND deleted_at IS NULL'),
                'delta' => 'pipeline',
                'tone' => 'gold',
            ],
        ];
    }

    private function districts(): array
    {
        return $this->queryChart(
            'SELECT COALESCE(NULLIF(neighborhood, ""), "Nao informado") AS label, COUNT(*) AS value
             FROM clients
             WHERE deleted_at IS NULL
             GROUP BY label
             ORDER BY value DESC
             LIMIT 6'
        );
    }

    private function surfaces(): array
    {
        return $this->queryChart(
            'SELECT st.name AS label, COUNT(*) AS value
             FROM lead_surface_interests lsi
             INNER JOIN surface_types st ON st.id = lsi.surface_type_id
             GROUP BY st.id, st.name
             ORDER BY value DESC
             LIMIT 6'
        );
    }

    private function dirtTypes(): array
    {
        return $this->queryChart(
            'SELECT dt.name AS label, COUNT(*) AS value
             FROM lead_dirt_types ldt
             INNER JOIN dirt_types dt ON dt.id = ldt.dirt_type_id
             GROUP BY dt.id, dt.name
             ORDER BY value DESC
             LIMIT 6'
        );
    }

    private function conversion(): array
    {
        return $this->queryChart(
            'SELECT status AS label, COUNT(*) AS value
             FROM leads
             WHERE deleted_at IS NULL
             GROUP BY status
             ORDER BY value DESC'
        );
    }

    private function recurrence(): array
    {
        return $this->queryChart(
            'SELECT event_type AS label, COUNT(*) AS value
             FROM calendar_events
             WHERE deleted_at IS NULL
             GROUP BY event_type
             ORDER BY value DESC'
        );
    }

    private function timeline(): array
    {
        return $this->queryRows(
            'SELECT action AS type, message AS title, channel AS body, created_at AS time
             FROM logs
             ORDER BY created_at DESC
             LIMIT 6'
        );
    }

    private function calendar(): array
    {
        return $this->queryRows(
            'SELECT title, event_type AS category, status, location_text AS location,
                    DATE_FORMAT(starts_at, "%d/%m") AS day,
                    DATE_FORMAT(starts_at, "%H:%i") AS time
             FROM calendar_events
             WHERE starts_at >= NOW() AND deleted_at IS NULL
             ORDER BY starts_at ASC
             LIMIT 5'
        );
    }

    private function activities(): array
    {
        return $this->queryRows(
            'SELECT level, action, message, created_at
             FROM logs
             ORDER BY created_at DESC
             LIMIT 7'
        );
    }

    private function recentLeads(): array
    {
        return $this->queryRows(
            'SELECT name AS client, phone, status, score, created_at,
                    COALESCE(email, "Sem e-mail") AS detail
             FROM leads
             WHERE deleted_at IS NULL
             ORDER BY created_at DESC
             LIMIT 5'
        );
    }

    private function followups(): array
    {
        return $this->queryRows(
            'SELECT f.title, f.status, DATE_FORMAT(f.due_at, "%d/%m %H:%i") AS due_at,
                    c.name AS client_name
             FROM follow_ups f
             LEFT JOIN clients c ON c.id = f.client_id
             WHERE f.status = "pending"
             ORDER BY f.due_at ASC
             LIMIT 5'
        );
    }

    private function recurrences(): array
    {
        return $this->queryRows(
            'SELECT rp.next_due_at, rp.interval_months, rp.reason,
                    DATE_FORMAT(rp.next_due_at, "%d/%m/%Y") AS due_at,
                    DATEDIFF(rp.next_due_at, NOW()) AS days_left,
                    c.name AS client_name,
                    COALESCE(p.neighborhood, "Local a confirmar") AS neighborhood,
                    COALESCE(p.city, "") AS city
             FROM recurrence_plans rp
             INNER JOIN clients c ON c.id = rp.client_id
             LEFT JOIN properties p ON p.id = rp.property_id
             WHERE rp.status = "active"
               AND rp.next_due_at >= NOW()
             ORDER BY rp.next_due_at ASC
             LIMIT 6'
        );
    }

    private function count(string $sql): int
    {
        try {
            return (int) $this->pdo->query($sql)->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }

    private function queryChart(string $sql): array
    {
        $rows = $this->queryRows($sql);

        return array_map(static fn (array $row): array => [
            'label' => (string) ($row['label'] ?? ''),
            'value' => (int) ($row['value'] ?? 0),
        ], $rows);
    }

    private function queryRows(string $sql): array
    {
        try {
            return $this->pdo->query($sql)->fetchAll();
        } catch (Throwable) {
            return [];
        }
    }

    private function recurrenceRate(): string
    {
        $clients = max(1, $this->count('SELECT COUNT(*) FROM clients WHERE deleted_at IS NULL'));
        $recurring = $this->count('SELECT COUNT(DISTINCT client_id) FROM follow_ups WHERE status = "pending" AND client_id IS NOT NULL');

        return (int) round(($recurring / $clients) * 100) . '%';
    }

    private function withFallbacks(array $data, array $fallback): array
    {
        if ($this->allKpisEmpty($data['kpis'])) {
            $data['kpis'] = $fallback['kpis'] ?? $this->fallbackKpis();
        }

        $data['charts']['districts'] = $data['charts']['districts'] ?: $this->fallbackDistricts();
        $data['charts']['surfaces'] = $data['charts']['surfaces'] ?: $this->fallbackSurfaces();
        $data['charts']['dirt'] = $data['charts']['dirt'] ?: $this->fallbackDirt();
        $data['charts']['conversion'] = $data['charts']['conversion'] ?: $this->fallbackConversion();
        $data['charts']['recurrence'] = $data['charts']['recurrence'] ?: $this->fallbackRecurrence();
        $data['timeline'] = $data['timeline'] ?: ($fallback['timeline'] ?? []);
        $data['calendar'] = $data['calendar'] ?: ($fallback['calendar'] ?? []);
        $data['activities'] = $data['activities'] ?: $this->fallbackActivities();
        $data['recent_leads'] = $data['recent_leads'] ?: ($fallback['recent_leads'] ?? []);
        $data['followups'] = $data['followups'] ?: $this->fallbackFollowups();
        $data['recurrences'] = $data['recurrences'] ?: $this->fallbackRecurrences();

        return $data;
    }

    private function allKpisEmpty(array $kpis): bool
    {
        foreach ($kpis as $kpi) {
            if ((string) $kpi['value'] !== '0' && (string) $kpi['value'] !== '0%') {
                return false;
            }
        }

        return true;
    }

    private function fallbackKpis(): array
    {
        return [
            ['label' => 'Leads do mes', 'value' => '42', 'delta' => '+18% vs. mes anterior', 'tone' => 'gold'],
            ['label' => 'Servicos concluidos', 'value' => '18', 'delta' => 'execucao premium', 'tone' => 'green'],
            ['label' => 'Recorrencia', 'value' => '31%', 'delta' => '+6% em ciclos', 'tone' => 'blue'],
            ['label' => 'Clientes ativos', 'value' => '128', 'delta' => 'base monitorada', 'tone' => 'green'],
            ['label' => 'Propostas enviadas', 'value' => '24', 'delta' => 'pipeline quente', 'tone' => 'gold'],
        ];
    }

    private function fallbackDistricts(): array
    {
        return [
            ['label' => 'Jardim Europa', 'value' => 18],
            ['label' => 'Moema', 'value' => 14],
            ['label' => 'Brooklin', 'value' => 12],
            ['label' => 'Pinheiros', 'value' => 9],
            ['label' => 'Itaim Bibi', 'value' => 8],
        ];
    }

    private function fallbackSurfaces(): array
    {
        return [
            ['label' => 'Garagem', 'value' => 31],
            ['label' => 'Muro', 'value' => 27],
            ['label' => 'Fachada', 'value' => 21],
            ['label' => 'Pedra', 'value' => 18],
            ['label' => 'Piscina', 'value' => 12],
        ];
    }

    private function fallbackDirt(): array
    {
        return [
            ['label' => 'Lodo', 'value' => 34],
            ['label' => 'Musgo', 'value' => 26],
            ['label' => 'Manchas', 'value' => 19],
            ['label' => 'Mofo', 'value' => 13],
            ['label' => 'Ferrugem', 'value' => 7],
        ];
    }

    private function fallbackConversion(): array
    {
        return [
            ['label' => 'Novo', 'value' => 42],
            ['label' => 'Diagnostico', 'value' => 28],
            ['label' => 'Proposta', 'value' => 24],
            ['label' => 'Agendado', 'value' => 16],
            ['label' => 'Concluido', 'value' => 18],
        ];
    }

    private function fallbackRecurrence(): array
    {
        return [
            ['label' => 'Preventivo', 'value' => 31],
            ['label' => 'Follow-up', 'value' => 22],
            ['label' => 'Servico', 'value' => 18],
            ['label' => 'Visita', 'value' => 11],
        ];
    }

    private function fallbackActivities(): array
    {
        return [
            ['level' => 'info', 'action' => 'ia_textual', 'message' => 'Resumo tecnico gerado para lead premium.', 'created_at' => 'Hoje, 09:44'],
            ['level' => 'warning', 'action' => 'follow_up', 'message' => 'Retorno comercial prioritario em aberto.', 'created_at' => 'Hoje, 10:15'],
            ['level' => 'info', 'action' => 'kanban', 'message' => 'Lead movido para proposta enviada.', 'created_at' => 'Ontem, 17:10'],
        ];
    }

    private function fallbackFollowups(): array
    {
        return [
            ['title' => 'Apresentar plano tecnico Villa Serena', 'status' => 'pending', 'due_at' => '08/05 14:30', 'client_name' => 'Condominio Villa Serena'],
            ['title' => 'Confirmar visita Marina Albuquerque', 'status' => 'pending', 'due_at' => '09/05 09:00', 'client_name' => 'Marina Albuquerque'],
            ['title' => 'Reativar lead Atelier Brava', 'status' => 'pending', 'due_at' => '10/05 11:00', 'client_name' => 'Atelier Brava'],
        ];
    }

    private function fallbackRecurrences(): array
    {
        return [
            ['client_name' => 'Marina Albuquerque', 'neighborhood' => 'Jardim Europa', 'city' => 'Nova Friburgo', 'due_at' => '07/11/2026', 'days_left' => 184, 'interval_months' => 6],
            ['client_name' => 'Residencial Arvoredo', 'neighborhood' => 'Brooklin', 'city' => 'Nova Friburgo', 'due_at' => '12/11/2026', 'days_left' => 189, 'interval_months' => 6],
            ['client_name' => 'Condominio Villa Serena', 'neighborhood' => 'Alto da Boa Vista', 'city' => 'Nova Friburgo', 'due_at' => '21/11/2026', 'days_left' => 198, 'interval_months' => 6],
        ];
    }
}
