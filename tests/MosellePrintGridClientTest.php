<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/MosellePrintGridClient.php';

$assert = static function(bool $condition, string $message): void {
    if (!$condition) { fwrite(STDERR, $message . "\n"); exit(1); }
};
$client = new MosellePrintGridClient('');
$assert($client->publishedGrids() === [], 'Aucune grille ne doit être inventée sans connexion Moselle.');
$assert(MosellePrintGridClient::normalizeResponse(['grids'=>[]]) === [], 'Liste vide attendue.');
$valid = ['grids'=>[[
  'printer'=>'Exemple validé', 'currency'=>'EUR', 'tax_included'=>true,
  'valid_until'=>'2099-12-31', 'amounts_cents'=>['200'=>['30'=>29151]]
]]];
$parsed = MosellePrintGridClient::normalizeResponse($valid);
$assert(count($parsed)===1 && $parsed[0]['amounts_cents']['200']['30']===29151, 'Montant précis perdu.');
$assert(!isset($parsed[0]['amounts_cents']['180']), 'Interdiction de fabriquer des tarifs intermédiaires.');
$invalid = $valid;
$invalid['grids'][0]['tax_included'] = false;
$assert(MosellePrintGridClient::normalizeResponse($invalid) === [], 'Un devis HT ne doit pas être pris pour du TTC.');
$invalid = $valid;
$invalid['grids'][0]['valid_until'] = '2020-01-01';
$assert(MosellePrintGridClient::normalizeResponse($invalid) === [], 'Grille périmée visible.');
echo "MosellePrintGridClient: OK\n";
