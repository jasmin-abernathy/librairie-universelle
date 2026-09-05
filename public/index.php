<?php

declare(strict_types=1);

[$config, $pdo] = require dirname(__DIR__) . '/src/bootstrap.php';

$query = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$results = $query !== '' ? Search::works($pdo, $query) : [];

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Chercher une œuvre et choisir comment la lire : papier, numérique, bibliothèque ou domaine public.">
    <title><?= e($config['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="/assets/js/app.js" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<header class="site-header">
    <a class="brand" href="/" aria-label="Accueil — Librairie universelle">📚 <span>Librairie universelle</span></a>
    <span class="status-badge">prototype</span>
</header>

<main id="main">
    <section class="hero" aria-labelledby="hero-title">
        <p class="eyebrow">Une recherche. Toutes les façons de lire.</p>
        <h1 id="hero-title">Chercher une œuvre, pas un produit.</h1>
        <p class="lede">Papier, ebook, audio, librairie locale, bibliothèque ou téléchargement légal gratuit lorsque l’œuvre et la version le permettent.</p>

        <form class="search-form" action="/" method="get" role="search">
            <label for="q">Titre, auteur ou autrice</label>
            <div class="search-row">
                <input id="q" name="q" type="search" value="<?= e($query) ?>" placeholder="Ex. Les Misérables, Ursula Le Guin…" autocomplete="off">
                <button type="submit">Rechercher</button>
            </div>
        </form>

        <ul class="principles" aria-label="Principes du moteur">
            <li>Sans IA</li>
            <li>Sans profilage</li>
            <li>Résultats explicables</li>
            <li>Sources identifiées</li>
        </ul>
    </section>

    <?php if ($query !== ''): ?>
        <section class="results" aria-labelledby="results-title">
            <div class="section-heading">
                <h2 id="results-title">Résultats pour « <?= e($query) ?> »</h2>
                <span><?= count($results) ?> résultat<?= count($results) === 1 ? '' : 's' ?></span>
            </div>

            <?php if ($results === []): ?>
                <div class="empty-state">
                    <h3>Aucun résultat dans le prototype.</h3>
                    <p>Le catalogue n’est pas encore importé. La prochaine étape sera d’ajouter des sources bibliographiques vérifiées une par une.</p>
                </div>
            <?php else: ?>
                <div class="result-list">
                    <?php foreach ($results as $result): ?>
                        <article class="work-card">
                            <div>
                                <p class="work-kind">Œuvre</p>
                                <h3><?= e($result['title']) ?></h3>
                                <?php if (!empty($result['contributors'])): ?>
                                    <p><?= e($result['contributors']) ?></p>
                                <?php endif; ?>
                            </div>
                            <dl>
                                <div><dt>Première publication</dt><dd><?= e($result['first_publication_year'] ? (string) $result['first_publication_year'] : '—') ?></dd></div>
                                <div><dt>Domaine public</dt><dd><?= e($result['public_domain_status']) ?></dd></div>
                            </dl>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <section class="modes" aria-labelledby="modes-title">
            <h2 id="modes-title">Ce que cette fiche d’œuvre réunira</h2>
            <div class="mode-grid">
                <article><span aria-hidden="true">🏪</span><h3>Librairies</h3><p>Neuf, occasion, retrait local et disponibilité réelle.</p></article>
                <article><span aria-hidden="true">⚡</span><h3>Numérique</h3><p>EPUB, PDF et audio avec formats et DRM clairement annoncés.</p></article>
                <article><span aria-hidden="true">🏛️</span><h3>Bibliothèques</h3><p>Emprunt lorsque des données ouvertes ou partenaires le permettent.</p></article>
                <article><span aria-hidden="true">🟢</span><h3>Domaine public</h3><p>Lire ou télécharger gratuitement depuis une source légitime et identifiable.</p></article>
            </div>
        </section>
    <?php endif; ?>
</main>

<footer>
    <p>Prototype sans publicité, sans traqueur et sans moteur de recommandation opaque.</p>
</footer>
</body>
</html>
