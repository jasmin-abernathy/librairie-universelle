<?php
declare(strict_types=1);
$src=file_get_contents(dirname(__DIR__).'/public/autoedition.php');
$css=file_get_contents(dirname(__DIR__).'/public/assets/css/autoedition-paper.css');
$draft=file_get_contents(dirname(__DIR__).'/public/assets/css/author-draft.css');
if(!is_string($src)||!is_string($css)||!is_string($draft))exit(1);
$check=static function(bool $ok,string $msg):void { if(!$ok){fwrite(STDERR,$msg."\n");exit(1);} };
$check(str_contains($src, 'name="office_manuscript" accept=".docx,.odt,.epub,')
    && str_contains($src, 'Formats acceptés : <strong>.docx, .odt, .epub</strong>')
    && str_contains($src, 'Les fichiers TXT et les EPUB protégés ne sont pas pris en charge.'),
    'Le champ papier doit proposer DOCX, ODT et EPUB sans DRM.');
$previewForInput=(string)file_get_contents(dirname(__DIR__) . '/public/print-preview.php');
$check(str_contains($previewForInput, 'EpubPrintSource::fromUpload($manuscript, $maxBytes)')
    && str_contains($previewForInput, 'OfficePrintSource::fromUpload($manuscript, $maxBytes)')
    && str_contains($previewForInput, "EpubPrintSource::fromUpload(\$_FILES['ebook'],")
    && str_contains($previewForInput, "strtolower(pathinfo("),
    'Le formulaire doit router les EPUB vers leur parseur sans casser les anciens EPUB.');
$check(str_contains($src, 'réglages Corep ou Bookelis')
    && str_contains($src, "sans modifier votre fichier d'origine"),
    'Le générateur EPUB doit conserver le choix Corep/Bookelis et la source originale.');
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
$draftStart=strpos($src,'<section class="paper-draft-toolbar"');
$formEnd=strrpos($src,'</form>');
$check($draftStart!==false && $formEnd!==false && $draftStart>$formEnd,
    'La sauvegarde du brouillon doit être située après le générateur et le formulaire.');
$check(substr_count($src,'id="draft-save"')===1 && substr_count($src,'id="draft-status"')===1,
    'Le bloc brouillon doit rester unique et conserver ses commandes.');

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
$check(PrintSettings::COREP_MAX_PAGES===680 &&
       PrintSettings::COREP_MIN_FONT_PT===9.0 &&
       PrintSettings::COREP_RECOMMENDED_FONT_PT===10.0,
       'Les recommandations Corep 9–10 pt et la limite de 680 pages doivent rester explicites.');
$check(str_contains($src,'680 pages recto-verso') &&
       str_contains($src,'10 pt de préférence') &&
       str_contains($src,'9 pt au minimum') &&
       str_contains($src,'sans traits de coupe ni fonds perdus'),
       'Les contraintes Corep validées par Rachel doivent être visibles dans le parcours.');
$check($settings['trim_size']==='a5' && PrintSettings::trimDimensions($settings['trim_size'])===[148,210],
    'Sans réglage explicite, le générateur doit composer en A5 (148 × 210 mm).');
$check(str_contains($src,'name="print_provider"') && str_contains($src,'data-bookelis-size="1"') &&
    str_contains($src,'<option value="a5"'),
    'Le choix Corep/Bookelis et les formats du profil doivent être disponibles.');
$bookelis=PrintSettings::normalize(['print_provider'=>'bookelis','print_trim_size'=>'a4']);
$check($bookelis['provider']==='bookelis' && PrintSettings::trimDimensions($bookelis['trim_size'])===[210,297],
    'Le profil Bookelis doit accepter A4 sans assouplir Corep.');
$check(PrintSettings::normalize(['print_provider'=>'bookelis'])['trim_size']==='a5',
    'Le profil Bookelis doit démarrer en A5 pour la comparaison.');
$rejected=false;
try {PrintSettings::normalize(['print_provider'=>'bookelis','print_trim_size'=>'custom']); }
catch(InvalidArgumentException $e){$rejected=true;}
$check($rejected,'Aucun format arbitraire Bookelis ne doit être accepté.');
$rejected=false;
try {PrintSettings::normalize(['print_provider'=>'bookelis','print_bleed_mm'=>3]); }
catch(InvalidArgumentException $e){$rejected=true;}
$check($rejected,'Pas de faux fond perdu validé pour Bookelis.');
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
$preview=(string)file_get_contents(dirname(__DIR__).'/public/print-preview.php');
$previewJS=(string)file_get_contents(dirname(__DIR__).'/public/assets/js/print-preview.js');
$previewCSS=(string)file_get_contents(dirname(__DIR__).'/public/assets/css/print-preview.css');
$check(str_contains($preview,'data-corep-max-pages="<?= PrintSettings::COREP_MAX_PAGES ?>"') &&
       str_contains($preview,"if (\$settings['provider'] === 'corep_metz')") &&
       str_contains($preview,'Limite : <?= PrintSettings::COREP_MAX_PAGES ?> pages A5 en recto-verso'),
       'La limite Corep doit être transmise à la pagination et affichée au bon profil.');
