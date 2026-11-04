<?php
declare(strict_types=1);
require dirname(__DIR__,2) . '/src/discovery-bootstrap.php';
require_once dirname(__DIR__,2) . '/src/Csrf.php';
require_once dirname(__DIR__,2) . '/src/OfficialSiretLookup.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex');
function respond(int $code, array $payload): never {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respond(405, ['ok'=>false,'error'=>'Méthode non autorisée.']);
}
if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
    respond(403, ['ok'=>false,'error'=>'Session expirée. Actualisez le formulaire.']);
}
if (!isset($_SESSION['siret_search_history']) || !is_array($_SESSION['siret_search_history'])) {
    $_SESSION['siret_search_history'] = [];
}
$_SESSION['siret_search_history'] = array_values(array_filter(
    $_SESSION['siret_search_history'],
    static fn(mixed $timestamp): bool => is_int($timestamp) && $timestamp > time()-600
));
if (count($_SESSION['siret_search_history']) >= 10) {
    respond(429, ['ok'=>false,'error'=>'Trop de recherches. Réessayez dans quelques minutes.']);
}
$_SESSION['siret_search_history'][] = time();
try {
    if (!is_string($_POST['siret'] ?? null)) {
        throw new InvalidArgumentException('SIRET invalide.');
    }
    $company = (new OfficialSiretLookup())->search($_POST['siret']);
    respond(200, ['ok'=>true,'results'=>[$company]]);
} catch (InvalidArgumentException $e) {
    respond(422, ['ok'=>false,'error'=>$e->getMessage()]);
} catch (DomainException $e) {
    respond(404, ['ok'=>false,'error'=>$e->getMessage()]);
} catch (RuntimeException $e) {
    respond(503, ['ok'=>false,'error'=>$e->getMessage()]);
}
