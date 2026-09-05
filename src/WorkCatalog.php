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
SELECT
    e.*,
    GROUP_CONCAT(
        DISTINCT CASE ec.role
            WHEN 'translator' THEN 'Traduction : ' || c.name
            WHEN 'editor' THEN 'Édition : ' || c.name
            WHEN 'illustrator' THEN 'Illustration : ' || c.name
            ELSE c.name
        END
    ) AS edition_contributors,
    GROUP_CONCAT(DISTINCT sr.source_url) AS source_urls
FROM editions e
LEFT JOIN edition_contributors ec ON ec.edition_id = e.id
LEFT JOIN contributors c ON c.id = ec.contributor_id
LEFT JOIN source_records sr
    ON sr.entity_type = 'edition'
   AND sr.local_id = e.id
WHERE e.work_id = :id
GROUP BY e.id
ORDER BY
    CASE e.medium WHEN 'ebook' THEN 0 WHEN 'paper' THEN 1 WHEN 'audio' THEN 2 ELSE 3 END,
    e.publication_date DESC,
    e.id ASC
SQL);
        $editionStatement->execute([':id' => $id]);

        $offerStatement = $pdo->prepare(<<<'SQL'
SELECT
    o.*,
    s.name AS source_name,
    s.source_type,
    e.title AS edition_title,
    e.language AS edition_language,
    e.isbn13 AS edition_isbn13
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
    CASE WHEN o.price_cents IS NULL THEN 1 ELSE 0 END,
    o.price_cents ASC,
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
