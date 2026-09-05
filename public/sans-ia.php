<?php

declare(strict_types=1);

[$config] = require dirname(__DIR__) . '/src/bootstrap.php';

function e_no_ai(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Pourquoi le projet n’utilise ni IA de recommandation, ni profilage comportemental, ni classement opaque.">
    <meta name="theme-color" content="#f7f3ea">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Sans IA — <?= e_no_ai($config['name']) ?>">
    <meta property="og:description" content="Recherche déterministe, sources identifiées, règles visibles et aucune recommandation opaque.">
    <title>Sans IA — <?= e_no_ai($config['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<header class="site-header">
    <a class="brand" href="/" aria-label="Accueil — Librairie universelle">📚 <span>Librairie universelle <small>nom de travail</small></span></a>
    <nav class="site-nav" aria-label="Navigation principale">
        <a href="/projet.php">Le projet</a>
        <a aria-current="page" href="/sans-ia.php">Sans IA</a>
    </nav>
</header>

<main id="main">
    <section class="page-hero compact" aria-labelledby="no-ai-title">
        <p class="eyebrow">Principe de conception</p>
        <h1 id="no-ai-title">Pas d’IA pour décider ce que vous devriez lire.</h1>
        <p class="lede">Le projet choisit volontairement une recherche et des classements déterministes, compréhensibles et auditables.</p>
    </section>

    <section class="content-section" aria-labelledby="means-title">
        <div class="section-kicker">Concrètement</div>
        <h2 id="means-title">« Sans IA » ne veut pas seulement dire « pas de chatbot ».</h2>
        <div class="feature-grid">
            <article><h3>Pas de profil lecteur caché</h3><p>Le comportement de navigation n’a pas vocation à produire un profil destiné à influencer les résultats.</p></article>
            <article><h3>Pas de recommandation opaque</h3><p>Un livre ne doit pas être poussé parce qu’un modèle a calculé qu’il maximiserait un clic ou une vente.</p></article>
            <article><h3>Des règles visibles</h3><p>Un classement peut utiliser des critères explicites : format choisi, prix, disponibilité, distance ou ordre alphabétique.</p></article>
            <article><h3>Des choix humains</h3><p>Les sélections éditoriales futures pourront venir de libraires, bibliothécaires ou lecteurs identifiés comme tels.</p></article>
        </div>
    </section>

    <section class="content-section" aria-labelledby="search-title">
        <div class="section-kicker">Recherche</div>
        <h2 id="search-title">Le moteur doit pouvoir répondre à « pourquoi ce résultat ? »</h2>
        <p class="lede small">Le MVP commence par des correspondances simples sur les métadonnées bibliographiques. Si le classement devient plus riche, chaque critère devra rester documenté et testable.</p>
        <div class="example-box">
            <p class="example-label">Exemple acceptable</p>
            <p>« Vous avez choisi <strong>ebook</strong>, puis <strong>prix croissant</strong>. Cette offre apparaît avant l’autre parce qu’elle correspond à ces deux filtres. »</p>
        </div>
    </section>

    <section class="content-section" aria-labelledby="human-title">
        <div class="section-kicker">Découverte</div>
        <h2 id="human-title">Découvrir des livres peut rester profondément humain.</h2>
        <ul class="check-list">
            <li>Sélections d’un libraire ou d’une librairie.</li>
            <li>Listes thématiques éditoriales avec auteur clairement identifié.</li>
            <li>Liens déterministes : même autrice, même collection, même thème documenté.</li>
            <li>Listes publiques créées volontairement par des lecteurs si cette fonction est ajoutée.</li>
        </ul>
    </section>

    <section class="content-section callout" aria-labelledby="privacy-title">
        <div>
            <div class="section-kicker">Vie privée</div>
            <h2 id="privacy-title">Pas besoin d’espionner les lecteurs pour améliorer le produit.</h2>
            <p>Si des statistiques sont ajoutées au MVP, elles devront être minimales, agrégées et documentées : par exemple le nombre de recherches ou la part de recherches sans résultat. Pas de fingerprinting ni de profil individuel de lecture.</p>
        </div>
        <a class="button-link secondary" href="/projet.php">Voir le périmètre du MVP</a>
    </section>
</main>

<footer>
    <p>Recherche déterministe · sources identifiées · aucune recommandation opaque.</p>
</footer>
</body>
</html>
