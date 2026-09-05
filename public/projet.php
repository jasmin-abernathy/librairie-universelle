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
    <meta name="description" content="Comprendre le MVP : une recherche centrée sur l’œuvre pour réunir les différentes façons légales de lire.">
    <meta name="theme-color" content="#f7f3ea">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Le projet — <?= e_project($config['name']) ?>">
    <meta property="og:description" content="Une recherche centrée sur l’œuvre, des sources identifiées et aucun moteur de recommandation par IA.">
    <title>Le projet — <?= e_project($config['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<header class="site-header">
    <a class="brand" href="/" aria-label="Accueil — Librairie universelle">📚 <span>Librairie universelle <small>nom de travail</small></span></a>
    <nav class="site-nav" aria-label="Navigation principale">
        <a aria-current="page" href="/projet.php">Le projet</a>
        <a href="/sans-ia.php">Sans IA</a>
    </nav>
</header>

<main id="main">
    <section class="page-hero" aria-labelledby="project-title">
        <p class="eyebrow">MVP — prototype en construction</p>
        <h1 id="project-title">Une recherche. Toutes les façons légales de lire.</h1>
        <p class="lede">Le projet part de l’œuvre plutôt que d’une boutique ou d’un ISBN. L’objectif du MVP est simple : vérifier qu’une même fiche peut rendre les choix de lecture plus clairs, sans enfermer la personne dans une plateforme.</p>
        <div class="cta-row">
            <a class="button-link" href="/">Essayer la recherche</a>
            <a class="text-link" href="#mvp">Voir ce que teste le MVP</a>
        </div>
    </section>

    <section class="content-section" aria-labelledby="problem-title">
        <div class="section-kicker">Le problème</div>
        <h2 id="problem-title">Aujourd’hui, une même œuvre est éclatée entre plusieurs catalogues.</h2>
        <div class="prose-grid">
            <p>Une édition papier peut être chez un libraire, l’ebook ailleurs, l’occasion sur une autre plateforme, tandis qu’une version du domaine public existe peut-être gratuitement sur une source patrimoniale.</p>
            <p>Le MVP teste une autre logique : commencer par « je veux lire ce livre », puis afficher les différentes voies d’accès de manière compréhensible et sourcée.</p>
        </div>
    </section>

    <section id="mvp" class="content-section" aria-labelledby="mvp-title">
        <div class="section-kicker">Ce que l’on teste</div>
        <h2 id="mvp-title">Un petit périmètre, mais une promesse complète.</h2>
        <div class="feature-grid">
            <article><span aria-hidden="true">1</span><h3>Chercher</h3><p>Une recherche simple par titre, auteur ou autrice, sans personnalisation cachée.</p></article>
            <article><span aria-hidden="true">2</span><h3>Regrouper</h3><p>Une fiche par œuvre qui peut réunir plusieurs éditions et plusieurs modes d’accès.</p></article>
            <article><span aria-hidden="true">3</span><h3>Comprendre</h3><p>Formats, provenance, disponibilité et statut du domaine public doivent être lisibles.</p></article>
            <article><span aria-hidden="true">4</span><h3>Choisir</h3><p>Le site ne doit pas pousser automatiquement l’option qui rapporte le plus.</p></article>
        </div>
    </section>

    <section class="content-section" aria-labelledby="not-yet-title">
        <div class="section-kicker">Pas encore</div>
        <h2 id="not-yet-title">Le MVP n’essaie pas de tout construire d’un coup.</h2>
        <ul class="check-list muted-list">
            <li>Pas encore de panier multi-libraires.</li>
            <li>Pas encore de paiement intégré.</li>
            <li>Pas encore de compte lecteur.</li>
            <li>Pas de promesse de catalogue exhaustif.</li>
            <li>Pas de faux stock « temps réel » si la source ne le permet pas.</li>
        </ul>
        <p class="note">Ces fonctions ne sont pas nécessaires pour valider le cœur du produit : la recherche et la fiche œuvre.</p>
    </section>

    <section class="content-section callout" aria-labelledby="trust-title">
        <div>
            <div class="section-kicker">Confiance</div>
            <h2 id="trust-title">Le moteur doit pouvoir expliquer ce qu’il montre.</h2>
            <p>Les sources doivent être identifiées. Les cas juridiques incertains doivent être signalés. Une future recommandation devra être humaine ou reposer sur une règle visible.</p>
        </div>
        <a class="button-link secondary" href="/sans-ia.php">Pourquoi le projet est sans IA</a>
    </section>
</main>

<footer>
    <p>Prototype en construction — le nom public définitif reste à choisir.</p>
</footer>
</body>
</html>
