<?php

declare(strict_types=1);

final class Search
{
    public static function works(
        PDO $pdo,
        string $query,
        int $limit = 30,
        array $languages = ['fr', 'en']
    ): array {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $languages = array_values(array_intersect(
            ['fr', 'en'],
            array_unique(array_map(
                static fn (mixed $language): string => mb_strtolower(trim((string) $language), 'UTF-8'),
                $languages
            ))
        ));
        if ($languages === []) {
            $languages = ['fr', 'en'];
        }

        $limit = max(1, min($limit, 100));
        $needle = '%' . self::escapeLike(mb_strtolower($query, 'UTF-8')) . '%';

        $languagePlaceholdersWork = [];
        $languagePlaceholdersEdition = [];
        foreach ($languages as $index => $_language) {
            $languagePlaceholdersWork[] = ':work_language_' . $index;
            $languagePlaceholdersEdition[] = ':edition_language_' . $index;
        }

        $languageSql = sprintf(
            "AND (
                lower(COALESCE(w.language, '')) IN (%s)
                OR EXISTS (
                    SELECT 1
                    FROM editions e_language
                    WHERE e_language.work_id = w.id
                      AND lower(COALESCE(e_language.language, '')) IN (%s)
                )
            )",
            implode(', ', $languagePlaceholdersWork),
            implode(', ', $languagePlaceholdersEdition)
        );

        $sql = <<<SQL
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
WHERE (
       lower(w.title) LIKE :needle ESCAPE '\\'
    OR lower(COALESCE(w.subtitle, '')) LIKE :needle ESCAPE '\\'
    OR lower(COALESCE(w.original_title, '')) LIKE :needle ESCAPE '\\'
    OR lower(COALESCE(c.name, '')) LIKE :needle ESCAPE '\\'
)
{$languageSql}
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

        foreach ($languages as $index => $language) {
            $statement->bindValue(':work_language_' . $index, $language, PDO::PARAM_STR);
            $statement->bindValue(':edition_language_' . $index, $language, PDO::PARAM_STR);
        }

        $statement->execute();
        return $statement->fetchAll();
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
