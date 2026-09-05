<?php

declare(strict_types=1);

final class GallicaImporter
{
    public function __construct(
        private PDO $pdo,
        private GallicaOpdsSource $source,
        private int $sourceId
    ) {
    }

    public function importWork(int $workId, int $limit = 10): array
    {
        $work = $this->loadWork($workId);
        if ($work === null) {
            throw new RuntimeException('Œuvre introuvable.');
        }

        if ($work['public_domain_status'] !== 'yes' || $work['language'] !== 'fr') {
            return ['found' => 0, 'inserted' => 0, 'skipped' => 1, 'mismatched' => 0];
        }

        $entries = $this->source->search((string) $work['title'], $limit);
        $stats = ['found' => count($entries), 'inserted' => 0, 'skipped' => 0, 'mismatched' => 0];

        foreach ($entries as $entry) {
            if (!GallicaOpdsSource::matchesWork($entry, (string) $work['title'], (string) $work['contributors'])) {
                $stats['mismatched']++;
                continue;
            }

            $externalId = (string) $entry['external_id'] . ':epub';
            if ($this->alreadyImported($externalId)) {
                $stats['skipped']++;
                continue;
            }

            $this->pdo->beginTransaction();
            try {
                $offer = $this->pdo->prepare(<<<'SQL'
INSERT INTO offers (
    source_id, work_id, offer_type, price_cents, currency, availability,
    url, file_format, drm_type, checked_at
) VALUES (
    :source, :work, 'free_download', 0, 'EUR', 'available',
    :url, 'EPUB', 'none', CURRENT_TIMESTAMP
)
SQL);
                $offer->execute([
                    ':source' => $this->sourceId,
                    ':work' => $workId,
                    ':url' => $entry['epub_url'],
                ]);
                $offerId = (int) $this->pdo->lastInsertId();

                $record = $this->pdo->prepare(<<<'SQL'
INSERT INTO source_records (source_id, external_id, entity_type, local_id, source_url)
VALUES (:source, :external, 'offer', :local, :url)
SQL);
                $record->execute([
                    ':source' => $this->sourceId,
                    ':external' => $externalId,
                    ':local' => $offerId,
                    ':url' => $entry['read_url'] ?: $entry['epub_url'],
                ]);

                $this->pdo->commit();
                $stats['inserted']++;
            } catch (Throwable $error) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                throw $error;
            }
        }

        return $stats;
    }

    private function loadWork(int $workId): ?array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
SELECT
    w.id,
    w.title,
    w.language,
    w.public_domain_status,
    GROUP_CONCAT(DISTINCT c.name) AS contributors
FROM works w
LEFT JOIN work_contributors wc ON wc.work_id = w.id AND wc.role = 'author'
LEFT JOIN contributors c ON c.id = wc.contributor_id
WHERE w.id = :id
GROUP BY w.id
SQL);
        $statement->execute([':id' => $workId]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    private function alreadyImported(string $externalId): bool
    {
        $statement = $this->pdo->prepare(<<<'SQL'
SELECT 1 FROM source_records
WHERE source_id = :source AND external_id = :external AND entity_type = 'offer'
LIMIT 1
SQL);
        $statement->execute([
            ':source' => $this->sourceId,
            ':external' => $externalId,
        ]);
        return $statement->fetchColumn() !== false;
    }
}
