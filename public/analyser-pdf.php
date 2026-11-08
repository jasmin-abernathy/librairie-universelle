<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Csrf.php';
require_once dirname(__DIR__) . '/src/UploadLimits.php';
require_once dirname(__DIR__) . '/src/PdfNativeAnalyzer.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

function answer(array $data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') answer(['error'=>'Méthode invalide.'],405);
if (UploadLimits::postExceeded((int)($_SERVER['CONTENT_LENGTH']??0))) answer(['error'=>'PDF trop volumineux pour le serveur.'],413);
if (!Csrf::validate($_POST['csrf_token'] ?? null)) answer(['error'=>'Session expirée. Rechargez la page.'],403);
$limit=UploadLimits::effective(100*1048576,
    UploadLimits::iniBytes((string)ini_get('upload_max_filesize')),
    UploadLimits::iniBytes((string)ini_get('post_max_size')));
$f = $_FILES['print_pdf'] ?? [];
if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
    || !is_uploaded_file((string)($f['tmp_name']??''))
    || (int)($f['size']??0)<100
    || (int)($f['size']??0)>$limit) {
    answer(['error'=>'Choisissez un PDF ne dépassant pas '.round($limit/1048576).' Mo (limite effective du serveur).'],422);
}
$source=(string)$f['tmp_name'];
$handle=@fopen($source,'rb');
$signature=$handle ? fread($handle,5) : '';
if($handle)fclose($handle);
if($signature!=='%PDF-')answer(['error'=>'Ce fichier ne présente pas une signature PDF valide.'],422);
try {
    answer(PdfNativeAnalyzer::inspect($source));
} catch (Throwable $error) {
    answer(['error'=>$error->getMessage()],422);
}
