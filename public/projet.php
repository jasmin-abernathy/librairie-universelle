<?php

declare(strict_types=1);

[$config] = require dirname(__DIR__) . '/src/bootstrap.php';

function e_project(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Comprendre le MVP : une recherche centrée sur l’œuvre, un storefront ebook gratuit et payant et une autoédition validée humainement.">
    <meta name="theme-color" content="#f7f3ea">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Le projet — <?= e_project($config['name']) ?>">
    <meta property="og:description" content="Une recherche centrée sur l’œuvre, des sources identifiées, des ebooks gratuits et payants et aucun moteur de recommandation par IA.">
    <title>Le projet — <?= e_project($config['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<header class="site-header">
    <a class="brand" href="/" aria-label="Accueil — Librairie universelle">📚 <span>Librairie universelle <small>nom de travail</small></span></a>
    <nav class="site-nav" aria-label="Navigation principale">
        <a href="/ebooks.php">Ebooks</a>
        <a href="/autoedition.php">Autoédition</a>
        <a aria-current="page" href="/projet.php">Le projet</a>
        <a href="/sans-ia.php">Sans IA</a>
    </nav>
</header>

<main id="main">
    <section class="page-hero" aria-labelledby="project-title">
        <p class="eyebrow">MVP — prototype en construction</p>
        <h1 id="project-title">Une recherche. Toutes les façons légales de lire.</h1>
        <p class="lede">Le projet part de l’œuvre plutôt que d’une boutique ou d’un ISBN. Le MVP réunit déjà des ebooks gratuits et payants, plusieurs éditions réelles et un parcours d’autoédition avec validation humaine.</p>
        <div class="cta-row">
            <a class="button-link" href="/ebooks.php">Voir le storefront ebook</a>
            <a class="text-link" href="/">Essayer la recherche</a>
            <a class="text-link" href="#mvp">Voir le périmètre actuel</a>
        </div>
    </section>

    <section class="content-section" aria-labelledby="problem-title">
        <div class="section-kicker">Le problème</div>
        <h2 id="problem-title">Aujourd’hui, une même œuvre est éclatée entre plusieurs catalogues.</h2>
        <div class="prose-grid">
            <p>Une édition papier peut être chez un libraire, l’ebook ailleurs, l’occasion sur une autre plateforme, tandis qu’une version du domaine public existe peut-être gratuitement sur une source patrimoniale.</p>
            <p>Le MVP teste une autre logique : commencer par « je veux lire ce livre », puis afficher les différentes voies d’accès de manière compréhensible, datée et sourcée.</p>
        </div>
    </section>

    <section id="mvp" class="content-section" aria-labelledby="mvp-title">
        <div class="section-kicker">Déjà codé</div>
        <h2 id="mvp-title">Un petit catalogue, mais les briques essentielles sont réelles.</h2>
        <div class="feature-grid">
            <article><span aria-hidden="true">1</span><h3>Chercher</h3><p>Recherche simple par titre ou auteur, sans personnalisation comportementale.</p></article>
            <article><span aria-hidden="true">2</span><h3>Comparer</h3><p>Œuvre, éditions, vendeurs, prix, formats et DRM restent séparés et compréhensibles.</p></article>
            <article><span aria-hidden="true">3</span><h3>Lire gratuitement</h3><p>Les accès gratuits vérifiés apparaissent dans le même storefront que les offres payantes.</p></article>
            <article><span aria-hidden="true">4</span><h3>Publier en indépendant</h3><p>EPUB, PDF imprimeur et couverture peuvent être soumis, puis examinés humainement avant publication.</p></article>
        </div>
    </section>

    <section class="content-section" aria-labelledby="quality-title">
        <div class="section-kicker">Autoédition</div>
        <h2 id="quality-title">Ouvert aux indépendants ne veut pas dire publication automatique.</h2>
        <div class="prose-grid">
            <p>Le projet veut donner une vraie place aux auteurs autoédités, pas reproduire les catalogues saturés de contenus industriels et de variantes sans valeur.</p>
            <p>Le parcours contrôle les fichiers, les ISBN et les déclarations de droits. Une personne choisit ensuite entre validation, demande de corrections et refus. Aucun modèle d’IA ne prend cette décision.</p>
        </div>
        <a class="text-link" href="/autoedition.php">Voir le formulaire et les ressources ISBN / impression →</a>
    </section>

    <section class="content-section" aria-labelledby="not-yet-title">
        <div class="section-kicker">Pas encore</div>
        <h2 id="not-yet-title">Les dépendances externes restent volontairement hors du faux-semblant.</h2>
        <ul class="check-list muted-list">
            <li>Pas encore de paiement intégré pour nos propres ebooks payants autoédités.</li>
            <li>Pas encore de panier multi-libraires.</li>
            <li>Pas encore de flux partenaire ePagine / réseau de libraires indépendants contractualisé.</li>
            <li>Pas encore de compte lecteur.</li>
            <li>Pas de promesse de catalogue exhaustif ni de stock temps réel inventé.</li>
        </ul>
        <p class="note">Une fonction externe n’est annoncée comme disponible qu’une fois la source, ses droits de réutilisation et son comportement réel vérifiés.</p>
    </section>

    <section class="content-section callout" aria-labelledby="trust-title">
        <div>
            <div class="section-kicker">Confiance</div>
            <h2 id="trust-title">Le moteur doit pouvoir expliquer ce qu’il montre.</h2>
            <p>Les sources sont identifiées, les prix sont datés, les cas juridiques incertains restent signalés et la publication indépendante est validée humainement.</p>
        </div>
        <a class="button-link secondary" href="/sans-ia.php">Pourquoi le projet est sans IA</a>
    </section>
</main>

<footer>
    <div class="footer-links"><a href="/ebooks.php">Ebooks</a><a href="/autoedition.php">Autoédition</a><a href="/feedback.php">Donner un retour</a></div>
    <p>Prototype en construction — le nom public définitif reste à choisir.</p>
</footer>
</body>
</html>
