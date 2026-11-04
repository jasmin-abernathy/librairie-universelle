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

$bookstoreCount = (int) $pdo->query("SELECT COUNT(*) FROM bookstores")->fetchColumn();
$listedBookstoreCount = (int) $pdo->query("SELECT COUNT(*) FROM bookstores WHERE directory_status = 'listed'")->fetchColumn();
$reviewBookstoreCount = (int) $pdo->query("SELECT COUNT(*) FROM bookstores WHERE directory_status = 'review'")->fetchColumn();
$assert($bookstoreCount >= 23, 'Annuaire librairies incomplet.');
$assert($listedBookstoreCount >= 21, 'Librairies publiques insuffisantes.');
$assert($reviewBookstoreCount >= 2, 'Fiches à vérifier absentes de la curation.');

$tables = array_flip($pdo->query("SELECT name FROM sqlite_master WHERE type = 'table'")->fetchAll(PDO::FETCH_COLUMN));
foreach (['author_submissions', 'submission_files', 'submission_paper_covers', 'submission_print_settings', 'feedback', 'sync_runs', 'bookstores'] as $table) {
    $assert(isset($tables[$table]), 'Table manquante: ' . $table);
}

$allOffers = EbookStorefront::browse($pdo, $config);
$freeOffers = EbookStorefront::browse($pdo, $config, 'free');
$paidOffers = EbookStorefront::browse($pdo, $config, 'paid');
$assert($allOffers === [], 'Le catalogue ne doit pas être prérempli par des offres fictives.');
$assert($freeOffers === [] && $paidOffers === [], 'Des offres de démonstration restent publiées.');

$insert = $pdo->prepare(<<<'SQL'
INSERT INTO author_submissions (
    status, author_name, email, title, language, description,
    ebook_distribution, ebook_price_cents, rights_confirmed, quality_confirmed
) VALUES (
    'approved', 'Autrice CI', 'test@example.invalid', 'Ouvrage de contrôle isolé', 'fr',
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

$print = $pdo->prepare(<<<'SQL'
INSERT INTO submission_print_settings (
    submission_id, trim_size, binding, toc_enabled, chapter_start, page_number_position,
    hide_chapter_openers, front_matter_numbering, bleed_mm, gutter_mode, gutter_mm
) VALUES (
    :submission, '140x210', 'paperback', 1, 'right', 'outside', 1, 'roman', 0, 'auto', NULL
)
SQL);
$print->execute([':submission' => $submissionId]);

$service = new SelfPublishingService($pdo, $config);
$paperCoverStmt = $pdo->prepare("INSERT INTO submission_paper_covers
 (submission_id,side,original_name,stored_name,mime_type,size_bytes,sha256)
 VALUES (:id,'back','quatrieme.pdf','quatrieme.pdf','application/pdf',100,:sha)");
$paperCoverStmt->execute([':id' => $submissionId, ':sha' => str_repeat('b', 64)]);
$submissionDetail = $service->find($submissionId);
$assert(count($submissionDetail['paper_covers']) === 1 && $submissionDetail['paper_covers'][0]['side'] === 'back',
    'Fichier de quatrième de couverture absent.');
$paperPath = $service->paperCoverPathForAdmin((int) $submissionDetail['paper_covers'][0]['id']);
$assert($paperPath !== null && str_ends_with($paperPath['path'], 'quatrieme.pdf'),
    'Téléchargement privé de la quatrième de couverture non résolu.');
$assert($submissionDetail !== null && $submissionDetail['print_settings'] !== null, 'Réglages de composition papier absents de la soumission.');
$assert($submissionDetail['print_settings']['chapter_start'] === 'right', 'Règle de début de chapitre non conservée.');
$assert((int) $submissionDetail['print_settings']['toc_enabled'] === 1, 'Sommaire automatique non conservé.');
$workId = $service->publish($submissionId);
$assert($workId > 0, 'Publication indépendante non créée.');

$status = $pdo->prepare('SELECT status, published_work_id FROM author_submissions WHERE id = :id');
$status->execute([':id' => $submissionId]);
$row = $status->fetch();
$assert($row !== false && $row['status'] === 'published' && (int) $row['published_work_id'] === $workId, 'Statut de publication incorrect.');

$publishedOffers = EbookStorefront::browse($pdo, $config, 'free', 'Ouvrage de contrôle isolé');
$assert(count($publishedOffers) === 1, 'Ebook indépendant gratuit non visible dans le storefront.');
$assert($publishedOffers[0]['source_name'] === 'Auteurs indépendants validés', 'Source indépendante incorrecte.');

@unlink($databasePath);
@unlink($databasePath . '-shm');
@unlink($databasePath . '-wal');
@rmdir($storagePath);

fwrite(STDOUT, "MVP storefront sans offre factice + autoédition: OK\n");
