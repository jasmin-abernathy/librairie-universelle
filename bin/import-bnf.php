<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Ce script doit être exécuté en ligne de commande.\n");
    exit(1);
}

[$config, $pdo] = require dirname(__DIR__) . '/src/bootstrap.php';

$options = getopt('', ['work::', 'all', 'limit::']);
$limit = isset($options['limit']) ? max(1, min(100, (int) $options['limit'])) : 20;

$sourceId = $pdo->query("SELECT id FROM sources WHERE name = 'Bibliothèque nationale de France' LIMIT 1")->fetchColumn();
if ($sourceId === false) {
    fwrite(STDERR, "Source BnF absente de la base.\n");
    exit(1);
}

if (!isset($options['all']) && !isset($options['work'])) {
    fwrite(STDOUT, "Usage :\n");
    fwrite(STDOUT, "  php bin/import-bnf.php --work=2\n");
    fwrite(STDOUT, "  php bin/import-bnf.php --all --limit=20\n");
    exit(0);
}

$workIds = [];
if (isset($options['all'])) {
    $workIds = array_map('intval', $pdo->query('SELECT id FROM works ORDER BY id')->fetchAll(PDO::FETCH_COLUMN));
} else {
    $workIds = [(int) $options['work']];
}

$source = new BnfSruSource();
$importer = new CatalogImporter($pdo, $source, (int) $sourceId);
$failed = 0;

foreach ($workIds as $workId) {
    $titleStatement = $pdo->prepare('SELECT title FROM works WHERE id = :id');
    $titleStatement->execute([':id' => $workId]);
    $title = $titleStatement->fetchColumn();

    if ($title === false) {
        fwrite(STDERR, "[{$workId}] œuvre inconnue\n");
        $failed++;
        continue;
    }

    try {
        $stats = $importer->importWork($workId, $limit);
        fwrite(STDOUT, sprintf(
            "[%d] %s — trouvées: %d, ajoutées: %d, reliées: %d, déjà vues: %d, conflits: %d\n",
            $workId,
            $title,
            $stats['found'],
            $stats['inserted'],
            $stats['linked'],
            $stats['skipped'],
            $stats['conflicts']
        ));
    } catch (Throwable $error) {
        fwrite(STDERR, sprintf("[%d] %s — ERREUR: %s\n", $workId, $title, $error->getMessage()));
        $failed++;
    }
}

exit($failed === 0 ? 0 : 2);
