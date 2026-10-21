<?php
declare(strict_types=1);
$config = require dirname(__DIR__) . '/src/discovery-bootstrap.php';
header('Referrer-Policy: no-referrer');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <meta name="description" content="Accompagnement facultatif des auteurs indépendants : vérification des fichiers pour l'impression et correction assistée avec contrôle humain.">
    <title>Prestations pour auteurs — Librairie universelle</title>
    <link rel="stylesheet" href="/assets/css/app.css?v=20261009-2">
    <link rel="stylesheet" href="/assets/css/prestations-auteurs.css?v=20261021-1">
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<?php $currentNav = 'autoedition'; require dirname(__DIR__) . '/src/views/site-header.php'; ?>
<main id="main" class="author-services">
    <section class="page-hero compact">
        <p class="eyebrow">Un coup de main humain, sans obligation</p>
        <h1>Votre livre mérite un dernier regard.</h1>
        <p class="lede">Nos outils de mise en page, de couverture et de préparation à l'impression restent gratuits. Si vous le souhaitez, Le Potager du Web peut aussi vérifier vos fichiers ou vous aider à corriger votre texte.</p>
        <p><a class="text-link" href="/autoedition.php">← Revenir au générateur gratuit</a></p>
    </section>
    <section class="author-services-grid" aria-label="Prestations facultatives">
        <article class="author-service-card" id="verification">
            <p class="eyebrow">Avant l'impression</p>
            <h2>Vérification humaine des fichiers</h2>
            <p class="author-service-price">79 € <small>HT · tarif indicatif</small></p>
            <p>Vous avez préparé vos PDF ? Une personne les examine pour repérer les problèmes les plus visibles avant de demander l'impression.</p>
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
            <h2>Correction accompagnée</h2>
            <p class="author-service-price">Dès 179 € <small>HT · sur devis</small></p>
            <p>Une première passe d'orthographe, de grammaire et de typographie assistée par logiciel, avec contrôle humain des propositions, puis vérification technique des fichiers.</p>
            <ul>
                <li>Repérage des fautes et incohérences typographiques</li>
                <li>Validation humaine des corrections dans le périmètre convenu</li>
                <li>Contrôle du dossier avant impression</li>
            </ul>
            <p class="author-service-limit">Ce prix ne garantit pas la correction intégrale d'un roman ni une relecture éditoriale exhaustive. Le devis dépend de la longueur, de l'état du texte et du temps nécessaire.</p>
            <a class="button-link" href="mailto:contact@lepotager.org?subject=Librairie%20universelle%20-%20devis%20correction%20accompagnee&amp;body=Bonjour%2C%0A%0AJe%20souhaite%20un%20devis%20pour%20une%20correction%20accompagnee.%0A%0ATitre%20du%20livre%20%3A%20%0ALongueur%20approximative%20%3A%20%0AEtat%20du%20manuscrit%20%3A%20%0ADate%20souhaitee%20%3A%20%0A%0AMerci.">Demander un devis</a>
        </article>
    </section>
    <p class="author-services-note">Montants indicatifs hors taxes : le devis précisera le total à payer, TVA comprise si applicable. Aucun paiement ni travail facturé avant votre accord. L'impression est facturée séparément par l'imprimeur choisi.</p>
    <section class="content-section" aria-labelledby="author-services-process">
        <h2 id="author-services-process">Comment ça fonctionne ?</h2>
        <ol class="check-list">
            <li>Vous décrivez votre livre, sa longueur et vos besoins. Pas besoin de transmettre votre manuscrit complet dans le premier message.</li>
            <li>Nous précisons ce qui sera vérifié ou corrigé et vous transmettons un devis avec un prix et un délai.</li>
            <li>Vous restez libre d'accepter ou de refuser. Une fois le devis accepté, vous pourrez régler votre facture via <a href="https://www.lepotager.org/paiement/" rel="noopener noreferrer">le paiement sécurisé du Potager</a>.</li>
        </ol>
        <p>Les boutons ouvrent votre application de messagerie. Si elle n'est pas configurée, utilisez le <a href="https://www.lepotager.org/contact/" rel="noopener noreferrer">formulaire de contact du Potager</a> en précisant « Librairie universelle » et l'offre souhaitée.</p>
        <p><strong>Important :</strong> aucune de ces prestations n'est obligatoire pour utiliser le générateur gratuit ou déposer un livre pour validation au catalogue.</p>
        <p><a class="text-link" href="/diffuser-mon-livre.php">Et après ? Apprendre à proposer mon livre aux librairies →</a></p>
    </section>
</main>
<footer><div class="footer-links"><a href="/autoedition.php">Autoédition gratuite</a><a href="/diffuser-mon-livre.php">Vendre mon livre</a><a href="/projet.php">Le projet</a></div><p>Prestations facultatives proposées par Le Potager du Web.</p></footer>
</body></html>
