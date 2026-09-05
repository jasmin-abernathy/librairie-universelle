<?php

declare(strict_types=1);

final class OfferImporter
{
    private const ALLOWED_TYPES = ['new', 'used', 'ebook', 'audio', 'other'];

    public function __construct(
        private PDO $pdo,
        private OfferSource $source,
        private int $sourceId
    ) {
    }

    public function importEdition(int $editionId): array
    {
        $edition = $this->loadEdition($editionId);
        if ($edition === null) {
            throw new RuntimeException('Édition introuvable.');
        }
        $isbn = Isbn::normalize($edition['isbn13']);
        if ($isbn === null || strlen($isbn) !== 13 || !Isbn::isValid13($isbn)) {
            throw new RuntimeException('Cette édition n’a pas d’ISBN-13 exploitable.');
        }

        $rows = $this->source->offersForIsbn($isbn);
        $stats = ['found' => count($rows), 'inserted' => 0, 'updated' => 0, 'ignored' => 0];

        foreach ($rows as $row) {
            try {
                $offer = $this->normalizeOffer($row);
            } catch (InvalidArgumentException) {
                $stats['ignored']++;
                continue;
            }

            $existingId = $this->existingOfferId($offer['external_id']);
            if ($existingId !== null) {
                $this->updateOffer($existingId, $edition, $offer);
                $stats['updated']++;
                continue;
            }

            $this->pdo->beginTransaction();
            try {
                $statement = $this->pdo->prepare(<<<'SQL'
INSERT INTO offers (
    source_id, work_id, edition_id, offer_type, price_cents, currency,
    availability, url, file_format, drm_type, checked_at
) VALUES (
    :source, :work, :edition, :type, :price, :currency,
    :availability, :url, :format, :drm, CURRENT_TIMESTAMP
)
SQL);
                $statement->execute([
                    ':source' => $this->sourceId,
                    ':work' => $edition['work_id'],
                    ':edition' => $editionId,
                    ':type' => $offer['offer_type'],
                    ':price' => $offer['price_cents'],
                    ':currency' => $offer['currency'],
                    ':availability' => $offer['availability'],
                    ':url' => $offer['url'],
                    ':format' => $offer['file_format'],
                    ':drm' => $offer['drm_type'],
                ]);
                $offerId = (int) $this->pdo->lastInsertId();

                $record = $this->pdo->prepare(<<<'SQL'
INSERT INTO source_records (source_id, external_id, entity_type, local_id, source_url)
VALUES (:source, :external, 'offer', :local, :url)
SQL);
                $record->execute([
                    ':source' => $this->sourceId,
                    ':external' => $offer['external_id'],
                    ':local' => $offerId,
                    ':url' => $offer['url'],
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

    private function loadEdition(int $editionId): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, work_id, isbn13 FROM editions WHERE id = :id');
        $statement->execute([':id' => $editionId]);
        $row = $statement->fetch();
        return $row === false ? null : $row;
    }

    private function existingOfferId(string $externalId): ?int
    {
        $statement = $this->pdo->prepare(<<<'SQL'
SELECT local_id FROM source_records
WHERE source_id = :source AND external_id = :external AND entity_type = 'offer'
LIMIT 1
SQL);
        $statement->execute([
            ':source' => $this->sourceId,
            ':external' => $externalId,
        ]);
        $id = $statement->fetchColumn();
        return $id === false || $id === null ? null : (int) $id;
    }

    private function updateOffer(int $offerId, array $edition, array $offer): void
    {
        $statement = $this->pdo->prepare(<<<'SQL'
UPDATE offers
SET work_id = :work,
    edition_id = :edition,
    offer_type = :type,
    price_cents = :price,
    currency = :currency,
    availability = :availability,
    url = :url,
    file_format = :format,
    drm_type = :drm,
    checked_at = CURRENT_TIMESTAMP
WHERE id = :id AND source_id = :source
SQL);
        $statement->execute([
            ':work' => $edition['work_id'],
            ':edition' => $edition['id'],
            ':type' => $offer['offer_type'],
            ':price' => $offer['price_cents'],
            ':currency' => $offer['currency'],
            ':availability' => $offer['availability'],
            ':url' => $offer['url'],
            ':format' => $offer['file_format'],
            ':drm' => $offer['drm_type'],
            ':id' => $offerId,
            ':source' => $this->sourceId,
        ]);
    }

    private function normalizeOffer(array $row): array
    {
        $externalId = trim((string) ($row['external_id'] ?? ''));
        $type = trim((string) ($row['offer_type'] ?? ''));
        $url = trim((string) ($row['url'] ?? ''));
        if ($externalId === '' || !in_array($type, self::ALLOWED_TYPES, true) || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Offre partenaire invalide.');
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('Schéma URL non autorisé.');
        }

        $price = $row['price_cents'] ?? null;
        if ($price !== null && (!is_int($price) || $price < 0)) {
            throw new InvalidArgumentException('Prix partenaire invalide.');
        }

        $currency = strtoupper(trim((string) ($row['currency'] ?? 'EUR')));
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new InvalidArgumentException('Devise partenaire invalide.');
        }

        return [
            'external_id' => $externalId,
            'offer_type' => $type,
            'price_cents' => $price,
            'currency' => $currency,
            'availability' => self::nullable($row['availability'] ?? null),
            'url' => $url,
            'file_format' => self::nullable($row['file_format'] ?? null),
            'drm_type' => self::nullable($row['drm_type'] ?? null),
        ];
    }

    private static function nullable(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));
        return $text === '' ? null : $text;
    }
}
