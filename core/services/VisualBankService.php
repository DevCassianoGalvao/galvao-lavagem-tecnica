<?php

final class VisualBankService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function search(array $filters = [], int $limit = 60): array
    {
        $sql = 'SELECT
                    ul.id AS link_id,
                    u.id AS upload_id,
                    u.storage_path,
                    u.mime_type,
                    u.width_px,
                    u.height_px,
                    u.image_role,
                    ul.relation_type,
                    ul.caption,
                    ul.created_at,
                    c.id AS client_id,
                    c.name AS client_name,
                    COALESCE(p.neighborhood, c.neighborhood, "Nao informado") AS neighborhood,
                    p.city,
                    s.id AS surface_id,
                    s.name AS surface_name,
                    s.access_difficulty,
                    st.name AS surface_type,
                    srv.id AS service_id,
                    srv.title AS service_title,
                    GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ", ") AS tags,
                    GROUP_CONCAT(DISTINCT dt.name ORDER BY dt.name SEPARATOR ", ") AS dirt_types,
                    GROUP_CONCAT(DISTINCT pr.name ORDER BY pr.name SEPARATOR ", ") AS products
                FROM upload_links ul
                INNER JOIN uploads u ON u.id = ul.upload_id
                LEFT JOIN clients c ON c.id = ul.client_id
                LEFT JOIN properties p ON p.id = ul.property_id
                LEFT JOIN surfaces s ON s.id = ul.surface_id
                LEFT JOIN surface_types st ON st.id = s.surface_type_id
                LEFT JOIN services srv ON srv.id = ul.service_id
                LEFT JOIN client_tags ct ON ct.client_id = c.id
                LEFT JOIN tags t ON t.id = ct.tag_id
                LEFT JOIN lead_dirt_types ldt ON ldt.lead_id = ul.lead_id
                LEFT JOIN dirt_types dt ON dt.id = ldt.dirt_type_id
                LEFT JOIN product_usages pu ON pu.service_id = ul.service_id AND (pu.surface_id = ul.surface_id OR pu.surface_id IS NULL)
                LEFT JOIN products pr ON pr.id = pu.product_id
                WHERE u.deleted_at IS NULL
                  AND u.status IN ("active", "temporary")
                  AND u.mime_type LIKE "image/%"';

        $params = [];

        if (($filters['query'] ?? '') !== '') {
            $sql .= ' AND (c.name LIKE :query OR p.neighborhood LIKE :query OR s.name LIKE :query OR st.name LIKE :query OR ul.caption LIKE :query)';
            $params['query'] = '%' . $filters['query'] . '%';
        }

        if (($filters['surface'] ?? '') !== '') {
            $sql .= ' AND (st.slug = :surface OR st.name = :surface_name)';
            $params['surface'] = $filters['surface'];
            $params['surface_name'] = $filters['surface'];
        }

        if (($filters['neighborhood'] ?? '') !== '') {
            $sql .= ' AND (p.neighborhood = :neighborhood OR c.neighborhood = :neighborhood)';
            $params['neighborhood'] = $filters['neighborhood'];
        }

        if (($filters['difficulty'] ?? '') !== '') {
            $sql .= ' AND s.access_difficulty = :difficulty';
            $params['difficulty'] = $filters['difficulty'];
        }

        if (($filters['tag'] ?? '') !== '') {
            $sql .= ' AND t.slug = :tag';
            $params['tag'] = $filters['tag'];
        }

        if (($filters['dirt'] ?? '') !== '') {
            $sql .= ' AND dt.slug = :dirt';
            $params['dirt'] = $filters['dirt'];
        }

        $sql .= ' GROUP BY ul.id, u.id, c.id, p.id, s.id, st.id, srv.id
                  ORDER BY ul.created_at DESC
                  LIMIT :limit';

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function beforeAfterPairs(int $limit = 12): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                before_link.id AS before_link_id,
                after_link.id AS after_link_id,
                before_upload.id AS before_upload_id,
                after_upload.id AS after_upload_id,
                c.name AS client_name,
                COALESCE(p.neighborhood, c.neighborhood, "Nao informado") AS neighborhood,
                COALESCE(s.name, st.name, "Superficie tecnica") AS surface_name,
                srv.title AS service_title,
                after_link.created_at
             FROM upload_links before_link
             INNER JOIN upload_links after_link
                ON after_link.service_id = before_link.service_id
               AND after_link.relation_type IN ("after", "ai_result")
             INNER JOIN uploads before_upload ON before_upload.id = before_link.upload_id
             INNER JOIN uploads after_upload ON after_upload.id = after_link.upload_id
             LEFT JOIN clients c ON c.id = before_link.client_id
             LEFT JOIN properties p ON p.id = before_link.property_id
             LEFT JOIN surfaces s ON s.id = before_link.surface_id
             LEFT JOIN surface_types st ON st.id = s.surface_type_id
             LEFT JOIN services srv ON srv.id = before_link.service_id
             WHERE before_link.relation_type IN ("before", "ai_source", "diagnostic")
               AND before_upload.deleted_at IS NULL
               AND after_upload.deleted_at IS NULL
             GROUP BY before_link.id, after_link.id
             ORDER BY after_link.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function facets(): array
    {
        return (new CacheService('visual-bank'))->remember('facets', 600, function (): array {
            return [
                'surfaces' => $this->facetRows('SELECT name AS label, slug AS value FROM surface_types ORDER BY name'),
                'neighborhoods' => $this->facetRows(
                    'SELECT neighborhood AS label, neighborhood AS value
                     FROM properties
                     WHERE neighborhood IS NOT NULL AND neighborhood <> ""
                     GROUP BY neighborhood
                     ORDER BY neighborhood'
                ),
                'tags' => $this->facetRows('SELECT name AS label, slug AS value FROM tags ORDER BY name'),
                'dirt' => $this->facetRows('SELECT name AS label, slug AS value FROM dirt_types ORDER BY name'),
                'difficulties' => [
                    ['label' => 'Facil', 'value' => 'easy'],
                    ['label' => 'Media', 'value' => 'medium'],
                    ['label' => 'Dificil', 'value' => 'hard'],
                ],
            ];
        });
    }

    public function surfaceHistory(int $surfaceId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT n.title, n.body, n.created_at, srv.title AS service_title
             FROM notes n
             LEFT JOIN services srv ON srv.id = n.service_id
             WHERE n.surface_id = :surface_id
               AND n.deleted_at IS NULL
             ORDER BY n.created_at DESC
             LIMIT 12'
        );
        $stmt->execute(['surface_id' => $surfaceId]);

        return $stmt->fetchAll();
    }

    public function uploadPath(int $uploadId, string $size = 'original'): ?array
    {
        $sql = $size === 'thumb'
            ? 'SELECT
                    COALESCE(t.id, u.id) AS id,
                    COALESCE(t.storage_path, u.storage_path) AS storage_path,
                    COALESCE(t.mime_type, u.mime_type) AS mime_type
                FROM uploads u
                LEFT JOIN uploads t
                  ON t.image_role = "thumbnail"
                 AND t.deleted_at IS NULL
                 AND t.status IN ("active", "temporary")
                 AND t.storage_path LIKE CONCAT("thumbnails/", u.sha256_hash, "-%")
                WHERE u.id = :id
                  AND u.deleted_at IS NULL
                  AND u.status IN ("active", "temporary")
                ORDER BY t.width_px ASC
                LIMIT 1'
            : 'SELECT id, storage_path, mime_type
               FROM uploads
               WHERE id = :id
                 AND deleted_at IS NULL
                 AND status IN ("active", "temporary")
               LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $uploadId]);
        $upload = $stmt->fetch();

        return $upload ?: null;
    }

    private function facetRows(string $sql): array
    {
        try {
            return $this->pdo->query($sql)->fetchAll();
        } catch (Throwable) {
            return [];
        }
    }
}
