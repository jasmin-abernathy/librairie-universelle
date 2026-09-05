<?php

declare(strict_types=1);

[$config, $pdo] = require dirname(__DIR__) . '/src/bootstrap.php';

$query = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$results = $query !== '' ? Search::works($pdo, $query) : [];

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function rightsLabel(string $status): string
{
    return match ($status) {
        'yes' => 'Oui — œuvre originale',
        'no' => 'Non',
        'review' => 'À vérifier',
        default => 'Inconnu',
    };
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Chercher une œuvre et choisir comment la lire : papier, numérique, bibliothèque ou domaine public. Prototype sans IA ni profilage.">
    <meta name="theme-color" content="#f7f3ea">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Une recherche. Toutes les façons légales de lire.">
    <meta property="og:description" content="Un MVP centré sur l’œuvre : papier, numérique, domaine public et sources identifiées, sans IA ni recommandation opaque.">
    <title><?= e($config['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="/assets/js/app.js" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<header class="site-header">
    <a class="brand" href="/" aria-label="Accueil — Librairie universelle">📚 <span>Librairie universelle <small>nom de travail</small></span></a>
    <nav class="site-nav" aria-label="Navigation principale">
        <a href="/projet.php">Le projet</a>
        <a href="/sans-ia.php">Sans IA</a>
    </nav>
</header>

<main id="main">
    <section class="hero" aria-labelledby="hero-title">
        <div class="hero-meta"><span class="status-badge">MVP en construction</span><span>Recherche déterministe · sources explicites</span></div>
        <p class="eyebrow">Une recherche. Toutes les façons légales de lire.</p>
        <h1 id="hero-title">Chercher une œuvre, pas un produit.</h1>
        <p class="lede">Papier, ebook, audio, librairie locale, bibliothèque ou téléchargement légal gratuit lorsque l’œuvre et la version le permettent.</p>

        <form class="search-form" action="/" method="get" role="search">
            <label for="q">Titre, auteur ou autrice</label>
            <div class="search-row">
                <input id="q" name="q" type="search" value="<?= e($query) ?>" placeholder="Ex. Les Misérables, George Orwell…" autocomplete="off">
                <button type="submit">Rechercher</button>
            </div>
        </form>

        <div class="try-searches" aria-label="Exemples à essayer">
            <span>Essayer :</span>
            <a href="/?q=Les+Mis%C3%A9rables">Les Misérables</a>
            <a href="/?q=George+Orwell">George Orwell</a>
            <a href="/?q=1984">1984</a>
            <a href="/?q=Animal+Farm">Animal Farm</a>
        </div>

        <ul class="principles" aria-label="Principes du moteur">
            <li>Sans IA</li>
            <li>Sans profilage</li>
            <li>Résultats explicables</li>
            <li>Sources identifiées</li>
        </ul>
        <div class="cta-row hero-links">
            <a class="text-link" href="/projet.php">Comprendre le MVP</a>
            <a class="text-link" href="/sans-ia.php">Pourquoi sans IA ?</a>
        </div>
    </section>

    <?php if ($query !== ''): ?>
        <section class="results" aria-labelledby="results-title">
            <div class="section-heading">
                <h2 id="results-title">Résultats pour « <?= e($query) ?> »</h2>
                <span><?= count($results) ?> résultat<?= count($results) === 1 ? '' : 's' ?></span>
            </div>

            <?php if ($results === []): ?>
                <div class="empty-state">
                    <h3>Pas encore dans le petit corpus du MVP.</h3>
                    <p>Le prototype contient volontairement peu d’œuvres, toutes documentées. On élargira le corpus après validation du modèle et des sources.</p>
                    <a class="text-link" href="/?q=George+Orwell">Essayer avec George Orwell</a>
                </div>
            <?php else: ?>
                <div class="result-list">
                    <?php foreach ($results as $result): ?>
                        <article class="work-card">
                            <div>
                                <p class="work-kind">Œuvre</p>
                                <h3><a href="/work.php?id=<?= (int) $result['id'] ?>"><?= e($result['title']) ?></a></h3>
                                <?php if (!empty($result['original_title']) && $result['original_title'] !== $result['title']): ?>
                                    <p class="original-title">Titre original : <?= e($result['original_title']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($result['contributors'])): ?>
                                    <p><?= e($result['contributors']) ?></p>
                                <?php endif; ?>
                                <a class="text-link" href="/work.php?id=<?= (int) $result['id'] ?>">Voir les façons de lire →</a>
                            </div>
                            <dl>
                                <div><dt>Première publication</dt><dd><?= e($result['first_publication_year'] ? (string) $result['first_publication_year'] : '—') ?></dd></div>
                                <div><dt>Domaine public</dt><dd><?= e(rightsLabel($result['public_domain_status'])) ?></dd></div>
                            </dl>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <section class="mvp-intro" aria-labelledby="mvp-intro-title">
            <div class="section-kicker">Pourquoi ce MVP ?</div>
            <h2 id="mvp-intro-title">Aujourd’hui, une même œuvre est dispersée entre plusieurs mondes.</h2>
            <p class="lede small">Le prototype vérifie qu’on peut réunir ces possibilités sans cacher leur origine et sans pousser automatiquement l’option la plus rentable.</p>
            <div class="proof-grid">
                <article><strong>1 recherche</strong><span>au lieu de repartir de zéro sur chaque catalogue</span></article>
                <article><strong>1 fiche œuvre</strong><span>qui distingue clairement l’œuvre, ses éditions et les offres</span></article>
                <article><strong>0 boîte noire</strong><span>le classement doit rester explicable et contrôlable</span></article>
            </div>
        </section>

        <section class="modes" aria-labelledby="modes-title">
            <h2 id="modes-title">Ce que cette fiche d’œuvre réunira</h2>
            <div class="mode-grid">
                <article><span aria-hidden="true">🏪</span><h3>Librairies</h3><p>Neuf, occasion, retrait local et disponibilité lorsque la donnée est réellement accessible.</p></article>
                <article><span aria-hidden="true">⚡</span><h3>Numérique</h3><p>EPUB, PDF et audio avec formats et DRM clairement annoncés.</p></article>
                <article><span aria-hidden="true">🏛️</span><h3>Bibliothèques</h3><p>Emprunt lorsque des données ouvertes ou partenaires le permettent.</p></article>
                <article><span aria-hidden="true">🟢</span><h3>Domaine public</h3><p>Lire ou télécharger gratuitement depuis une source légitime et identifiable.</p></article>
            </div>
        </section>

        <section class="content-section callout" aria-labelledby="scope-title">
            <div>
                <div class="section-kicker">Déjà testable</div>
                <h2 id="scope-title">Le premier corpus réel est en place.</h2>
                <p>Les Misérables et plusieurs œuvres de George Orwell permettent déjà de tester la recherche, la distinction œuvre/édition et les cas de domaine public. Pour Orwell, le site distingue explicitement le texte original des traductions.</p>
            </div>
            <a class="button-link secondary" href="/?q=George+Orwell">Tester Orwell</a>
        </section>
    <?php endif; ?>
</main>

<footer>
    <div class="footer-links">
        <a href="/projet.php">Le projet</a>
        <a href="/sans-ia.php">Sans IA</a>
    </div>
    <p>Prototype sans publicité, sans traqueur et sans moteur de recommandation opaque.</p>
</footer>
</body>
</html>
