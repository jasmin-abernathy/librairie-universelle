<?php

declare(strict_types=1);

final class Search
{
    public static function works(PDO $pdo, string $query, int $limit = 30): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $limit = max(1, min($limit, 100));
        $needle = '%' . self::escapeLike(mb_strtolower($query, 'UTF-8')) . '%';

        $sql = <<<'SQL'
SELECT
    w.id,
    w.title,
    w.subtitle,
    w.original_title,
    w.first_publication_year,
    w.language,
    w.public_domain_status,
    GROUP_CONCAT(DISTINCT c.name) AS contributors
FROM works w
LEFT JOIN work_contributors wc ON wc.work_id = w.id
LEFT JOIN contributors c ON c.id = wc.contributor_id
WHERE lower(w.title) LIKE :needle ESCAPE '\'
   OR lower(COALESCE(w.subtitle, '')) LIKE :needle ESCAPE '\'
   OR lower(COALESCE(w.original_title, '')) LIKE :needle ESCAPE '\'
   OR lower(COALESCE(c.name, '')) LIKE :needle ESCAPE '\'
GROUP BY w.id
ORDER BY
    CASE WHEN lower(w.title) = :exact THEN 0 ELSE 1 END,
    w.title COLLATE NOCASE ASC
LIMIT :limit
SQL;

        $statement = $pdo->prepare($sql);
        $statement->bindValue(':needle', $needle, PDO::PARAM_STR);
        $statement->bindValue(':exact', mb_strtolower($query, 'UTF-8'), PDO::PARAM_STR);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
