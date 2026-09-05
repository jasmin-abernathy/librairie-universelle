<?php

declare(strict_types=1);

final class EbookStorefront
{
    public static function browse(PDO $pdo, array $config, string $mode = 'all', string $query = ''): array
    {
        $mode = in_array($mode, ['all', 'free', 'paid'], true) ? $mode : 'all';
        $query = trim($query);

        $sql = <<<'SQL'
SELECT
    o.id AS offer_id,
    o.offer_type,
    o.price_cents,
    o.currency,
    o.availability,
    o.url,
    o.file_format AS offer_format,
    o.drm_type AS offer_drm,
    o.checked_at,
    w.id AS work_id,
    w.title AS work_title,
    w.original_title,
    w.public_domain_status,
    e.id AS edition_id,
    e.title AS edition_title,
    e.isbn13,
    e.language,
    e.publisher,
    e.file_format AS edition_format,
    s.name AS source_name,
    s.source_type,
    GROUP_CONCAT(DISTINCT c.name) AS contributors
FROM offers o
JOIN sources s ON s.id = o.source_id AND s.active = 1
LEFT JOIN editions e ON e.id = o.edition_id
JOIN works w ON w.id = COALESCE(o.work_id, e.work_id)
LEFT JOIN work_contributors wc ON wc.work_id = w.id AND wc.role = 'author'
LEFT JOIN contributors c ON c.id = wc.contributor_id
WHERE o.offer_type IN ('ebook', 'free_download', 'read_online')
SQL;
        $params = [];

        if ($mode === 'free') {
            $sql .= " AND (o.offer_type IN ('free_download', 'read_online') OR o.price_cents = 0)";
        } elseif ($mode === 'paid') {
            $sql .= " AND o.offer_type = 'ebook' AND o.price_cents IS NOT NULL AND o.price_cents > 0";
        }

        if ($query !== '') {
            $sql .= ' AND (w.title LIKE :query ESCAPE \'\\\' OR w.original_title LIKE :query ESCAPE \'\\\' OR e.title LIKE :query ESCAPE \'\\\' OR c.name LIKE :query ESCAPE \'\\\')';
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query);
            $params[':query'] = '%' . $escaped . '%';
        }

        $sql .= <<<'SQL'
 GROUP BY o.id
 ORDER BY
    CASE WHEN o.offer_type IN ('free_download', 'read_online') OR o.price_cents = 0 THEN 0 ELSE 1 END,
    w.title COLLATE NOCASE ASC,
    o.price_cents ASC,
    s.name COLLATE NOCASE ASC
SQL;

        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        $rows = $statement->fetchAll();

        foreach ($rows as &$row) {
            $row['is_free'] = in_array($row['offer_type'], ['free_download', 'read_online'], true) || (int) $row['price_cents'] === 0;
            $row['is_stale'] = self::isStale($row['checked_at'], (int) $config['offer_max_age_days']);
        }
        unset($row);

        return $rows;
    }

    private static function isStale(?string $checkedAt, int $maxAgeDays): bool
    {
        if ($checkedAt === null || trim($checkedAt) === '') {
            return true;
        }

        try {
            $checked = new DateTimeImmutable($checkedAt);
            return $checked < (new DateTimeImmutable('now'))->modify('-' . $maxAgeDays . ' days');
        } catch (Throwable) {
            return true;
        }
    }
}
