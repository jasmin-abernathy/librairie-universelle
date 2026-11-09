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
$check(str_contains($page,'après examen et publication de la fiche'), 'La fiche doit être examinée avant publication.');
$proposal=(string)file_get_contents($root.'/public/proposer-livre.php');
$authors=(string)file_get_contents($root.'/public/livres-auteurs.php');
$check(str_contains($proposal,'Catalogue indépendant · livres d’auteurs') &&
       !str_contains($proposal,'référencement humain') &&
       !str_contains($authors,'vérification humaine') &&
       !str_contains($auto,'filtrée humainement'),
       'Les titres publics ne doivent pas annoncer une validation robotique par opposition à une validation humaine.');
$check(str_contains($page,'ne gère ni paiement, ni livraison, ni réservation'), 'Vente en ligne promise sans commerce intégré.');
$check(str_contains($page,"Rien n'est envoyé ni enregistré sur notre serveur"), 'Confidentialité du formulaire local non indiquée.');
$check(str_contains($auto,'href="/diffuser-mon-livre.php"') && str_contains($kit,'href="/diffuser-mon-livre.php"') && str_contains($header,"'diffusion'"), 'Parcours de vente non accessible depuis les pages auteur.');
$check(str_contains($service,"if ($". "submission['status'] !== 'approved')"), 'Modération avant publication manquante.');
$check(str_contains($service,"$". "this->pdo->commit();"), 'Publication cataloguée non transactionnelle.');
$paid=(string)file_get_contents($root.'/public/prestations-auteurs.php');
$check(str_contains($paid,'79 €') && str_contains($paid,'179 €'), 'Les deux tarifs ne sont pas publiés.');
$project=(string)file_get_contents($root.'/public/projet.php');
foreach ([$paid,$project,$page] as $pricingPage) {
    $check(str_contains($pricingPage,'30 pages A4') && str_contains($pricingPage,'9 000 mots'),
        'La limite de pages et de mots du forfait de relecture doit apparaître dans tous les parcours.');
}
$check(str_contains($paid,'$correctionMailBody') &&
       str_contains($paid,'PIÈCE JOINTE À AJOUTER AVANT ENVOI') &&
       str_contains($paid,'rawurlencode($correctionMailBody)') &&
       str_contains($paid,'ne transmet') === false &&
       str_contains($paid,'DOCX, ODT ou EPUB sans DRM') &&
       str_contains($project,'DOCX, ODT ou EPUB') &&
       str_contains($page,'DOCX, ODT ou EPUB original'),
       'Le manuscrit original doit être explicitement demandé pour la relecture, sans promettre une pièce jointe automatique.');
$check(str_contains($paid,'Je relis et corrige personnellement') &&
       str_contains($paid,'correcteur logiciel pour une dernière vérification') &&
       !str_contains($paid,"première passe d'orthographe, de grammaire et de typographie assistée par logiciel"),
       'La relecture personnelle doit précéder le dernier contrôle logiciel.');
$check(str_contains($paid,'mailto:contact@lepotager.org') && str_contains($paid,'https://www.lepotager.org/contact/'), 'Les demandes de devis ne sont pas accessibles.');
$check(str_contains($paid,'avant votre accord') && str_contains($paid,'restent gratuits'), 'Les conditions de paiement ou la gratuité ne sont pas claires.');
$check(str_contains($auto,'/prestations-auteurs.php') && str_contains($kit,'/prestations-auteurs.php') && str_contains($page,'/prestations-auteurs.php'), 'Offres non reliées aux parcours auteurs.');
// Éviter sur tout le site public les tournures qui suggèrent une validation
// « humaine » par opposition à une plateforme robotique. Ne pas réintroduire
// « vérification humaine », « examiné humainement », etc., dans les textes.
$publicFiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root.'/public', FilesystemIterator::SKIP_DOTS)
);
foreach ($publicFiles as $publicFile) {
    if (!$publicFile->isFile()
        || !in_array(strtolower($publicFile->getExtension()), ['php','js'], true)
        || str_contains($publicFile->getPathname(), '/public/admin/')) {
        continue;
    }
    $contents=(string)file_get_contents($publicFile->getPathname());
    $check(preg_match('/humain(?:e|es|s|ement)?/iu', $contents) !== 1,
        'Vocabulaire artificiel réintroduit dans '.$publicFile->getPathname());
}
echo "Parcours vente locale, publication contrôlée et relais depuis l’autoédition : OK\n";
