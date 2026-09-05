<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/HttpClient.php';
require_once dirname(__DIR__) . '/src/GallicaOpdsSource.php';

$xml = file_get_contents(__DIR__ . '/fixtures/gallica-opds.xml');
if ($xml === false) {
    fwrite(STDERR, "Fixture Gallica introuvable.\n");
    exit(1);
}

$source = new GallicaOpdsSource();
$entries = $source->parse($xml);
if (count($entries) !== 1) {
    fwrite(STDERR, "Nombre d’entrées Gallica inattendu.\n");
    exit(1);
}

$entry = $entries[0];
if ($entry['external_id'] !== 'ark:/12148/bpt6kTEST') {
    fwrite(STDERR, "ARK Gallica mal extrait.\n");
    exit(1);
}
if ($entry['epub_url'] !== 'https://gallica.bnf.fr/ark:/12148/bpt6kTEST.epub') {
    fwrite(STDERR, "URL EPUB Gallica incorrecte.\n");
    exit(1);
}
if (!GallicaOpdsSource::matchesWork($entry, 'Les Misérables', 'Victor Hugo')) {
    fwrite(STDERR, "Rapprochement titre/auteur Gallica refusé à tort.\n");
    exit(1);
}
if (GallicaOpdsSource::matchesWork($entry, 'Notre-Dame de Paris', 'Victor Hugo')) {
    fwrite(STDERR, "Faux rapprochement Gallica accepté.\n");
    exit(1);
}

fwrite(STDOUT, "Gallica OPDS parser: OK\n");
