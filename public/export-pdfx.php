<?php
declare(strict_types=1);
$config=require dirname(__DIR__).'/src/discovery-bootstrap.php';
require_once dirname(__DIR__).'/src/PdfXExport.php';
require_once dirname(__DIR__).'/src/UploadLimits.php';
require_once dirname(__DIR__).'/src/Csrf.php';
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src \'self\'; script-src \'none\'; style-src \'self\'; img-src \'self\'; connect-src \'none\'; frame-ancestors \'none\'; base-uri \'none\'; object-src \'none\'');
function e(string $s):string{return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$why=PdfXExport::reason($config);
$error=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    if($why!==null)$error=$why;
    elseif(UploadLimits::postExceeded((int)($_SERVER['CONTENT_LENGTH']??0)))
        $error='La requête dépasse la limite du serveur. Choisis un fichier plus petit.';
    elseif(!Csrf::validate($_POST['csrf_token']??null))
        $error='Session expirée. Recharge la page puis réessaie.';
    else{
        $f=$_FILES['source_pdf']??[];
        $version=(string)($_POST['pdfx_version']??'');
        $limit=(int)$config['pdfx_max_bytes'];
        if(!in_array($version,['x4','x1a'],true))$error='Format PDF/X invalide.';
        elseif((int)($f['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)
            $error='Envoi incomplet ou trop volumineux. Vérifie le PDF et sa taille.';
        elseif(!is_uploaded_file((string)($f['tmp_name']??''))||
            (int)($f['size']??0)<64||(int)($f['size']??0)>$limit)
            $error='Le document est absent ou dépasse la limite autorisée.';
        else{
            $dir=rtrim((string)$config['storage_path'],'/').'/pdfx-temp/'.bin2hex(random_bytes(16));
            if(!@mkdir($dir,0700,true))$error='Stockage privé temporaire indisponible.';
            else{
                try{
                    $in=$dir.'/source.pdf';$out=$dir.'/result.pdf';
                    if(!move_uploaded_file((string)$f['tmp_name'],$in))throw new RuntimeException('Import du PDF impossible.');
                    chmod($in,0600);
                    PdfXExport::convert($in,$out,$version,$config);
                    $name=$version==='x4'?'livre-pdfx4-a-verifier.pdf':'livre-pdfx1a-a-verifier.pdf';
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment; filename="'.$name.'"');
                    header('Content-Length: '.filesize($out));
                    header('X-Robots-Tag: noindex, nofollow');
                    readfile($out);
                }catch(Throwable $problem){
                    if(!headers_sent())$error=$problem->getMessage();
                }finally{
                    foreach(glob($dir.'/*')?:[] as $path)@unlink($path);
                    @rmdir($dir);
                }
                if($error===null)exit;
            }
        }
    }
}
$limit=round(min((int)($config['pdfx_max_bytes']??26214400),UploadLimits::effective(
    (int)($config['pdfx_max_bytes']??26214400),
    UploadLimits::iniBytes((string)ini_get('upload_max_filesize')),
    UploadLimits::iniBytes((string)ini_get('post_max_size'))
))/1048576,1);
?><!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="referrer" content="no-referrer">
<title>Export professionnel PDF/X — Librairie universelle</title>
<link rel="stylesheet" href="/assets/css/app.css?v=20261009-2">
<link rel="stylesheet" href="/assets/css/printer-kit.css?v=20261018-1">
</head><body><a class="skip-link" href="#main">Aller au contenu</a>
<?php $currentNav='autoedition';require dirname(__DIR__).'/src/views/site-header.php';?>
<main id="main">
<section class="page-hero compact"><p class="eyebrow">Autoédition · livre papier</p>
<h1>Convertir un PDF en PDF/X pour l'impression</h1>
<p class="lede">Après avoir enregistré ton livre au format PDF standard, tu peux préparer une version PDF/X-4 ou PDF/X-1a. La conversion est distincte de la mise en page et doit rester vérifiée par l'imprimeur.</p>
<p><a href="/autoedition.php?partie=paper">← Revenir à la mise en page</a> · <a href="/dossier-impression.php">Préparer les trois fichiers</a></p>
</section>
<div class="kit-grid">
<section class="kit-form">
<?php if($error!==null):?><div class="error-box" role="alert"><?=e($error)?></div><?php endif;?>
<?php if($why!==null):?>
<div class="kit-warning" role="status"><strong>Export professionnel en attente de configuration</strong>
<p><?=e($why)?> Aucun PDF/X n'est présenté comme certifié tant que le moteur, les limites d'hébergement et le profil ICC ne sont pas disponibles.</p></div>
<?php else:?>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?=e(Csrf::token())?>">
<fieldset><legend>Ton PDF source</legend>
<label>PDF déjà composé et relu
<input type="file" name="source_pdf" accept=".pdf,application/pdf" required>
<small>Maximum <?=e(number_format($limit,1,',',' '))?> Mo. Le fichier est traité temporairement sur l'hébergement pour la conversion, puis supprimé.</small>
</label></fieldset>
<fieldset><legend>Format professionnel</legend>
<label>Version PDF/X
<select name="pdfx_version">
<option value="x4">PDF/X-4 — conserve les transparences lorsque possible</option>
<option value="x1a">PDF/X-1a:2001 — conversion CMJN, transparences aplaties</option>
</select></label>
<p class="kit-note">Le profil ICC CMJN utilisé doit correspondre aux instructions de l'imprimeur. La conversion peut modifier le rendu des couleurs et certains éléments ; vérifie les deux PDF.</p>
</fieldset>
<button class="kit-primary" type="submit">Convertir et télécharger le PDF/X à vérifier</button>
</form>
<?php endif;?>
</section>
<aside class="kit-results"><h2>Ce que signifie « professionnel »</h2>
<p>La conversion utilise Ghostscript et un profil ICC CMJN configuré sur le serveur. Elle peut produire les balises PDF/X et une intention de sortie.</p>
<div class="kit-warning"><strong>Ce n'est pas une certification ISO.</strong><p>Le contrôle automatisé vérifie quelques indicateurs nécessaires. Il ne remplace pas un contrôle PDF/X indépendant, ni la validation des couleurs, fonds perdus, dimensions, polices ou du BAT.</p></div>
<p>Garde toujours ton PDF standard original. Les PDF intérieurs et les deux couvertures restent des documents distincts si ton imprimeur les demande ainsi.</p>
</aside></div>
</main><footer><div class="footer-links"><a href="/autoedition.php">Autoédition</a><a href="/dossier-impression.php">Dossier imprimeur</a></div></footer></body></html>
