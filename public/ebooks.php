<?php

declare(strict_types=1);

[$config, $pdo] = require dirname(__DIR__) . '/src/bootstrap.php';

$mode = isset($_GET['mode']) ? (string) $_GET['mode'] : 'all';
$mode = in_array($mode, ['all', 'free', 'paid'], true) ? $mode : 'all';
$query = isset($_GET['q']) ? trim((string) $_GET['q']) : '';

$requestedLanguages = isset($_GET['lang']) && is_array($_GET['lang']) ? $_GET['lang'] : ['fr', 'en'];
$languages = array_values(array_intersect(
    ['fr', 'en'],
    array_unique(array_map(
        static fn (mixed $language): string => mb_strtolower(trim((string) $language), 'UTF-8'),
        $requestedLanguages
    ))
));
if ($languages === []) {
    $languages = ['fr', 'en'];
}

$offers = $query !== '' ? EbookStorefront::browse($pdo, $config, $mode, $query) : [];
$independentEbooks = (new AuthorBookListings($pdo))->visibleEbooks($query, $mode, $query === '' ? 8 : 48);
$allEbookSources = !empty($config['federated_search_enabled'])
    ? array_filter(
        DiscoveryRegistry::definitions(),
        static fn (array $definition, string $key): bool => DiscoveryRegistry::supportsEbookSurface($key),
        ARRAY_FILTER_USE_BOTH
    )
    : [];

$discoveryDefinitions = $query !== '' && $mode !== 'paid'
    ? array_filter(
        $allEbookSources,
        static fn (array $definition, string $key): bool =>
            array_intersect($definition['languages'] ?? [], $languages) !== []
            && DiscoveryRegistry::supportsMode($key, $mode),
        ARRAY_FILTER_USE_BOTH
    )
    : [];

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

