<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$check=static function(bool $ok,string $why):void {if(!$ok){fwrite(STDERR,"[CGV] ".$why."\n");exit(1);}};
$cgv=(string)file_get_contents($root.'/public/conditions-generales.php');
$nav=(string)file_get_contents($root.'/src/views/site-header.php');
$proposal=(string)file_get_contents($root.'/public/proposer-livre.php');
$ebooks=(string)file_get_contents($root.'/public/ebooks.php');
$contract=(string)file_get_contents($root.'/docs/ACCORD-REFERENCEMENT-CONFIANCE-3-POURCENT.md');
foreach (['3 %','après signature','livres papier et EPUB','trimestrielle','prix unique','Aucune copie','vendeur'] as $needle) {
 if ($needle==='après signature') {$check(str_contains($cgv,'après signature'),$needle);continue;}
 if ($needle==='livres papier et EPUB') {$check(str_contains($cgv,'livres papier et EPUB')||str_contains($cgv,'livre papier ou numérique'),$needle);continue;}
 if ($needle==='trimestrielle') {$check(str_contains($cgv,'trimestrielle'),$needle);continue;}
 if ($needle==='Aucune copie') {$check(str_contains($cgv,'Aucune copie'),$needle);continue;}
 $check(str_contains($cgv,$needle),$needle);
}
$check(str_contains($nav,"'conditions' =>") && str_contains($nav,"'conditions'"),'Conditions générales absentes du menu partagé.');
$check(str_contains($proposal,'href="/conditions-generales.php"') && str_contains($ebooks,'href="/conditions-generales.php"'),'CGV absentes des parcours vendeur.');
$check(str_contains($contract,'livres papier et/ou EPUB autoédités') && str_contains($contract,'https://librairie.lepotager.org/conditions-generales.php'),'Convention non cohérente avec les deux formats.');
$check(str_contains($cgv,'ne s\\'y ajoute ni en copie ni en copie cachée'),'Aucune copie cachée des messages acheteurs.');
echo "CGV : conditions accessibles, prix, 3 %, signature et confidentialité — OK\n";
