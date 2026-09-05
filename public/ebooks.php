<?php

declare(strict_types=1);

[$config, $pdo] = require dirname(__DIR__) . '/src/bootstrap.php';
$mode = isset($_GET['mode']) ? (string) $_GET['mode'] : 'all';
$query = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$offers = EbookStorefront::browse($pdo, $config, $mode, $query);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function priceLabel(?int $cents, ?string $currency, bool $isFree): string
{
    if ($isFree) {
        return 'Gratuit';
    }
    if ($cents === null) {
        return 'Prix à vérifier';
    }

    return number_format($cents / 100, 2, ',', ' ') . ' ' . ($currency === 'EUR' ? '€' : e($currency));
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Storefront ebook réunissant livres payants et textes gratuits de sources vérifiées, sans recommandation par IA.">
    <title>Ebooks — <?= e($config['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<header class="site-header">
    <a class="brand" href="/">📚 <span>Librairie universelle <small>nom de travail</small></span></a>
    <nav class="site-nav" aria-label="Navigation principale">
        <a href="/ebooks.php" aria-current="page">Ebooks</a>
        <a href="/autoedition.php">Autoédition</a>
        <a href="/projet.php">Le projet</a>
        <a href="/sans-ia.php">Sans IA</a>
    </nav>
</header>

<main id="main">
    <section class="page-hero compact">
        <p class="eyebrow">Storefront numérique</p>
        <h1>Payant ou gratuit : le même rayon, avec la même exigence.</h1>
        <p class="lede">Les textes gratuits ne sont pas relégués parce qu’ils rapportent moins. Les offres payantes affichent leur vendeur, leur format, leur DRM et la date de vérification lorsqu’elle est connue.</p>
        <ul class="principles" aria-label="Règles du storefront">
            <li>Sources identifiées</li>
            <li>Domaine public vérifié</li>
            <li>Autoédition validée humainement</li>
            <li>Pas de recommandation par IA</li>
        </ul>
    </section>

    <section class="content-section" aria-labelledby="browse-title">
        <div class="section-heading">
            <h2 id="browse-title">Parcourir les ebooks</h2>
            <span><?= count($offers) ?> offre<?= count($offers) === 1 ? '' : 's' ?></span>
        </div>

        <form class="storefront-controls" method="get" action="/ebooks.php">
            <label for="ebook-search">Titre ou auteur</label>
            <div class="search-row">
                <input id="ebook-search" type="search" name="q" value="<?= e($query) ?>" placeholder="Ex. 1984, Victor Hugo…">
                <button type="submit">Filtrer</button>
            </div>
            <div class="filter-pills" aria-label="Type d’offre">
                <a href="/ebooks.php<?= $query !== '' ? '?q=' . urlencode($query) : '' ?>"<?= $mode === 'all' ? ' aria-current="page"' : '' ?>>Tout</a>
                <a href="/ebooks.php?mode=free<?= $query !== '' ? '&amp;q=' . urlencode($query) : '' ?>"<?= $mode === 'free' ? ' aria-current="page"' : '' ?>>Gratuit</a>
                <a href="/ebooks.php?mode=paid<?= $query !== '' ? '&amp;q=' . urlencode($query) : '' ?>"<?= $mode === 'paid' ? ' aria-current="page"' : '' ?>>Payant</a>
            </div>
        </form>

        <?php if ($offers === []): ?>
            <div class="empty-state">
                <h3>Aucune offre dans ce filtre pour le moment.</h3>
                <p>Le storefront n’invente pas de disponibilité : il n’affiche que les sources déjà reliées et documentées.</p>
            </div>
        <?php else: ?>
            <div class="ebook-grid">
                <?php foreach ($offers as $offer): ?>
                    <article class="ebook-card">
                        <div class="ebook-card-topline">
                            <span class="status-badge"><?= $offer['is_free'] ? 'Gratuit' : 'Payant' ?></span>
                            <?php if ($offer['is_stale']): ?><span class="stale-badge">À revérifier</span><?php endif; ?>
                        </div>
                        <p class="work-kind"><?= e($offer['source_name']) ?></p>
                        <h3><a href="/work.php?id=<?= (int) $offer['work_id'] ?>"><?= e($offer['work_title']) ?></a></h3>
                        <?php if (!empty($offer['contributors'])): ?><p class="ebook-author"><?= e($offer['contributors']) ?></p><?php endif; ?>
                        <?php if (!empty($offer['edition_title'])): ?><p class="ebook-edition"><?= e($offer['edition_title']) ?></p><?php endif; ?>
                        <dl class="ebook-facts">
                            <div><dt>Prix</dt><dd><?= e(priceLabel($offer['price_cents'] !== null ? (int) $offer['price_cents'] : null, $offer['currency'], (bool) $offer['is_free'])) ?></dd></div>
                            <div><dt>Format</dt><dd><?= e($offer['offer_format'] ?: $offer['edition_format'] ?: 'Non précisé') ?></dd></div>
                            <div><dt>DRM</dt><dd><?= e($offer['offer_drm'] ?: 'Non précisé') ?></dd></div>
                            <?php if (!empty($offer['isbn13'])): ?><div><dt>ISBN</dt><dd><code><?= e($offer['isbn13']) ?></code></dd></div><?php endif; ?>
                        </dl>
                        <?php if (!empty($offer['checked_at'])): ?>
                            <p class="freshness-note">Donnée vérifiée : <?= e(substr((string) $offer['checked_at'], 0, 10)) ?><?= $offer['is_stale'] ? ' — revérification recommandée' : '' ?>.</p>
                        <?php endif; ?>
                        <a class="button-link" href="<?= e($offer['url']) ?>" rel="noopener noreferrer">Voir cette offre ↗</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="content-section callout">
        <div>
            <div class="section-kicker">Auteurs indépendants</div>
            <h2>Le storefront accueillera aussi l’autoédition — mais après validation humaine.</h2>
            <p>Un livre indépendant peut être excellent. Ce qu’on refuse, c’est la publication automatique de masse et les catalogues remplis de variantes sans valeur.</p>
        </div>
        <a class="button-link secondary" href="/autoedition.php">Voir le parcours auteur</a>
    </section>
</main>

<footer>
    <div class="footer-links"><a href="/autoedition.php">Autoédition</a><a href="/feedback.php?kind=catalog">Signaler un problème de catalogue</a><a href="/projet.php">Le projet</a></div>
    <p>Storefront sobre : gratuit et payant réunis, sans profilage comportemental.</p>
</footer>
</body>
</html>
