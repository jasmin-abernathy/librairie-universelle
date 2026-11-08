<?php
declare(strict_types=1);
[$config,$pdo] = require dirname(__DIR__, 2) . '/src/bootstrap.php';
AdminAuth::enforce($config);
header('Cache-Control: private, no-store');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if (!$id) {
    http_response_code(400);
    echo 'Fiche invalide.';
    exit;
}
$book=(new AuthorBookListings($pdo))->contractData((int)$id);
if (!$book) {
    http_response_code(404);
    echo 'Fiche introuvable.';
    exit;
}
require_once dirname(__DIR__,2).'/src/SellerAgreementPdf.php';
try {
    $bytes=SellerAgreementPdf::generate($book);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="convention-referencement-vendeur-' . (int)$id . '.pdf"');
    header('Content-Length: '.strlen($bytes));
    echo $bytes;
} catch (InvalidArgumentException $error) {
    http_response_code(422);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $error->getMessage();
} catch (RuntimeException $error) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Impossible de générer le PDF pour le moment.';
}
