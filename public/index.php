<?php

declare(strict_types=1);

[$config, $pdo] = require dirname(__DIR__) . '/src/bootstrap.php';

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

$results = $query !== '' ? Search::works($pdo, $query, 30, $languages) : [];
$discoveryDefinitions = !empty($config['federated_search_enabled'])
    ? array_filter(
        DiscoveryRegistry::definitions(),
        static fn (array $definition): bool => array_intersect($definition['languages'] ?? [], $languages) !== []
    )
    : [];

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
    <meta name="description" content="Chercher une œuvre et choisir comment la lire : ebook payant ou gratuit, papier, bibliothèque ou domaine public. Prototype sans IA ni profilage.">
    <meta name="theme-color" content="#f7f3ea">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Une recherche. Toutes les façons légales de lire.">
    <meta property="og:description" content="Un MVP centré sur l’œuvre : numérique payant ou gratuit, domaine public, autoédition validée et sources identifiées, sans IA.">
    <title><?= e($config['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/discovery.css">
    <script src="/assets/js/app.js" defer></script>
    <script src="/assets/js/discovery.js" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<header class="site-header">
    <a class="brand" href="/" aria-label="Accueil — Librairie universelle">📚 <span>Librairie universelle <small>nom de travail</small></span></a>
    <nav class="site-nav" aria-label="Navigation principale">
        <a href="/ebooks.php">Ebooks</a>
        <a href="/autoedition.php">Autoédition</a>
        <a href="/projet.php">Le projet</a>
        <a href="/sans-ia.php">Sans IA</a>
    </nav>
</header>

<main id="main">
    <section class="hero" aria-labelledby="hero-title">
        <div class="hero-meta"><span class="status-badge">MVP en construction</span><span>Recherche déterministe · sources explicites</span></div>
        <p class="eyebrow">Une recherche. Toutes les façons légales de lire.</p>
        <h1 id="hero-title">Chercher une œuvre, pas un produit.</h1>
        <p class="lede">Ebook payant ou gratuit, papier, audio, librairie locale, bibliothèque ou domaine public : le choix reste visible au lieu d’être décidé à votre place.</p>

        <form class="search-form" action="/" method="get" role="search">
            <label for="q">Titre, auteur ou autrice</label>
            <div class="search-row">
                <input id="q" name="q" type="search" value="<?= e($query) ?>" placeholder="Ex. Les Misérables, George Orwell…" autocomplete="off">
                <button type="submit">Rechercher</button>
            </div>
            <fieldset class="language-filter">
                <legend>Langue des résultats</legend>
                <label>
                    <input type="checkbox" name="lang[]" value="fr" <?= in_array('fr', $languages, true) ? 'checked' : '' ?>>
                    Français
                </label>
                <label>
                    <input type="checkbox" name="lang[]" value="en" <?= in_array('en', $languages, true) ? 'checked' : '' ?>>
                    Anglais
                </label>
                <small>Vous pouvez cocher les deux.</small>
            </fieldset>
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
            <a class="button-link" href="/ebooks.php">Parcourir les ebooks</a>
            <a class="text-link" href="/autoedition.php">Publier en indépendant</a>
            <a class="text-link" href="/projet.php">Comprendre le MVP</a>
        </div>
    </section>

    <?php if ($query !== ''): ?>
        <section class="results" aria-labelledby="results-title">
            <div class="section-heading">
                <h2 id="results-title">Résultats pour « <?= e($query) ?> »</h2>
                <div>
                    <span><?= count($results) ?> résultat<?= count($results) === 1 ? '' : 's' ?></span>
                    <a class="text-link" href="/">Effacer la recherche</a>
                </div>
            </div>

            <?php if ($results === []): ?>
                <div class="empty-state">
                    <h3>Pas encore dans notre catalogue local.</h3>
                    <p>La recherche continue automatiquement dans les catalogues externes affichés juste après cette section.</p>
                    <div class="cta-row">
                        <a class="text-link" href="/">Effacer la recherche</a>
                        <a class="text-link" href="/?q=George+Orwell">Essayer avec George Orwell</a>
                    </div>
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
                                <div><dt>Langue d’origine</dt><dd><?= e(strtoupper((string) ($result['language'] ?? '—'))) ?></dd></div>
                                <div><dt>Domaine public</dt><dd><?= e(rightsLabel($result['public_domain_status'])) ?></dd></div>
                            </dl>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($query !== '' && $discoveryDefinitions !== []): ?>
        <section
            class="results external-results"
            id="external-results"
            aria-labelledby="external-results-title"
            data-discovery-search
            data-query="<?= e($query) ?>"
            data-languages="<?= e(implode(',', $languages)) ?>"
            data-sources="<?= e(json_encode(array_keys($discoveryDefinitions), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
        >
            <div class="section-heading">
                <div>
                    <p class="section-kicker">Recherche fédérée</p>
                    <h2 id="external-results-title">Autres catalogues</h2>
                </div>
                <span id="discovery-progress" aria-live="polite">Préparation de la recherche…</span>
            </div>
            <p class="lede small">Ces résultats viennent de sources externes identifiées. Une présence dans un catalogue ne vaut jamais, à elle seule, autorisation de téléchargement en France.</p>
            <div class="source-pills" aria-label="Sources interrogées">
                <?php foreach ($discoveryDefinitions as $definition): ?>
                    <span title="<?= e($definition['description']) ?>"><?= e($definition['name']) ?></span>
                <?php endforeach; ?>
            </div>
            <div id="external-result-list" class="external-result-list" aria-live="polite">
                <p class="discovery-loading">Les catalogues sont interrogés progressivement pour ne pas bloquer la page.</p>
            </div>
            <noscript>
                <p class="note">La recherche dans les catalogues externes nécessite JavaScript. La recherche locale reste entièrement fonctionnelle sans JavaScript.</p>
            </noscript>
        </section>
    <?php endif; ?>

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
            <h2 id="modes-title">Ce que cette fiche d’œuvre réunit progressivement</h2>
            <div class="mode-grid">
                <article><span aria-hidden="true">⚡</span><h3>Ebooks</h3><p>Gratuits et payants dans le même storefront, avec format, DRM, prix, fraîcheur et source.</p></article>
                <article><span aria-hidden="true">✍️</span><h3>Autoédition</h3><p>Des auteurs indépendants, mais après contrôle des fichiers et validation humaine — jamais par publication automatique de masse.</p></article>
                <article><span aria-hidden="true">🏪</span><h3>Librairies</h3><p>Neuf, occasion, retrait local et disponibilité lorsque la donnée est réellement accessible.</p></article>
                <article><span aria-hidden="true">🟢</span><h3>Domaine public</h3><p>Lire ou télécharger gratuitement depuis une source légitime et identifiable.</p></article>
            </div>
        </section>

        <section class="content-section callout" aria-labelledby="scope-title">
            <div>
                <div class="section-kicker">Déjà testable</div>
                <h2 id="scope-title">Le storefront numérique et le parcours auteur sont codés.</h2>
                <p>Les Misérables, Orwell et les premières offres commerciales servent de cas réels. Les dépôts d’autoédition restent fermés par défaut jusqu’à l’activation de l’alpha.</p>
            </div>
            <div class="cta-row">
                <a class="button-link secondary" href="/ebooks.php">Voir les ebooks</a>
                <a class="text-link" href="/autoedition.php">Voir l’autoédition</a>
            </div>
        </section>
</main>

<footer>
    <div class="footer-links">
        <a href="/ebooks.php">Ebooks</a>
        <a href="/autoedition.php">Autoédition</a>
        <a href="/feedback.php">Donner un retour</a>
        <a href="/projet.php">Le projet</a>
        <a href="/sans-ia.php">Sans IA</a>
    </div>
    <p>Prototype sans publicité, sans traqueur et sans moteur de recommandation opaque.</p>
</footer>
</body>
</html>
