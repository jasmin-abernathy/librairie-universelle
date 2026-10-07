<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$config = require dirname(__DIR__, 2) . '/src/discovery-bootstrap.php';

if (empty($config['federated_search_enabled'])) {
    http_response_code(404);
    echo json_encode(['error'=>'Recherche fédérée désactivée.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$query = trim((string) ($_GET['q'] ?? ''));
$sourceKey = trim((string) ($_GET['source'] ?? ''));
$requestedLanguages = array_map(
    static fn (string $value): string => mb_strtolower(trim($value), 'UTF-8'),
    explode(',', (string) ($_GET['lang'] ?? 'fr,en'))
);
$languages = array_values(array_intersect(['fr', 'en'], array_unique($requestedLanguages)));
if ($languages === []) {
    $languages = ['fr', 'en'];
}

if (mb_strlen($query, 'UTF-8') < 2 || mb_strlen($query, 'UTF-8') > 120) {
    http_response_code(400);
    echo json_encode(['error'=>'Recherche invalide.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!in_array($sourceKey, DiscoveryRegistry::keys(), true)) {
    http_response_code(400);
    echo json_encode(['error'=>'Source inconnue.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$definition = DiscoveryRegistry::definitions()[$sourceKey];
if (!DiscoveryRegistry::supportsLanguages($sourceKey, $languages)) {
    echo json_encode([
        'source'=>['key'=>$sourceKey, 'name'=>$definition['name']],
        'results'=>[],
        'skipped'=>true,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $http = new HttpClient(
        max(2, min(8, (int) ($config['discovery_timeout_seconds'] ?? 5))),
        'LibrairieUniverselle/0.2 (+https://librairie.lepotager.org; contact: jasmin@lepotager.org)',
        1
    );
    $source = DiscoveryRegistry::create($sourceKey, $http);
    $cache = new DiscoveryCache((string) $config['storage_path'], (int) ($config['discovery_cache_ttl'] ?? 1800));
    $payload = $cache->remember($source->key(), $query, static fn (): array => $source->search($query, 8));
    $results = DiscoveryRegistry::filterResults($sourceKey, $payload['results'], $languages);

    echo json_encode([
        'source'=>['key'=>$source->key(), 'name'=>$source->name()],
        'results'=>$results,
        'cached'=>$payload['cached'],
        'languages'=>$languages,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    http_response_code(200);
    echo json_encode([
        'source'=>['key'=>$sourceKey, 'name'=>$definition['name'] ?? $sourceKey],
        'results'=>[],
        'error'=>'Source temporairement indisponible.',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
