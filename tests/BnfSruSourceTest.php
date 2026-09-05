<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/CatalogSource.php';
require_once dirname(__DIR__) . '/src/HttpClient.php';
require_once dirname(__DIR__) . '/src/BnfSruSource.php';

$fixture = file_get_contents(__DIR__ . '/fixtures/bnf-sru-1984.xml');
if ($fixture === false) {
    fwrite(STDERR, "Fixture BnF introuvable\n");
    exit(1);
}

$requestedUrl = null;
$source = new BnfSruSource(static function (string $url) use ($fixture, &$requestedUrl): string {
    $requestedUrl = $url;
    return $fixture;
});

$records = $source->searchEditions('1984', 'George Orwell', 10);

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "Échec: {$message}\n");
        exit(1);
    }
};

$assert(count($records) === 2, 'deux notices doivent être parsées');
$assert($records[0]['external_id'] === 'ark:/12148/cb465691674', 'ARK de la notice 2020');
$assert($records[0]['isbn13'] === '9782072878497', 'ISBN13 2020 normalisé');
$assert($records[0]['language'] === 'fr', 'langue fre → fr');
$assert($records[0]['title'] === '1984', 'mention matérielle nettoyée du titre');
$assert($records[0]['contributors'][0]['name'] === 'Kamoun, Josée', 'contributrice conservée');
$assert($records[1]['isbn13'] === '9782070248100', 'ISBN10 converti en ISBN13');
$assert(str_contains((string) $requestedUrl, 'catalogue.bnf.fr/api/SRU'), 'endpoint SRU BnF');
$assert(str_contains(urldecode((string) $requestedUrl), 'George Orwell'), 'auteur présent dans la requête CQL');
$assert(str_contains(urldecode((string) $requestedUrl), '1984'), 'titre présent dans la requête CQL');

fwrite(STDOUT, "BnF SRU parser: OK\n");
