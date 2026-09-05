<?php

declare(strict_types=1);

final class SelfPublishingService
{
    public function __construct(private PDO $pdo, private array $config)
    {
    }

    public function submit(array $input, array $files): int
    {
        if (!$this->config['self_publishing_enabled']) {
            throw new RuntimeException('Les dépôts auteur ne sont pas encore ouverts.');
        }

        $authorName = trim((string) ($input['author_name'] ?? ''));
        $pseudonym = trim((string) ($input['pseudonym'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $title = trim((string) ($input['title'] ?? ''));
        $subtitle = trim((string) ($input['subtitle'] ?? ''));
        $language = trim((string) ($input['language'] ?? 'fr'));
        $description = trim((string) ($input['description'] ?? ''));
        $isbnEbook = Isbn::normalize($input['isbn_ebook'] ?? null);
        $isbnPaper = Isbn::normalize($input['isbn_paper'] ?? null);
        $ebookDistribution = ($input['ebook_distribution'] ?? 'paid') === 'free' ? 'free' : 'paid';
        $ebookPrice = self::eurosToCents($input['ebook_price'] ?? null);
        $paperPrice = self::eurosToCents($input['paper_price'] ?? null);

        if ($authorName === '' || $title === '' || $description === '') {
            throw new InvalidArgumentException('Nom, titre et présentation sont obligatoires.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Adresse e-mail invalide.');
        }
        if (!preg_match('/^[a-z]{2,3}(?:-[A-Z]{2})?$/', $language)) {
            throw new InvalidArgumentException('Code de langue invalide.');
        }
        if (!Isbn::isValid($isbnEbook) || !Isbn::isValid($isbnPaper)) {
            throw new InvalidArgumentException('Un des ISBN renseignés est invalide.');
        }
        if ($isbnEbook !== null && $isbnPaper !== null && $isbnEbook === $isbnPaper) {
            throw new InvalidArgumentException('Les versions papier et EPUB doivent avoir des ISBN distincts.');
        }
        if (empty($input['rights_confirmed']) || empty($input['quality_confirmed'])) {
            throw new InvalidArgumentException('Les déclarations de droits et de qualité doivent être acceptées.');
        }
        if ($ebookDistribution === 'paid' && $ebookPrice !== null && $ebookPrice < 0) {
            throw new InvalidArgumentException('Prix ebook invalide.');
        }
        if ($ebookDistribution === 'free') {
            $ebookPrice = 0;
        }

        $uploaded = [];
        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare(<<<'SQL'
INSERT INTO author_submissions (
    author_name, pseudonym, email, title, subtitle, language, description,
    isbn_ebook, isbn_paper, ebook_price_cents, paper_price_cents,
    ebook_distribution, rights_confirmed, quality_confirmed
) VALUES (
    :author_name, :pseudonym, :email, :title, :subtitle, :language, :description,
    :isbn_ebook, :isbn_paper, :ebook_price_cents, :paper_price_cents,
    :ebook_distribution, 1, 1
)
SQL);
            $statement->execute([
                ':author_name' => $authorName,
                ':pseudonym' => $pseudonym !== '' ? $pseudonym : null,
                ':email' => $email,
                ':title' => $title,
                ':subtitle' => $subtitle !== '' ? $subtitle : null,
                ':language' => $language,
                ':description' => $description,
                ':isbn_ebook' => $isbnEbook,
                ':isbn_paper' => $isbnPaper,
                ':ebook_price_cents' => $ebookPrice,
                ':paper_price_cents' => $paperPrice,
                ':ebook_distribution' => $ebookDistribution,
            ]);
            $submissionId = (int) $this->pdo->lastInsertId();

            foreach (['ebook', 'cover', 'print_pdf'] as $kind) {
                if (!isset($files[$kind]) || ($files[$kind]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $stored = UploadValidator::validateAndStore($files[$kind], $kind, $this->config);
                $uploaded[] = $stored['path'];
                $this->insertFile($submissionId, $kind, $stored);
            }

            if (!$this->hasKind($submissionId, 'ebook') && !$this->hasKind($submissionId, 'print_pdf')) {
                throw new InvalidArgumentException('Ajoutez au moins un EPUB ou un PDF prêt à imprimer.');
            }

            $this->pdo->commit();
            return $submissionId;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            foreach ($uploaded as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            throw $error;
        }
    }

    public function listSubmissions(?string $status = null): array
    {
        $sql = 'SELECT s.*, COUNT(f.id) AS file_count FROM author_submissions s LEFT JOIN submission_files f ON f.submission_id = s.id';
        $params = [];
        if ($status !== null && in_array($status, ['pending', 'needs_changes', 'approved', 'rejected', 'published'], true)) {
            $sql .= ' WHERE s.status = :status';
            $params[':status'] = $status;
        }
        $sql .= ' GROUP BY s.id ORDER BY s.submitted_at DESC, s.id DESC';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM author_submissions WHERE id = :id');
        $statement->execute([':id' => $id]);
        $submission = $statement->fetch();
        if ($submission === false) {
            return null;
        }

        $files = $this->pdo->prepare('SELECT * FROM submission_files WHERE submission_id = :id ORDER BY id');
        $files->execute([':id' => $id]);
        $submission['files'] = $files->fetchAll();
        return $submission;
    }

    public function review(int $id, string $status, ?string $note): void
    {
        if (!in_array($status, ['needs_changes', 'approved', 'rejected'], true)) {
            throw new InvalidArgumentException('Statut de revue invalide.');
        }

        $statement = $this->pdo->prepare(<<<'SQL'
UPDATE author_submissions
SET status = :status, editorial_note = :note, reviewed_at = CURRENT_TIMESTAMP
WHERE id = :id AND status IN ('pending', 'needs_changes', 'approved')
SQL);
        $statement->execute([
            ':status' => $status,
            ':note' => ($note !== null && trim($note) !== '') ? trim($note) : null,
            ':id' => $id,
        ]);
        if ($statement->rowCount() < 1) {
            throw new RuntimeException('Soumission introuvable ou non modifiable.');
        }
    }

    public function publish(int $id): int
    {
        $submission = $this->find($id);
        if ($submission === null) {
            throw new RuntimeException('Soumission introuvable.');
        }
        if ($submission['status'] === 'published' && !empty($submission['published_work_id'])) {
            return (int) $submission['published_work_id'];
        }
        if ($submission['status'] !== 'approved') {
            throw new RuntimeException('La soumission doit être validée avant publication.');
        }

        $this->pdo->beginTransaction();
        try {
            $authorDisplay = trim((string) ($submission['pseudonym'] ?: $submission['author_name']));
            $contributor = $this->pdo->prepare('INSERT INTO contributors (name) VALUES (:name)');
            $contributor->execute([':name' => $authorDisplay]);
            $contributorId = (int) $this->pdo->lastInsertId();

            $work = $this->pdo->prepare(<<<'SQL'
INSERT INTO works (title, subtitle, language, public_domain_status, public_domain_note)
VALUES (:title, :subtitle, :language, 'no', :note)
SQL);
            $work->execute([
                ':title' => $submission['title'],
                ':subtitle' => $submission['subtitle'],
                ':language' => $submission['language'],
                ':note' => 'Publication indépendante validée humainement. Les droits restent ceux déclarés par l’auteur ou l’autrice.',
            ]);
            $workId = (int) $this->pdo->lastInsertId();

            $link = $this->pdo->prepare("INSERT INTO work_contributors (work_id, contributor_id, role) VALUES (:work, :contributor, 'author')");
            $link->execute([':work' => $workId, ':contributor' => $contributorId]);

            $publisher = $authorDisplay;
            if ($this->hasKind($id, 'ebook')) {
                $edition = $this->pdo->prepare(<<<'SQL'
INSERT INTO editions (work_id, isbn13, title, publisher, language, medium, file_format, drm_type)
VALUES (:work, :isbn, :title, :publisher, :language, 'ebook', 'EPUB', 'none')
SQL);
                $edition->execute([
                    ':work' => $workId,
                    ':isbn' => $submission['isbn_ebook'],
                    ':title' => $submission['title'],
                    ':publisher' => $publisher,
                    ':language' => $submission['language'],
                ]);
                $editionId = (int) $this->pdo->lastInsertId();

                if ($submission['ebook_distribution'] === 'free') {
                    $sourceId = $this->independentSourceId();
                    $offer = $this->pdo->prepare(<<<'SQL'
INSERT INTO offers (source_id, work_id, edition_id, offer_type, price_cents, currency, availability, url, file_format, drm_type, checked_at)
VALUES (:source, :work, :edition, 'free_download', 0, 'EUR', 'available', :url, 'EPUB', 'none', CURRENT_TIMESTAMP)
SQL);
                    $offer->execute([
                        ':source' => $sourceId,
                        ':work' => $workId,
                        ':edition' => $editionId,
                        ':url' => '/self-published-download.php?submission=' . $id,
                    ]);
                }
            }

            if ($this->hasKind($id, 'print_pdf') || !empty($submission['isbn_paper'])) {
                $edition = $this->pdo->prepare(<<<'SQL'
INSERT INTO editions (work_id, isbn13, title, publisher, language, medium, file_format)
VALUES (:work, :isbn, :title, :publisher, :language, 'paper', :format)
SQL);
                $edition->execute([
                    ':work' => $workId,
                    ':isbn' => $submission['isbn_paper'],
                    ':title' => $submission['title'],
                    ':publisher' => $publisher,
                    ':language' => $submission['language'],
                    ':format' => $this->hasKind($id, 'print_pdf') ? 'PDF imprimeur disponible en validation interne' : null,
                ]);
            }

            $update = $this->pdo->prepare("UPDATE author_submissions SET status = 'published', published_work_id = :work, reviewed_at = CURRENT_TIMESTAMP WHERE id = :id");
            $update->execute([':work' => $workId, ':id' => $id]);
            $this->pdo->commit();
            return $workId;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function filePathForAdmin(int $fileId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM submission_files WHERE id = :id');
        $statement->execute([':id' => $fileId]);
        $file = $statement->fetch();
        if ($file === false) {
            return null;
        }
        $file['path'] = rtrim((string) $this->config['storage_path'], '/') . '/submissions/' . $file['stored_name'];
        return $file;
    }

    public function freeEbookPath(int $submissionId): ?array
    {
        $statement = $this->pdo->prepare(<<<'SQL'
SELECT f.*
FROM submission_files f
JOIN author_submissions s ON s.id = f.submission_id
WHERE s.id = :id
  AND s.status = 'published'
  AND s.ebook_distribution = 'free'
  AND f.kind = 'ebook'
LIMIT 1
SQL);
        $statement->execute([':id' => $submissionId]);
        $file = $statement->fetch();
        if ($file === false) {
            return null;
        }
        $file['path'] = rtrim((string) $this->config['storage_path'], '/') . '/submissions/' . $file['stored_name'];
        return $file;
    }

    private function insertFile(int $submissionId, string $kind, array $stored): void
    {
        $statement = $this->pdo->prepare(<<<'SQL'
INSERT INTO submission_files (submission_id, kind, original_name, stored_name, mime_type, size_bytes, sha256)
VALUES (:submission, :kind, :original, :stored, :mime, :size, :sha)
SQL);
        $statement->execute([
            ':submission' => $submissionId,
            ':kind' => $kind,
            ':original' => $stored['original_name'],
            ':stored' => $stored['stored_name'],
            ':mime' => $stored['mime_type'],
            ':size' => $stored['size_bytes'],
            ':sha' => $stored['sha256'],
        ]);
    }

    private function hasKind(int $submissionId, string $kind): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM submission_files WHERE submission_id = :id AND kind = :kind LIMIT 1');
        $statement->execute([':id' => $submissionId, ':kind' => $kind]);
        return $statement->fetchColumn() !== false;
    }

    private function independentSourceId(): int
    {
        $statement = $this->pdo->prepare("SELECT id FROM sources WHERE name = 'Auteurs indépendants validés' LIMIT 1");
        $statement->execute();
        $id = $statement->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }

        $insert = $this->pdo->prepare(<<<'SQL'
INSERT INTO sources (name, source_type, data_license)
VALUES ('Auteurs indépendants validés', 'publisher', 'Données fournies par l’auteur et validées humainement pour le storefront')
SQL);
        $insert->execute();
        return (int) $this->pdo->lastInsertId();
    }

    private static function eurosToCents(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        $normalized = str_replace(',', '.', trim((string) $value));
        if (!is_numeric($normalized)) {
            throw new InvalidArgumentException('Prix invalide.');
        }
        return (int) round(((float) $normalized) * 100);
    }
}
