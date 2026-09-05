<?php

declare(strict_types=1);

final class CatalogImporter
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly CatalogSource $source,
        private readonly int $sourceId
    ) {
    }

    /** @return array{found:int, inserted:int, linked:int, skipped:int, conflicts:int} */
    public function importWork(int $workId, int $limit = 20): array
    {
        $work = $this->loadWork($workId);
        if ($work === null) {
            throw new InvalidArgumentException('Œuvre inconnue : ' . $workId);
        }

        $titles = array_values(array_unique(array_filter([
            trim((string) $work['title']),
            trim((string) ($work['original_title'] ?? '')),
        ])));

        $recordsByExternalId = [];
        foreach ($titles as $title) {
            foreach ($this->source->searchEditions($title, (string) $work['author'], $limit) as $record) {
                $recordsByExternalId[$record['external_id']] = $record;
            }
        }

        $stats = [
            'found' => count($recordsByExternalId),
            'inserted' => 0,
            'linked' => 0,
            'skipped' => 0,
            'conflicts' => 0,
        ];

        $this->pdo->beginTransaction();
        try {
            foreach ($recordsByExternalId as $record) {
                $existingLink = $this->findSourceRecord((string) $record['external_id']);
                if ($existingLink !== null) {
                    $stats['skipped']++;
                    continue;
                }

                $editionId = null;
                if (!empty($record['isbn13'])) {
                    $edition = $this->findEditionByIsbn((string) $record['isbn13']);
                    if ($edition !== null) {
                        if ((int) $edition['work_id'] !== $workId) {
                            $stats['conflicts']++;
                            continue;
                        }
                        $editionId = (int) $edition['id'];
                        $stats['linked']++;
                    }
                }

                if ($editionId === null) {
                    $editionId = $this->insertEdition($workId, $record);
                    $stats['inserted']++;
                }

                $this->linkEditionContributors($editionId, $record['contributors'] ?? []);
                $this->insertSourceRecord($editionId, $record);
            }

            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }

        return $stats;
    }

    private function loadWork(int $id): ?array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
SELECT
    w.id,
    w.title,
    w.original_title,
    (
        SELECT c.name
        FROM work_contributors wc
        JOIN contributors c ON c.id = wc.contributor_id
        WHERE wc.work_id = w.id AND wc.role = 'author'
        ORDER BY c.id
        LIMIT 1
    ) AS author
FROM works w
WHERE w.id = :id
SQL);
        $statement->execute([':id' => $id]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    private function findSourceRecord(string $externalId): ?array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
SELECT * FROM source_records
WHERE source_id = :source_id
  AND external_id = :external_id
  AND entity_type = 'edition'
LIMIT 1
SQL);
        $statement->execute([
            ':source_id' => $this->sourceId,
            ':external_id' => $externalId,
        ]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    private function findEditionByIsbn(string $isbn13): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, work_id FROM editions WHERE isbn13 = :isbn LIMIT 1');
        $statement->execute([':isbn' => $isbn13]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    /** @param array<string, mixed> $record */
    private function insertEdition(int $workId, array $record): int
    {
        $statement = $this->pdo->prepare(<<<'SQL'
INSERT INTO editions (
    work_id, isbn13, title, publisher, publication_date, language, medium, file_format, drm_type, accessibility_note
) VALUES (
    :work_id, :isbn13, :title, :publisher, :publication_date, :language, :medium, :file_format, NULL, :accessibility_note
)
SQL);
        $statement->execute([
            ':work_id' => $workId,
            ':isbn13' => $record['isbn13'] ?? null,
            ':title' => $record['title'] ?? null,
            ':publisher' => $record['publisher'] ?? null,
            ':publication_date' => $record['publication_date'] ?? null,
            ':language' => $record['language'] ?? null,
            ':medium' => $record['medium'] ?? 'paper',
            ':file_format' => $record['file_format'] ?? null,
            ':accessibility_note' => !empty($record['cover_url']) ? 'Couverture BnF disponible via ISBN : ' . $record['cover_url'] : null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<int, array{name:string, role:string}> $contributors */
    private function linkEditionContributors(int $editionId, array $contributors): void
    {
        foreach ($contributors as $contributor) {
            $name = trim((string) ($contributor['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $find = $this->pdo->prepare('SELECT id FROM contributors WHERE name = :name COLLATE NOCASE LIMIT 1');
            $find->execute([':name' => $name]);
            $contributorId = $find->fetchColumn();

            if ($contributorId === false) {
                $insert = $this->pdo->prepare('INSERT INTO contributors (name) VALUES (:name)');
                $insert->execute([':name' => $name]);
                $contributorId = (int) $this->pdo->lastInsertId();
            }

            $link = $this->pdo->prepare(<<<'SQL'
INSERT OR IGNORE INTO edition_contributors (edition_id, contributor_id, role)
VALUES (:edition_id, :contributor_id, :role)
SQL);
            $link->execute([
                ':edition_id' => $editionId,
                ':contributor_id' => (int) $contributorId,
                ':role' => trim((string) ($contributor['role'] ?? 'contributor')) ?: 'contributor',
            ]);
        }
    }

    /** @param array<string, mixed> $record */
    private function insertSourceRecord(int $editionId, array $record): void
    {
        $statement = $this->pdo->prepare(<<<'SQL'
INSERT INTO source_records (source_id, external_id, entity_type, local_id, source_url)
VALUES (:source_id, :external_id, 'edition', :local_id, :source_url)
SQL);
        $statement->execute([
            ':source_id' => $this->sourceId,
            ':external_id' => (string) $record['external_id'],
            ':local_id' => $editionId,
            ':source_url' => (string) $record['source_url'],
        ]);
    }
}
