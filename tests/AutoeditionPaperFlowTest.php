<?php
declare(strict_types=1);
$src=file_get_contents(dirname(__DIR__).'/public/autoedition.php');
$css=file_get_contents(dirname(__DIR__).'/public/assets/css/autoedition-paper.css');
$draft=file_get_contents(dirname(__DIR__).'/public/assets/css/author-draft.css');
if(!is_string($src)||!is_string($css)||!is_string($draft))exit(1);
$check=static function(bool $ok,string $msg):void { if(!$ok){fwrite(STDERR,$msg."\n");exit(1);} };
$ids=['paper-interior-step','paper-pdfx-step','paper-covers-step','print-cost-estimator','paper-dossier-step','paper-corep-step'];
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
$coverPage=(string)file_get_contents(dirname(__DIR__).'/public/assistant-papier.php');
$coverJS=(string)file_get_contents(dirname(__DIR__).'/public/assets/js/paper-assistant.js');
$check(str_contains($coverPage,'id="paper-export-format-help"') &&
    str_contains($coverPage,'A5 (148 × 210 mm)') &&
    str_contains($coverPage,'et non A4') &&
    str_contains($coverPage,'marges « Aucune »') &&
    str_contains($coverPage,'Le format A5 est imposé à cette étape'),
    'La boîte de dialogue doit rappeler A5, le risque A4, les marges et les autres formats.');
$check(str_contains($coverJS,"el('paper-sheet-size').textContent") &&
    str_contains($coverJS,"'@media print{@page{size:'"),
    'Le format demandé au navigateur doit rester synchronisé avec le livre.');
require_once dirname(__DIR__).'/src/PrintSettings.php';
$settings=PrintSettings::normalize([]);
$check($settings['trim_size']==='a5' && PrintSettings::trimDimensions($settings['trim_size'])===[148,210],
    'Sans réglage explicite, le générateur doit composer en A5 (148 × 210 mm).');
$check(str_contains($src,'<option value="a5" selected>') && !str_contains($src,'<option value="140x210"'),
    'L’interface de composition ne doit proposer que A5.');
$check(str_contains($coverPage,'<option value="148x210" selected>') && !str_contains($coverPage,'<option value="140x210"'),
    'L’assistant de couverture ne doit proposer que A5.');
$blocked=false;
try { PrintSettings::normalize(['print_trim_size'=>'140x210']); }
catch(InvalidArgumentException $e) { $blocked=true; }
$check($blocked, 'Un format 14 × 21 cm soumis manuellement ne doit pas passer.');
$blocked=false;
try { PrintSettings::normalize(['print_trim_size'=>'a4']); }
catch(InvalidArgumentException $e) { $blocked=true; }
$check($blocked, 'Un A4 soumis manuellement ne doit pas passer.');
$coverJs=(string)file_get_contents(dirname(__DIR__).'/public/assets/js/paper-assistant.js');
$check(str_contains($coverJs,"items['paper-size'].value = '148x210'") &&
       str_contains($coverJs,"const formats = { '148x210':[148,210] };"),
       'Un ancien brouillon ne doit pas restaurer un format différent.');
$check(str_contains($src,'<legend>Publication papier : préparation et impression</legend>'),
    'La légende sémantique du parcours papier ne doit pas être remplacée par un titre non associé.');
$check(preg_match('/#panel-paper\s*>\s*fieldset\s*>\s*legend\s*\{[^}]*float\s*:\s*left\s*;[^}]*width\s*:\s*100%/s',$css)===1,
    'Le titre papier doit être positionné dans le cadre sur mobile et ordinateur.');
$check(preg_match('/#panel-paper\s*>\s*fieldset\s*>\s*\.paper-step-nav\s*\{[^}]*clear\s*:\s*both/s',$css)===1,
    'Les raccourcis doivent passer sous la légende flottante, pas à côté.');
echo "Parcours papier, liens et import brouillon : OK\n";
