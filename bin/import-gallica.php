<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Ce script doit être exécuté en ligne de commande.\n");
    exit(1);
}

[$config, $pdo] = require dirname(__DIR__) . '/src/bootstrap.php';
$options = getopt('', ['work::', 'all', 'limit::']);
$limit = isset($options['limit']) ? max(1, min(50, (int) $options['limit'])) : 10;

if (!isset($options['all']) && !isset($options['work'])) {
    fwrite(STDOUT, "Usage :\n");
    fwrite(STDOUT, "  php bin/import-gallica.php --work=1\n");
    fwrite(STDOUT, "  php bin/import-gallica.php --all --limit=10\n");
    exit(0);
}

$sourceId = $pdo->query("SELECT id FROM sources WHERE name = 'Gallica EPUB' LIMIT 1")->fetchColumn();
if ($sourceId === false) {
    $insertSource = $pdo->prepare(<<<'SQL'
INSERT INTO sources (name, source_type, base_url, terms_url, data_license)
VALUES (
    'Gallica EPUB',
    'public_domain',
    'https://gallica.bnf.fr',
    'https://gallica.bnf.fr/edit/und/conditions-dutilisation-des-contenus-de-gallica',
    'EPUB BnF issus d’ouvrages du domaine public ; liens vers Gallica, réutilisation selon les conditions Gallica'
)
SQL);
    $insertSource->execute();
    $sourceId = (int) $pdo->lastInsertId();
}

if (isset($options['all'])) {
    $statement = $pdo->query("SELECT id FROM works WHERE public_domain_status = 'yes' AND language = 'fr' ORDER BY id");
    $workIds = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
} else {
    $workIds = [(int) $options['work']];
}

$source = new GallicaOpdsSource();
$importer = new GallicaImporter($pdo, $source, (int) $sourceId);
$runId = SyncLog::start($pdo, 'Gallica EPUB');
$failed = 0;
$inserted = 0;
$skipped = 0;
$mismatched = 0;
$errors = [];

foreach ($workIds as $workId) {
    $titleStatement = $pdo->prepare('SELECT title FROM works WHERE id = :id');
    $titleStatement->execute([':id' => $workId]);
    $title = $titleStatement->fetchColumn();
    if ($title === false) {
        $message = "[{$workId}] œuvre inconnue";
        fwrite(STDERR, $message . "\n");
        $errors[] = $message;
        $failed++;
        continue;
    }

    try {
        $stats = $importer->importWork($workId, $limit);
        $inserted += (int) $stats['inserted'];
        $skipped += (int) $stats['skipped'];
        $mismatched += (int) $stats['mismatched'];
        fwrite(STDOUT, sprintf(
            "[%d] %s — trouvées: %d, ajoutées: %d, déjà vues/non éligibles: %d, écartées: %d\n",
            $workId,
            $title,
            $stats['found'],
            $stats['inserted'],
            $stats['skipped'],
            $stats['mismatched']
        ));
    } catch (Throwable $error) {
        $message = sprintf("[%d] %s — ERREUR: %s", $workId, $title, $error->getMessage());
        fwrite(STDERR, $message . "\n");
        $errors[] = $message;
        $failed++;
    }
}

$status = $failed === 0 ? 'success' : ($failed < max(1, count($workIds)) ? 'partial' : 'failed');
SyncLog::finish(
    $pdo,
    $runId,
    $status,
    $inserted,
    $skipped,
    $mismatched,
    $errors !== [] ? mb_substr(implode("\n", $errors), 0, 5000) : null
);

exit($failed === 0 ? 0 : 2);