$check(str_contains($previewJS,"corepOverLimit=isCorep && pages>corepMaxPages;") &&
       str_contains($previewJS,'critical=pages<1||pageEls.length!==pages||missingImages.length>0||corepOverLimit;') &&
       str_contains($previewJS,"const isCorep=document.body.dataset.printProvider==='corep_metz';") &&
       str_contains($previewJS,"reviewStatus.textContent=corepOverLimit"),
       'Le dépassement de 680 pages doit empêcher l’export Corep mais pas l’export Bookelis.');
$check(str_contains($previewJS,'Corps du texte recomposé en 10,5 pt') &&
       str_contains($previewCSS,'body[data-print-provider="corep_metz"] .print-book code{font-size:9pt}') &&
       str_contains($previewCSS,'@media print{.corep-preset-note{display:none!important}}'),
       'Les règles Corep de taille de police doivent être visibles et non imprimées en tant que notice.');
$check(str_contains($preview,"if (\$settings['provider'] === 'bookelis')") &&
       str_contains($preview,'id="bookelis-cover-handoff"') &&
       str_contains($preview,'id="bookelis-real-pages"') &&
       str_contains($preview,'https://livres.bookelis.com/generation-couverture'),
       'Le parcours Bookelis doit proposer le générateur officiel après la pagination.');
$check(str_contains($preview,'id="bookelis-copy-pages"') &&
       str_contains($preview,'id="bookelis-copy-summary"') &&
       str_contains($preview,'id="bookelis-copy-feedback"') &&
       str_contains($preview,'id="bookelis-transfer-format"'),
       'Le transfert Bookelis doit proposer la copie des informations et afficher le format.');
$check(str_contains($previewJS,"copyBookelisText(String(actualPages)") &&
       str_contains($previewJS,"bookelisTransferPages.textContent=String(pages)") &&
       str_contains($previewJS,"navigator.clipboard.writeText(value)"),
       'La copie doit reprendre le nombre de pages paginées, non une estimation.');
$check(str_contains($previewCSS,'.bookelis-transfer-data') &&
       str_contains($previewCSS,'@media print{.bookelis-transfer{display:none!important}}'),
       'Le panneau de transfert doit être accessible et exclu de l’impression.');
$check(str_contains($preview,'quatrième, tranche et première') &&
       str_contains($preview,'Aucun fichier n\'est envoyé automatiquement'),
       'La couverture complète et la transmission manuelle doivent être expliquées.');
$check(str_contains($previewJS,"bookelisHandoff.hidden=button.disabled") &&
       str_contains($previewJS,"bookelisPages.textContent=String(pages)"),
       'La suite Bookelis doit attendre la validation de l’aperçu et afficher le nombre réel de pages.');
$check(str_contains($previewCSS,'.bookelis-cover-handoff[hidden]{display:none!important}') &&
       str_contains($previewCSS,'@media print{.bookelis-cover-handoff{display:none!important}}'),
       'Le guidage Bookelis doit être invisible dans le PDF intérieur.');
$check(str_contains($preview,"if (\$settings['provider'] !== 'bookelis')"),
       'L’export PDF/X optionnel doit rester dans le parcours Corep, pas être requis chez Bookelis.');
$check(str_contains($src,'<legend>Publication papier : préparation et impression</legend>'),
    'La légende sémantique du parcours papier ne doit pas être remplacée par un titre non associé.');
$check(preg_match('/#panel-paper\s*>\s*fieldset\s*>\s*legend\s*\{[^}]*float\s*:\s*left\s*;[^}]*width\s*:\s*100%/s',$css)===1,
    'Le titre papier doit être positionné dans le cadre sur mobile et ordinateur.');
$check(preg_match('/#panel-paper\s*>\s*fieldset\s*>\s*\.paper-step-nav\s*\{[^}]*clear\s*:\s*both/s',$css)===1,
    'Les raccourcis doivent passer sous la légende flottante, pas à côté.');
$wizard=(string)file_get_contents(dirname(__DIR__).'/public/assets/js/paper-wizard.js');
$apache=(string)file_get_contents(dirname(__DIR__).'/public/.htaccess');
$analyze=(string)file_get_contents(dirname(__DIR__).'/public/analyser-pdf.php');
$check(str_contains($src,'/assets/js/paper-wizard.js') && str_contains($src,'/assets/css/paper-wizard.css'),
    'Le formulaire papier doit activer son assistant dynamique.');
$check(str_contains($wizard,'wizard-barcode-download') &&
       str_contains($wizard,'wizard-plan-bookstores') &&
       str_contains($wizard,'wizard-build-kit') &&
       str_contains($wizard,'paper_wizard_final_iframe'),
    'Le parcours guidé doit proposer ISBN, prix public unique et kit dans la même page.');
$check(str_contains($wizard,'Le kit doit contenir')===false && str_contains($wizard,'finalScreen.appendChild') &&
       str_contains($wizard,'pagesField.readOnly=true'),
    'Étape finale guidée et pagination calculée non modifiable attendues.');
$check(str_contains($apache,'Header always set X-Frame-Options "SAMEORIGIN"') &&
       str_contains($apache,"frame-ancestors 'self'") &&
       str_contains($apache,"frame-src 'self' blob:"),
    'Apache doit autoriser les aperçus internes sans autoriser les cadres tiers.');
$check(str_contains($analyze,"Csrf::validate") && str_contains($analyze,"proc_open") &&
       str_contains($analyze,"pdfinfo") && str_contains($analyze,"corep_eligible"),
    'L’analyse native PDF doit rester temporaire, protégée et utile à Corep.');
echo "Parcours papier, liens et import brouillon : OK\n";
