<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$check=static function(bool $ok,string $why):void{if(!$ok){fwrite(STDERR,$why."\n");exit(1);}};
$page=(string)file_get_contents($root.'/public/amazon-et-librairies.php');
$header=(string)file_get_contents($root.'/src/views/site-header.php');
$guide=(string)file_get_contents($root.'/public/diffuser-mon-livre.php');
$sitemap=(string)file_get_contents($root.'/public/sitemap.php');
$js=(string)file_get_contents($root.'/public/assets/js/amazon-local.js');
$css=(string)file_get_contents($root.'/public/assets/css/amazon-local.css');
$check(str_contains($page,'KDP n&#39;est pas exclusive')===false && str_contains($page,"KDP n'est pas exclusive"),'Explication papier KDP absente.');
$check(str_contains($page,'KDP Select')&&str_contains($page,'90 jours'),'Attention ebook et durée absentes.');
$check(str_contains($page,'exemplaires d\'auteur')&&str_contains($page,"Non destiné à la vente"),'Revente auteur et distinction épreuves absentes.');
$check(str_contains($page,'ISBN gratuit')&&str_contains($page,'AFNIL'),'ISBN non expliqué.');
$check(str_contains($page,'l\'autre prestataire')===false && str_contains($page,'/autoedition.php?partie=paper'),'Parcours impression absent.');
$check(str_contains($page,'/diffuser-mon-livre.php')&&str_contains($page,'/#librairies')&&str_contains($page,'/proposer-livre.php'),'Parcours libraire et catalogue absents.');
$check(str_contains($page,'id="amazon-local-calculator"')&&str_contains($page,'id="amazon-comparison-status"'),'Simulateur inaccessible.');
$check(str_contains($page,'/assets/css/amazon-local.css?v=20261110-1')&&str_contains($page,'/assets/js/amazon-local.js?v=20261110-1'),'Références des assets incorrectes.');
$check(str_contains($js,'publicPrice/1.055')&&str_contains($js,'publicPrice*0.03')&&str_contains($js,'beforeTax>=9.99?0.60:0.50'),'Calculs TVA, commission ou KDP incorrects.');
$check(str_contains($page,'sans transmission des montants')&&str_contains($page,'pas un bénéfice net'),'Limites du simulateur absentes.');
$check(str_contains($page,'le local n\'est pas')===false && str_contains($page,"n'est pas automatiquement"),'La prudence écologique doit rester explicite.');
$check(str_contains($header,"'amazon'")&&str_contains($guide,'href="/amazon-et-librairies.php"')&&str_contains($sitemap,"'/amazon-et-librairies.php'"),'Page non découvrable depuis navigation, guide et sitemap.');
$check(str_contains($css,'@media(max-width:620px)')&&str_contains($css,':focus-visible'),'CSS mobile et focus manquants.');
foreach (['GHKDSCW2KQ3K4UU4','G201834170','G7BBN68RYX5UMDZF','GD9PMU58BV24QFZ7'] as $source) {
    $check(str_contains($page,$source),'Source officielle Amazon manquante : '.$source);
}
echo "Amazon / librairies : droits, parcours, sources, SEO et simulateur : OK\n";
