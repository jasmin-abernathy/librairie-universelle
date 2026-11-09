<?php
declare(strict_types=1);
$config = require dirname(__DIR__) . '/src/discovery-bootstrap.php';
header('Referrer-Policy: no-referrer');
$correctionMailSubject = 'Librairie universelle - demande de relecture et correction';
$correctionMailBody = implode("\n", [
    'Bonjour,',
    '',
    'Je souhaite une relecture et correction de mon manuscrit.',
    '',
    'Titre du livre : ',
    'Nombre de pages A4 : ',
    'Nombre de mots : ',
    'Format du fichier source : DOCX / ODT / EPUB',
    'Date souhaitée : ',
    '',
    'PIÈCE JOINTE À AJOUTER AVANT ENVOI : mon fichier original (.docx, .odt ou .epub), et non un PDF mis en page.',
    '',
    'Merci.',
]);
$correctionMailHref = 'mailto:contact@lepotager.org?subject=' . rawurlencode($correctionMailSubject)
    . '&body=' . rawurlencode($correctionMailBody);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <meta name="description" content="Accompagnement facultatif des auteurs indépendants : contrôle des fichiers d'impression et relecture personnelle de manuscrits, avec un dernier contrôle logiciel.">
    <title>Prestations pour auteurs — Librairie universelle</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=20261104-nav2">
    <link rel="stylesheet" href="/assets/css/prestations-auteurs.css?v=20261021-1">
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<?php $currentNav = 'autoedition'; require dirname(__DIR__) . '/src/views/site-header.php'; ?>
<main id="main" class="author-services">
    <section class="page-hero compact">
        <p class="eyebrow">Un accompagnement à la carte</p>
        <h1>Votre livre mérite un dernier regard.</h1>
        <p class="lede">Nos outils de mise en page, de couverture et de préparation à l'impression restent gratuits. Si vous le souhaitez, Le Potager du Web peut aussi vérifier vos fichiers ou vous aider à corriger votre texte.</p>
        <p><a class="text-link" href="/autoedition.php">← Revenir au générateur gratuit</a></p>
    </section>
    <section class="author-services-grid" aria-label="Prestations facultatives">
        <article class="author-service-card" id="verification">
            <p class="eyebrow">Avant l'impression</p>
            <h2>Vérification des PDF avant impression</h2>
            <p class="author-service-price">79 € <small>HT · tarif indicatif</small></p>
            <p>Vous avez préparé vos PDF ? Nous les examinons pour repérer les problèmes les plus visibles avant de demander l'impression.</p>
            <ul>
                <li>Pagination, marges et pages blanches</li>
                <li>Format, lisibilité, couverture et informations sur le livre</li>
                <li>Compte rendu des points à corriger</li>
            </ul>
            <p class="author-service-limit">La correction du manuscrit, les retouches importantes, la certification PDF/X et la validation du bon à tirer par l'imprimeur ne sont pas comprises.</p>
            <a class="button-link" href="mailto:contact@lepotager.org?subject=Librairie%20universelle%20-%20devis%20verification%20des%20fichiers&amp;body=Bonjour%2C%0A%0AJe%20souhaite%20faire%20verifier%20mes%20fichiers%20avant%20impression.%0A%0ATitre%20du%20livre%20%3A%20%0ANombre%20de%20pages%20%3A%20%0AFormat%20%3A%20%0AFichiers%20disponibles%20%3A%20%0A%0AMerci.">Demander cette vérification</a>
        </article>
        <article class="author-service-card" id="correction">
            <p class="eyebrow">Texte et impression</p>
            <h2>Relecture et correction</h2>
            <p class="author-service-price">179 € <small>HT · jusqu’à 30 pages A4</small></p>
            <p><strong>Je relis et corrige personnellement votre texte</strong> : orthographe, grammaire, ponctuation et typographie. Une fois ce travail terminé, j’utilise un correcteur logiciel pour une dernière vérification, afin de repérer les éventuelles coquilles qui auraient échappé à ma lecture. Le logiciel ne remplace pas mon travail.</p>
            <p><strong>Ce forfait couvre jusqu’à 30 pages A4 de texte courant, dans la limite de 9 000 mots.</strong> La première limite atteinte s’applique.</p>
            <p><strong>Fichier à envoyer pour la relecture : le manuscrit original en DOCX, ODT ou EPUB sans DRM.</strong> Joignez-le à votre e-mail de demande : je travaille sur le texte source, <strong>pas sur le PDF déjà mis en page pour l’impression</strong>. Le fichier n’est pas transmis automatiquement par le générateur de la Librairie.</p>
            <ul>
                <li>Relecture attentive et corrections effectuées personnellement</li>
                <li>Dernier contrôle logiciel pour repérer les oublis éventuels</li>
                <li>Contrôle final du document corrigé ; le dossier PDF d'impression peut être vérifié séparément si vous le souhaitez</li>
            </ul>
            <p class="author-service-limit">Au-delà de 30 pages A4 ou de 9 000 mots, le tarif est défini par devis avant toute intervention. Une réécriture importante, une préparation éditoriale approfondie ou la correction complète d’un roman ne sont pas incluses dans ce forfait.</p>
            <a class="button-link" href="<?= htmlspecialchars($correctionMailHref, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Envoyer mon manuscrit pour un devis</a>
            <p class="field-help">Le bouton prépare l’e-mail, mais n’ajoute pas de pièce jointe : sélectionnez votre DOCX, ODT ou EPUB original dans votre messagerie avant d’envoyer.</p>
        </article>
    </section>
    <p class="author-services-note">Montants indicatifs hors taxes : le devis précisera le total à payer, TVA comprise si applicable. Aucun paiement ni travail facturé avant votre accord. L'impression est facturée séparément par l'imprimeur choisi.</p>
    <section class="content-section" aria-labelledby="author-services-process">
        <h2 id="author-services-process">Comment ça fonctionne ?</h2>
        <ol class="check-list">
            <li><strong>Pour la relecture à 179 € HT :</strong> joignez à votre e-mail le DOCX, ODT ou EPUB original. Pour la vérification avant impression à 79 € HT, les fichiers à examiner sont les PDF prêts pour l’imprimeur. Aucun fichier n’est envoyé automatiquement depuis cette page.</li>
            <li>Nous précisons ce qui sera vérifié ou corrigé et vous transmettons un devis avec un prix et un délai.</li>
            <li>Vous restez libre d'accepter ou de refuser. Une fois le devis accepté, vous pourrez régler votre facture via <a href="https://www.lepotager.org/paiement/" target="_blank" rel="noopener noreferrer">le paiement sécurisé du Potager</a>.</li>
        </ol>
        <p>Les boutons ouvrent votre application de messagerie. Si elle n'est pas configurée, utilisez le <a href="https://www.lepotager.org/contact/" target="_blank" rel="noopener noreferrer">formulaire de contact du Potager</a> en précisant « Librairie universelle » et l'offre souhaitée.</p>
        <p><strong>Important :</strong> aucune de ces prestations n'est obligatoire pour utiliser le générateur gratuit ou déposer un livre pour validation au catalogue.</p>
        <p><a class="text-link" href="/diffuser-mon-livre.php">Et après ? Apprendre à proposer mon livre aux librairies →</a></p>
    </section>
</main>
<footer><div class="footer-links"><a href="/autoedition.php">Autoédition gratuite</a><a href="/diffuser-mon-livre.php">Vendre mon livre</a><a href="/projet.php">Le projet</a></div><p>Prestations facultatives proposées par Le Potager du Web.</p></footer>
</body></html>
