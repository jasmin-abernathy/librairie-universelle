<?php
declare(strict_types=1);

/** Contrôle temporaire du PDF de gabarit, sans stockage du document. */
require_once dirname(__DIR__) . '/src/Csrf.php';
require_once dirname(__DIR__) . '/src/UploadLimits.php';
require_once dirname(__DIR__) . '/src/PdfNativeAnalyzer.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

function bookelisAnswer(array $payload, int $status=200): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    bookelisAnswer(['error'=>'Méthode non autorisée.'],405);
}
if (UploadLimits::postExceeded((int)($_SERVER['CONTENT_LENGTH']??0))) {
    bookelisAnswer(['error'=>'Le gabarit dépasse la limite d’envoi du serveur.'],413);
}
if (!Csrf::validate($_POST['csrf_token']??null)) {
    bookelisAnswer(['error'=>'Session expirée : rechargez la page puis réimportez le gabarit.'],403);
}
$limit=UploadLimits::effective(12*1048576,
    UploadLimits::iniBytes((string)ini_get('upload_max_filesize')),
    UploadLimits::iniBytes((string)ini_get('post_max_size')));
$file=$_FILES['bookelis_template']??[];
if (($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK
    || !is_uploaded_file((string)($file['tmp_name']??''))
    || (int)($file['size']??0)<100
    || (int)($file['size']??0)>$limit
    || strtolower(pathinfo((string)($file['name']??''),PATHINFO_EXTENSION))!=='pdf') {
    bookelisAnswer(['error'=>'Sélectionnez un gabarit PDF de moins de '.round($limit/1048576).' Mo.'],422);
}
$path=(string)$file['tmp_name'];
$handle=@fopen($path,'rb');
$header=$handle?fread($handle,5):'';
if ($handle)fclose($handle);
if ($header!=='%PDF-') {
    bookelisAnswer(['error'=>'Le fichier sélectionné ne comporte pas de signature PDF.'],422);
}
try {
    $analysis=PdfNativeAnalyzer::inspect($path);
    if ($analysis['pages']!==1 || $analysis['mixed_sizes']) {
        throw new RuntimeException('Le gabarit de couverture doit comporter une seule page.');
    }
    bookelisAnswer([
        'pages'=>1,
        'width_mm'=>$analysis['width_mm'],
        'height_mm'=>$analysis['height_mm'],
        'notice'=>'Dimensions du PDF vérifiées. Son origine Bookelis et ses repères ne peuvent pas être certifiés automatiquement.'
    ]);
} catch (Throwable $error) {
    bookelisAnswer(['error'=>$error->getMessage()],422);
}
