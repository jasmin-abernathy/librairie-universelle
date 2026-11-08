<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/Csrf.php';
require_once dirname(__DIR__) . '/src/UploadLimits.php';
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
$f = $_FILES['print_pdf'] ?? [];
if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
    || !is_uploaded_file((string)($f['tmp_name']??''))
    || (int)($f['size']??0)<100
    || (int)($f['size']??0)>30*1048576) {
    answer(['error'=>'Choisissez un PDF de moins de 30 Mo.'],422);
}
$source=(string)$f['tmp_name'];
$handle=@fopen($source,'rb');
$signature=$handle ? fread($handle,5) : '';
if($handle)fclose($handle);
if($signature!=='%PDF-')answer(['error'=>'Ce fichier ne présente pas une signature PDF valide.'],422);
$exe=null;
foreach(['/usr/bin/pdfinfo','/bin/pdfinfo'] as $candidate){
    if(is_executable($candidate)){$exe=$candidate;break;}
}
if($exe===null||!function_exists('proc_open'))answer(['error'=>'Analyse PDF non disponible sur cet hébergement. Indiquez le nombre de pages manuellement et faites confirmer les dimensions par l’imprimeur.'],503);
$process=@proc_open([$exe,'-box',$source],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,null,['bypass_shell'=>true]);
if(!is_resource($process))answer(['error'=>'Impossible de démarrer l’analyse PDF.'],503);
$out=stream_get_contents($pipes[1],65536);fclose($pipes[1]);
$stderr=stream_get_contents($pipes[2],8192);fclose($pipes[2]);
$code=proc_close($process);
if($code!==0||!is_string($out))answer(['error'=>'PDF non analysable ou protégé. Vérifiez le fichier original.'],422);
if(!preg_match('/^Pages:\\s*(\\d+)/mi',$out,$m) ||
   !preg_match('/^Page size:\\s*([0-9.]+)\\s*x\\s*([0-9.]+)\\s*pts/mi',$out,$s)){
    answer(['error'=>'Pagination ou format PDF indéterminé.'],422);
}
$pages=(int)$m[1];$width=round((float)$s[1]*25.4/72,1);$height=round((float)$s[2]*25.4/72,1);
if($pages<1||$pages>5000||$width<30||$height<30||$width>1000||$height>1000)answer(['error'=>'Dimensions ou pagination hors limites.'],422);
$formats=['a5'=>[148,210],'130x180'=>[130,180],'156x234'=>[156,234],'170x244'=>[170,244],'170x170'=>[170,170],'190x150'=>[190,150],'a4'=>[210,297]];
$match=null;
foreach($formats as $key=>$dimensions){if(abs($width-$dimensions[0])<=1.5&&abs($height-$dimensions[1])<=1.5){$match=$key;break;}}
answer(['pages'=>$pages,'width_mm'=>$width,'height_mm'=>$height,'trim'=>$match,
  'corep_eligible'=>($match==='a5'&&$pages<=680),
  'warnings'=>['Format relevé sur la première page : vérifier les autres pages, les boîtes de coupe, les polices, les illustrations et les fonds perdus.','Aucune recomposition du PDF source n’a été effectuée.']]);
