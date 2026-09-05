<?php

declare(strict_types=1);

final class WorkCatalog
{
    public static function find(PDO $pdo, int $id): ?array
    {
        $workStatement = $pdo->prepare(<<<'SQL'
SELECT
    w.*,
    GROUP_CONCAT(DISTINCT c.name) AS contributors
FROM works w
LEFT JOIN work_contributors wc ON wc.work_id = w.id
LEFT JOIN contributors c ON c.id = wc.contributor_id
WHERE w.id = :id
GROUP BY w.id
SQL);
        $workStatement->execute([':id' => $id]);
        $work = $workStatement->fetch();

        if ($work === false) {
            return null;
        }

        $editionStatement = $pdo->prepare(<<<'SQL'
SELECT *
FROM editions
WHERE work_id = :id
ORDER BY publication_date ASC, id ASC
SQL);
        $editionStatement->execute([':id' => $id]);

        $offerStatement = $pdo->prepare(<<<'SQL'
SELECT
    o.*,
    s.name AS source_name,
    s.source_type,
    e.title AS edition_title,
    e.language AS edition_language
FROM offers o
JOIN sources s ON s.id = o.source_id
LEFT JOIN editions e ON e.id = o.edition_id
WHERE o.work_id = :id
   OR e.work_id = :id
ORDER BY
    CASE o.offer_type
        WHEN 'free_download' THEN 0
        WHEN 'read_online' THEN 1
        WHEN 'borrow' THEN 2
        WHEN 'ebook' THEN 3
        WHEN 'new' THEN 4
        WHEN 'used' THEN 5
        ELSE 6
    END,
    s.name COLLATE NOCASE ASC
SQL);
        $offerStatement->execute([':id' => $id]);

        return [
            'work' => $work,
            'editions' => $editionStatement->fetchAll(),
            'offers' => $offerStatement->fetchAll(),
        ];
    }
}