function modeLabel(string $mode): string
{
    return match ($mode) {
        'free' => 'Gratuit / libre',
        'paid' => 'Payant',
        default => 'Tout',
    };
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <meta name="description" content="Découvrez des EPUB indépendants proposés directement par leurs auteurs et des livres numériques libres dans des catalogues identifiés.">
    <meta name="theme-color" content="#f7f3ea">
    <title>Ebooks — <?= e($config['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css?v=20261104-nav2">
    <link rel="stylesheet" href="/assets/css/discovery.css?v=20261009-3">
    <link rel="stylesheet" href="/assets/css/ebooks.css?v=20261108-1">
    <script src="/assets/js/discovery.js?v=20261009-4" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<?php $currentNav = 'ebooks'; require dirname(__DIR__) . '/src/views/site-header.php'; ?>

<main id="main" class="ebooks-page">
    <section class="ebook-hero" aria-labelledby="ebook-title">
        <div>
            <p class="eyebrow">Bibliothèque d’ebooks</p>
            <h1 id="ebook-title">Un titre, plusieurs façons de le lire.</h1>
            <p class="lede">Découvrez des EPUB d’auteurs indépendants et recherchez des livres numériques libres. Pas de liens commerciaux vers les grandes plateformes de vente.</p>
        </div>
        <div class="ebook-hero-facts" aria-label="Périmètre de la recherche">
            <div><strong><?= count($allEbookSources) ?></strong><span>catalogues externes dédiés au numérique</span></div>
            <div><strong>FR + EN</strong><span>langues filtrables</span></div>
            <div><strong>0 profilage</strong><span>pas de recommandation opaque</span></div>
        </div>
    </section>

    <section class="ebook-search-section" aria-labelledby="ebook-search-title">
        <div class="ebook-search-copy">
            <p class="section-kicker">Recherche</p>
            <h2 id="ebook-search-title">Quel livre cherchez-vous ?</h2>
            <p>« Tout » réunit les EPUB autoédités et les catalogues de lecture libre. « Payant » affiche les livres numériques proposés par leurs auteurs, sans redirection vers une grande enseigne.</p>
        </div>

        <form class="ebook-search-form" method="get" action="/ebooks.php#ebook-results" role="search">
            <label class="ebook-query-label" for="ebook-search">Titre, auteur, autrice ou ISBN</label>
            <div class="ebook-search-row">
                <input id="ebook-search" type="search" name="q" value="<?= e($query) ?>" placeholder="Ex. Germinal, George Orwell, 978…" autocomplete="off">
                <button type="submit">Rechercher</button>
            </div>

            <div class="ebook-filter-grid">
                <fieldset class="ebook-filter-group language-filter">
                    <legend>Langue</legend>
                    <label><input type="checkbox" name="lang[]" value="fr" <?= in_array('fr', $languages, true) ? 'checked' : '' ?>> Français</label>
                    <label><input type="checkbox" name="lang[]" value="en" <?= in_array('en', $languages, true) ? 'checked' : '' ?>> Anglais</label>
                </fieldset>

                <fieldset class="ebook-filter-group mode-filter">
                    <legend>Type d’accès</legend>
                    <?php foreach (['all' => 'Tout', 'free' => 'Gratuit / libre', 'paid' => 'Payant'] as $value => $label): ?>
                        <label>
                            <input type="radio" name="mode" value="<?= e($value) ?>" <?= $mode === $value ? 'checked' : '' ?>>
                            <span><?= e($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
            </div>
        </form>
    </section>


    <section class="ebook-indie-section" id="epub-independants" aria-labelledby="epub-independants-title">
      <div class="ebook-block-heading">
        <div>
          <p class="section-kicker">Vente directe</p>
          <h2 id="epub-independants-title">EPUB d’auteurs indépendants</h2>
        </div>
        <p>Comme pour les livres papier : prix fixé par le vendeur, fiche examinée avant publication, contact direct et aucun paiement sur la Librairie.</p>
      </div>
      <?php if ($independentEbooks === []): ?>
        <div class="ebook-compact-empty">
          <strong><?= $query === '' ? 'Les premiers EPUB indépendants sont attendus.' : 'Aucun EPUB indépendant ne correspond à cette recherche.' ?></strong>
          <span>Vous pouvez proposer un livre numérique, gratuit ou payant, avec son lien de diffusion ou son adresse de contact.</span>
        </div>
      <?php else: ?>
        <div class="ebook-indie-grid">
          <?php foreach ($independentEbooks as $book): ?>
            <article class="ebook-indie-card">
              <p class="eyebrow">EPUB indépendant<?= $book['book_format'] === 'both' ? ' · aussi disponible en papier' : '' ?></p>
              <h3><a href="/livre-auteur.php?id=<?= (int)$book['id'] ?>"><?= e($book['title']) ?></a></h3>
              <p class="ebook-indie-author"><?= e($book['author_name']) ?></p>
              <p><?= e(mb_strimwidth($book['description'],0,175,'…','UTF-8')) ?></p>
              <strong class="ebook-indie-price"><?= (int)$book['digital_price_cents'] === 0 ? 'Gratuit' : number_format((int)$book['digital_price_cents']/100,2,',',' ').' €' ?></strong>
              <a href="/livre-auteur.php?id=<?= (int)$book['id'] ?>" class="button-link">Voir la fiche et contacter le vendeur</a>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <p class="ebook-indie-note">Les prix sont déclarés par les vendeurs. Les éventuels frais de livraison ne concernent pas la fourniture d’un EPUB téléchargeable.</p>
    </section>

    <?php if ($query !== ''): ?>
    <section class="ebook-results-section" id="ebook-results" aria-labelledby="ebook-results-title">
        <div class="ebook-results-header">
            <div>
                <p class="section-kicker"><?= $query !== '' ? 'Résultats' : 'Recherche' ?></p>
                <h2 id="ebook-results-title"><?= $query !== '' ? '« ' . e($query) . ' »' : 'Rechercher un ebook' ?></h2>
                <p class="ebook-results-context"><?= e(modeLabel($mode)) ?> · <?= e(implode(' + ', array_map('strtoupper', $languages))) ?></p>
            </div>
            <div class="ebook-result-summary" aria-label="Résumé des résultats">
                <span><?= count($independentEbooks) ?> fiche<?= count($independentEbooks) === 1 ? '' : 's' ?> d’auteur<?= count($independentEbooks) === 1 ? '' : 's' ?> · <?= count($offers) ?> accès libre<?= count($offers) === 1 ? '' : 's' ?> indexé<?= count($offers) === 1 ? '' : 's' ?></span>
                <?php if ($discoveryDefinitions !== []): ?>
                    <span id="discovery-progress" aria-live="polite">0/<?= count($discoveryDefinitions) ?> sources interrogées…</span>
                <?php elseif ($query !== '' && $mode === 'paid'): ?>
                    <span>Vente directe par les auteurs uniquement</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($discoveryDefinitions !== []): ?>
            <div
                class="ebook-result-block ebook-federated-block"
                id="federated-results"
                data-discovery-search
                data-query="<?= e($query) ?>"
                data-languages="<?= e(implode(',', $languages)) ?>"
                data-sources="<?= e(json_encode(array_keys($discoveryDefinitions), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
                data-result-limit="8"
            >
                <div class="ebook-block-heading">
                    <div>
                        <p class="ebook-block-label">Recherche en direct</p>
                        <h3>Catalogues externes</h3>
                    </div>
                    <p>Les fichiers restent chez leur source d’origine. Une présence dans un catalogue ne remplace pas la vérification des droits applicable en France.</p>
                </div>

                <details class="ebook-source-details">
                    <summary>Voir les <?= count($discoveryDefinitions) ?> sources interrogées</summary>
                    <div class="source-pills" aria-label="Sources interrogées">
                        <?php foreach ($discoveryDefinitions as $definition): ?>
                            <span title="<?= e($definition['description']) ?>"><?= e($definition['name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                </details>

                <div id="external-result-list" class="external-result-list ebook-external-results" aria-live="polite">
                    <div class="discovery-state is-loading" role="status">
                        <span class="discovery-spinner" aria-hidden="true"></span>
                        <span>Recherche dans les catalogues…</span>
                    </div>
                </div>
                <noscript>
                    <p class="note">La recherche dans les catalogues externes nécessite JavaScript. Les offres déjà indexées restent accessibles ci-dessous.</p>
                </noscript>
            </div>
        <?php elseif ($query !== '' && $mode === 'paid'): ?>
            <div class="ebook-result-block ebook-federated-block is-muted" id="federated-results">
                <div class="ebook-block-heading">
                    <div>
                        <p class="ebook-block-label">Recherche en direct</p>
                        <h3>Catalogues externes</h3>
                    </div>
                </div>
                <div class="ebook-compact-empty">
                    <strong>Les ventes de livres numériques se font directement auprès des auteurs.</strong>
                    <span>Parcourez les fiches ci-dessous pour consulter les EPUB autoédités déjà référencés.</span>
                </div>
            </div>
        <?php endif; ?>

        <div class="ebook-result-block ebook-indexed-block" id="indexed-results">
            <div class="ebook-block-heading">
                <div>
                    <p class="ebook-block-label">Catalogue local</p>
                    <h3>Accès numériques libres déjà indexés</h3>
                </div>
                <p>Ces accès proviennent des catalogues libres identifiés ; la vente d’EPUB autoédités figure dans la section dédiée.</p>
            </div>

            <?php if ($offers === []): ?>
                <div class="ebook-compact-empty">
                    <strong>Aucun accès libre indexé pour cette recherche.</strong>
                    <span>
                        <?php if ($discoveryDefinitions !== []): ?>
                            Les résultats des catalogues externes restent visibles juste au-dessus.
                        <?php elseif ($query !== '' && $mode === 'paid'): ?>
                            Découvrez les livres numériques d’auteurs indépendants ci-dessous.
                        <?php else: ?>
                            Lancez une recherche pour interroger aussi les catalogues externes compatibles.
                        <?php endif; ?>
                    </span>
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
                                <p class="freshness-note">Vérifié le <?= e(substr((string) $offer['checked_at'], 0, 10)) ?><?= $offer['is_stale'] ? ' — revérification recommandée' : '' ?>.</p>
                            <?php endif; ?>
                            <a class="button-link" href="<?= e($offer['url']) ?>" target="_blank" rel="noopener noreferrer">Voir cette offre ↗</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="ebook-bottom-callout">
        <div>
            <p class="section-kicker">Auteurs et autrices</p>
            <h2>Faites découvrir votre livre sans nous envoyer votre EPUB.</h2>
            <p>Comme pour un livre papier, proposez un EPUB autoédité avec sa fiche, son prix public et un contact vendeur. La Librairie ne demande pas votre fichier et n’encaisse pas la vente. Le référencement de la vente directe exige une convention à 3 % signée avant la publication.</p>
        </div>
        <div><a class="button-link secondary" href="/proposer-livre.php">Référencer mon EPUB</a><p class="field-help"><a href="/conditions-generales.php">Conditions de référencement et commission</a></p></div>
    </section>
</main>

<footer>
    <div class="footer-links"><a href="/autoedition.php">Autoédition</a><a href="/feedback.php?kind=catalog">Signaler un problème de catalogue</a><a href="/projet.php">Le projet</a></div>
    <p>Bibliothèque sobre : gratuit et payant réunis, sans profilage comportemental.</p>
</footer>
</body>
</html>
