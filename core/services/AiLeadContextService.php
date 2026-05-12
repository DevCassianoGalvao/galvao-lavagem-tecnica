<?php

final class AiLeadContextService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function forLead(int $leadId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                l.*,
                c.name AS client_name,
                c.city AS client_city,
                c.state AS client_state,
                p.name AS property_name,
                p.address_line,
                p.neighborhood,
                p.city AS property_city,
                p.state AS property_state,
                pt.name AS property_type
             FROM leads l
             LEFT JOIN clients c ON c.id = l.client_id
             LEFT JOIN properties p ON p.id = l.property_id
             LEFT JOIN property_types pt ON pt.id = p.property_type_id
             WHERE l.id = :lead_id
             LIMIT 1'
        );
        $stmt->execute(['lead_id' => $leadId]);
        $lead = $stmt->fetch();

        if (!$lead) {
            return null;
        }

        return [
            'lead' => $lead,
            'surfaces' => $this->fetchColumnList(
                'SELECT st.name
                 FROM lead_surface_interests lsi
                 INNER JOIN surface_types st ON st.id = lsi.surface_type_id
                 WHERE lsi.lead_id = :lead_id',
                $leadId
            ),
            'dirt_types' => $this->fetchColumnList(
                'SELECT dt.name
                 FROM lead_dirt_types ldt
                 INNER JOIN dirt_types dt ON dt.id = ldt.dirt_type_id
                 WHERE ldt.lead_id = :lead_id',
                $leadId
            ),
            'notes' => $this->fetchColumnList(
                'SELECT body FROM notes WHERE lead_id = :lead_id ORDER BY created_at DESC LIMIT 8',
                $leadId
            ),
        ];
    }

    private function fetchColumnList(string $sql, int $leadId): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['lead_id' => $leadId]);

        return array_values(array_filter(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
    }
}
