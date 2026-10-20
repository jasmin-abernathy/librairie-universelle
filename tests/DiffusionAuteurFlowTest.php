<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$page=(string)file_get_contents($root.'/public/diffuser-mon-livre.php');
$auto=(string)file_get_contents($root.'/public/autoedition.php');
$header=(string)file_get_contents($root.'/src/views/site-header.php');
$kit=(string)file_get_contents($root.'/public/dossier-impression.php');
$service=(string)file_get_contents($root.'/src/SelfPublishingService.php');
$check=static function(bool $ok,string $why):void{if(!$ok){fwrite(STDERR,$why."\n");exit(1);}};
$check(str_contains($page,'id="sales-pitch-form"') && str_contains($page,'id="sales-output-result"'), 'Fiche libraire et message absents du guide.');
$check(str_contains($page,'/#librairies'), 'Recherche des librairies locales absente du parcours.');
$check(str_contains($page,'après acceptation de votre dépôt par une personne'), 'Risque de présenter le dépôt comme une publication automatique non vérifiée.');
$check(str_contains($page,"ne signifie pas que le livre est achetable en ligne"), 'Vente en ligne promise alors que le parcours de commande manque.');
$check(str_contains($page,"Rien n'est envoyé ni enregistré sur notre serveur"), 'Confidentialité du formulaire local non indiquée.');
$check(str_contains($auto,'href="/diffuser-mon-livre.php"') && str_contains($kit,'href="/diffuser-mon-livre.php"') && str_contains($header,"'diffusion'"), 'Parcours de vente non accessible depuis les pages auteur.');
$check(str_contains($service,"if ($". "submission['status'] !== 'approved')"), 'Modération avant publication manquante.');
$check(str_contains($service,"$". "this->pdo->commit();"), 'Publication cataloguée non transactionnelle.');
echo "Parcours vente locale, publication contrôlée et relais depuis l’autoédition : OK\n";
