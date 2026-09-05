<?php

declare(strict_types=1);

$databasePath = sys_get_temp_dir() . '/librairie-universelle-test-' . bin2hex(random_bytes(6)) . '.sqlite';
$storagePath = sys_get_temp_dir() . '/librairie-universelle-storage-' . bin2hex(random_bytes(6));
putenv('DATABASE_PATH=' . $databasePath);
putenv('STORAGE_PATH=' . $storagePath);
putenv('SELF_PUBLISHING_ENABLED=false');
putenv('FEEDBACK_ENABLED=false');

[$config, $pdo] = require dirname(__DIR__) . '/src/bootstrap.php';

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
};

$assert(Isbn::isValid('9782072938245'), 'ISBN-13 valide refusé.');
$assert(Isbn::isValid('2-07-036822-X'), 'ISBN-10 valide refusé.');
$assert(!Isbn::isValid('9782072938246'), 'ISBN invalide accepté.');

$tables = array_flip($pdo->query("SELECT name FROM sqlite_master WHERE type = 'table'")->fetchAll(PDO::FETCH_COLUMN));
foreach (['author_submissions', 'submission_files', 'feedback', 'sync_runs'] as $table) {
    $assert(isset($tables[$table]), 'Table manquante: ' . $table);
}

$allOffers = EbookStorefront::browse($pdo, $config);
$freeOffers = EbookStorefront::browse($pdo, $config, 'free');
$paidOffers = EbookStorefront::browse($pdo, $config, 'paid');
$assert(count($allOffers) >= 4, 'Storefront ebook trop petit.');
$assert(count($freeOffers) >= 2, 'Offres gratuites absentes.');
$assert(count($paidOffers) >= 2, 'Offres payantes absentes.');

$insert = $pdo->prepare(<<<'SQL'
INSERT INTO author_submissions (
    status, author_name, email, title, language, description,
    ebook_distribution, ebook_price_cents, rights_confirmed, quality_confirmed
) VALUES (
    'approved', 'Auteur Test', 'test@example.invalid', 'Livre indépendant de test', 'fr',
    'Soumission de test pour la CI.', 'free', 0, 1, 1
)
SQL);
$insert->execute();
$submissionId = (int) $pdo->lastInsertId();

$file = $pdo->prepare(<<<'SQL'
INSERT INTO submission_files (
    submission_id, kind, original_name, stored_name, mime_type, size_bytes, sha256
) VALUES (
    :submission, 'ebook', 'test.epub', 'test.epub', 'application/epub+zip', 128, :sha
)
SQL);
$file->execute([
    ':submission' => $submissionId,
    ':sha' => str_repeat('a', 64),
]);

$service = new SelfPublishingService($pdo, $config);
$workId = $service->publish($submissionId);
$assert($workId > 0, 'Publication indépendante non créée.');

$status = $pdo->prepare('SELECT status, published_work_id FROM author_submissions WHERE id = :id');
$status->execute([':id' => $submissionId]);
$row = $status->fetch();
$assert($row !== false && $row['status'] === 'published' && (int) $row['published_work_id'] === $workId, 'Statut de publication incorrect.');

$publishedOffers = EbookStorefront::browse($pdo, $config, 'free', 'Livre indépendant de test');
$assert(count($publishedOffers) === 1, 'Ebook indépendant gratuit non visible dans le storefront.');
$assert($publishedOffers[0]['source_name'] === 'Auteurs indépendants validés', 'Source indépendante incorrecte.');

@unlink($databasePath);
@unlink($databasePath . '-shm');
@unlink($databasePath . '-wal');
@rmdir($storagePath);

fwrite(STDOUT, "MVP storefront + autoédition: OK\n");
