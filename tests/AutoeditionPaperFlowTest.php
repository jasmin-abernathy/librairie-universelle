<?php
declare(strict_types=1);
$src=file_get_contents(dirname(__DIR__).'/public/autoedition.php');
$css=file_get_contents(dirname(__DIR__).'/public/assets/css/autoedition-paper.css');
$draft=file_get_contents(dirname(__DIR__).'/public/assets/css/author-draft.css');
if(!is_string($src)||!is_string($css)||!is_string($draft))exit(1);
$check=static function(bool $ok,string $msg):void { if(!$ok){fwrite(STDERR,$msg."\n");exit(1);} };
$ids=['paper-interior-step','paper-pdfx-step','paper-covers-step','print-cost-estimator','paper-dossier-step'];
$offset=-1;
foreach($ids as $id){
    $location=strpos($src,'id="'.$id.'"');
    $check($location!==false,"Étape absente : ".$id);
    $check($location>$offset,"Les étapes papier ne suivent pas l'ordre du livre : ".$id);
    $check(str_contains($src,'href="#'.$id.'"'),"Accès à l'étape absent : ".$id);
    $offset=$location;
}
$check(substr_count($src,'href="/export-pdfx.php"')===1,'PDF/X doit apparaître une seule fois dans le parcours papier.');
$check(substr_count($src,'href="/dossier-impression.php"')===1,'Dossier imprimeur dupliqué.');
$check(strpos($src,'id="paper-pdfx-step"')<strpos($src,'id="paper-covers-step"'),'Le PDF/X doit être placé après le manuscrit et avant les couvertures.');
$check(str_contains($src,'for="draft-import"')&&str_contains($src,'aria-describedby="draft-import-name"'),'Import de brouillon non accessible.');
$check(str_contains($draft,'.draft-file-input:focus-visible + .draft-file-label'),'Sélection du fichier inaccessible au clavier.');
$check(str_contains($css,'@media(max-width:600px)')&&str_contains($css,'.paper-step-actions .button-link'),'Rendu mobile des liens manquant.');
echo "Parcours papier, liens et import brouillon : OK\n";
