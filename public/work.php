<?php

declare(strict_types=1);

[$config, $pdo] = require dirname(__DIR__) . '/src/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$record = $id ? WorkCatalog::find($pdo, (int) $id) : null;
$localAvailability = [];

if ($record === null) {
    http_response_code(404);
} elseif ($config['moselle_base_url'] !== '') {
    $isbns = [];
    foreach ($record['editions'] as $edition) {
        if (!empty($edition['isbn13'])) {
            $isbns[] = (string) $edition['isbn13'];
        }
    }
    $localAvailability = (new MoselleAvailabilityClient((string) $config['moselle_base_url']))->availabilityForIsbns($isbns);
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function offerLabel(string $type): string
{
    return match ($type) {
        'free_download' => 'Télécharger / exporter gratuitement',
        'read_online' => 'Lire en ligne gratuitement',
        'borrow' => 'Emprunter',
        'ebook' => 'Acheter l’ebook',
        'audio' => 'Écouter',
        'new' => 'Acheter neuf',
        'used' => 'Acheter d’occasion',
        default => 'Voir cette possibilité',
    };
}

function languageLabel(?string $language): string
{
    return match ($language) {
        'fr' => 'français',
        'en' => 'anglais',
        null, '' => 'non précisée',
        default => $language,
    };
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

function priceLabel(?int $cents, ?string $currency): string
{
    if ($cents === null) {
        return 'Prix à vérifier';
    }
    if ($cents === 0) {
        return 'Gratuit';
    }

    $amount = number_format($cents / 100, 2, ',', ' ');
    return $currency === 'EUR' || $currency === null ? $amount . ' €' : $amount . ' ' . $currency;
}

function offerIsStale(?string $checkedAt, int $maxAgeDays): bool
{
    if ($checkedAt === null || trim($checkedAt) === '') {
        return true;
    }
    try {
        return new DateTimeImmutable($checkedAt) < (new DateTimeImmutable('now'))->modify('-' . $maxAgeDays . ' days');
    } catch (Throwable) {
        return true;
    }
}

function localAvailabilityRows(array $availability): array
{
    $rows = [];
    foreach ($availability as $isbn => $locations) {
        if (!is_array($locations)) {
            continue;
        }
        foreach ($locations as $location) {
            if (is_array($location)) {
                $location['isbn13'] = (string) $isbn;
                $rows[] = $location;
            }
        }
    }
    return $rows;
}

$localRows = localAvailabilityRows($localAvailability);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f3ea">
    <title><?= $record ? e($record['work']['title']) . ' — ' : '' ?><?= e($config['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/work.css">
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
<?php if ($record === null): ?>
    <section class="page-shell compact-page">
        <p class="eyebrow">Œuvre introuvable</p>
        <h1>Cette fiche n’existe pas.</h1>
        <p class="lede">Le corpus du MVP est encore volontairement petit.</p>
        <a class="text-link" href="/">Revenir à la recherche</a>
    </section>
<?php else: ?>
    <?php $work = $record['work']; ?>
    <article class="work-detail page-shell">
        <p class="eyebrow">Fiche œuvre</p>
        <h1><?= e($work['title']) ?></h1>
        <?php if (!empty($work['original_title']) && $work['original_title'] !== $work['title']): ?>
            <p class="original-title">Titre original : <strong><?= e($work['original_title']) ?></strong></p>
        <?php endif; ?>
        <p class="lede"><?= e($work['contributors']) ?><?= $work['first_publication_year'] ? ' · ' . e((string) $work['first_publication_year']) : '' ?></p>

        <div class="work-facts" aria-label="Informations principales">
            <div><span>Langue originale</span><strong><?= e(languageLabel($work['language'])) ?></strong></div>
            <div><span>Domaine public</span><strong><?= e(rightsLabel($work['public_domain_status'])) ?></strong></div>
        </div>

        <?php if (!empty($work['public_domain_note'])): ?>
            <aside class="rights-note" aria-labelledby="rights-title">
                <h2 id="rights-title">Ce que ce statut veut dire ici</h2>
                <p><?= e($work['public_domain_note']) ?></p>
                <?php if ($work['language'] === 'en'): ?>
                    <p><strong>Important :</strong> une traduction française est une œuvre dérivée avec ses propres droits. Le MVP ne la considère jamais comme libre automatiquement.</p>
                <?php endif; ?>
            </aside>
        <?php endif; ?>

        <section class="access-section" aria-labelledby="access-title">
            <div class="section-heading">
                <h2 id="access-title">Comment lire cette œuvre ?</h2>
                <span><?= count($record['offers']) ?> possibilité<?= count($record['offers']) === 1 ? '' : 's' ?> vérifiée<?= count($record['offers']) === 1 ? '' : 's' ?></span>
            </div>

            <?php if ($record['offers'] === []): ?>
                <div class="empty-state">
                    <h3>Aucune source de lecture vérifiée pour l’instant.</h3>
                    <p>L’œuvre est déjà dans le corpus, mais le MVP préfère afficher « rien » plutôt qu’un lien dont les droits, le format ou la disponibilité n’ont pas été vérifiés.</p>
                </div>
            <?php else: ?>
                <div class="offer-list">
                <?php foreach ($record['offers'] as $offer): ?>
                    <?php $stale = offerIsStale($offer['checked_at'], (int) $config['offer_max_age_days']); ?>
                    <article class="offer-card">
                        <div>
                            <p class="work-kind"><?= e($offer['source_name']) ?></p>
                            <h3><?= e(offerLabel($offer['offer_type'])) ?> · <?= e(priceLabel($offer['price_cents'] !== null ? (int) $offer['price_cents'] : null, $offer['currency'])) ?></h3>
                            <p>
                                <?php if (!empty($offer['edition_title'])): ?><?= e($offer['edition_title']) ?> · <?php endif; ?>
                                <?php if (!empty($offer['edition_isbn13'])): ?>ISBN <?= e($offer['edition_isbn13']) ?> · <?php endif; ?>
                                <?php if (!empty($offer['edition_language'])): ?><?= e(languageLabel($offer['edition_language'])) ?> · <?php endif; ?>
                                <?= e($offer['file_format'] ?: 'format non précisé') ?>
                                <?php if (!empty($offer['drm_type'])): ?> · DRM : <?= e($offer['drm_type']) ?><?php endif; ?>
                            </p>
                            <?php if ($offer['availability'] === 'check_on_source'): ?>
                                <p class="availability-warning">Disponibilité à vérifier sur la source.</p>
                            <?php elseif (!empty($offer['checked_at'])): ?>
                                <p class="availability-warning">Donnée vérifiée le <?= e(substr((string) $offer['checked_at'], 0, 10)) ?><?= $stale ? ' — à revérifier avant achat.' : '.' ?></p>
                            <?php endif; ?>
                        </div>
                        <a class="primary-link" href="<?= e($offer['url']) ?>" rel="noopener noreferrer">Voir sur la source <span aria-hidden="true">↗</span></a>
                    </article>
                <?php endforeach; ?>
                </div>
                <p class="source-policy"><strong>Pourquoi le DRM est affiché par vendeur :</strong> une même édition numérique peut être livrée avec des protections différentes selon la plateforme. Le site ne déduit donc jamais un DRM global à partir du seul ISBN.</p>
            <?php endif; ?>
        </section>

        <?php if ($config['moselle_base_url'] !== ''): ?>
        <section class="access-section" aria-labelledby="moselle-title">
            <div class="section-heading">
                <h2 id="moselle-title">Acheter le livre en Moselle</h2>
                <span><?= count($localRows) ?> disponibilité<?= count($localRows) === 1 ? '' : 's' ?> confirmée<?= count($localRows) === 1 ? '' : 's' ?></span>
            </div>
            <?php if ($localRows === []): ?>
                <div class="empty-state">
                    <h3>Aucun stock local confirmé pour ces éditions.</h3>
                    <p>Le pilote Moselle n’affiche jamais une librairie simplement parce qu’elle existe : il faut qu’elle ait accepté le dispositif et qu’un stock ait été confirmé.</p>
                </div>
            <?php else: ?>
                <div class="offer-list">
                <?php foreach ($localRows as $location): ?>
                    <article class="offer-card">
                        <div>
                            <p class="work-kind">Librairie partenaire · Moselle</p>
                            <h3><?= e((string) ($location['bookstore_name'] ?? 'Librairie')) ?></h3>
                            <p>ISBN <?= e((string) ($location['isbn13'] ?? '')) ?> · <strong><?= (int) ($location['available_quantity'] ?? 0) ?> exemplaire<?= (int) ($location['available_quantity'] ?? 0) === 1 ? '' : 's' ?> disponible<?= (int) ($location['available_quantity'] ?? 0) === 1 ? '' : 's' ?></strong></p>
                            <p><?= e(trim((string) ($location['address'] ?? '') . ' ' . (string) ($location['postcode'] ?? '') . ' ' . (string) ($location['city'] ?? ''))) ?></p>
                            <?php if (!empty($location['checked_at'])): ?><p class="availability-warning">Stock confirmé le <?= e(substr((string) $location['checked_at'], 0, 10)) ?>.</p><?php endif; ?>
                        </div>
                        <a class="primary-link" href="<?= e(rtrim((string) $config['moselle_base_url'], '/') . '/bookstores.php?isbn=' . rawurlencode((string) ($location['isbn13'] ?? ''))) ?>">Voir le retrait local</a>
                    </article>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <p class="source-policy">Le pilote Moselle est une couche locale séparée : une librairie prospectée ou un stock non confirmé n’apparaît jamais ici.</p>
        </section>
        <?php endif; ?>

        <section class="edition-section" aria-labelledby="edition-title">
            <div class="section-heading">
                <h2 id="edition-title">Éditions identifiées</h2>
                <span><?= count($record['editions']) ?> édition<?= count($record['editions']) === 1 ? '' : 's' ?></span>
            </div>
            <?php if ($record['editions'] === []): ?>
                <p>Aucune édition précise n’a encore été ajoutée.</p>
            <?php else: ?>
                <div class="edition-list">
                <?php foreach ($record['editions'] as $edition): ?>
                    <article>
                        <h3><?= e($edition['title'] ?: $work['title']) ?></h3>
                        <p><?= e(languageLabel($edition['language'])) ?> · <?= e($edition['publication_date']) ?> · <?= e($edition['medium']) ?></p>
                        <?php if (!empty($edition['publisher'])): ?><p>Éditeur : <?= e($edition['publisher']) ?></p><?php endif; ?>
                        <?php if (!empty($edition['isbn13'])): ?><p>ISBN/EAN : <code><?= e($edition['isbn13']) ?></code></p><?php endif; ?>
                        <?php if (!empty($edition['edition_contributors'])): ?><p><?= e(str_replace(',', ' · ', $edition['edition_contributors'])) ?></p><?php endif; ?>
                        <?php if (!empty($edition['file_format'])): ?><p>Format : <?= e($edition['file_format']) ?><?php if (!empty($edition['drm_type'])): ?> · DRM de l’édition : <?= e($edition['drm_type']) ?><?php endif; ?></p><?php endif; ?>
                        <?php if (!empty($edition['source_urls'])): ?>
                            <?php $sourceUrl = explode(',', $edition['source_urls'])[0]; ?>
                            <p><a class="text-link" href="<?= e($sourceUrl) ?>" rel="noopener noreferrer">Notice source ↗</a></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <p class="source-policy">Les informations du MVP sont conservées avec leur provenance. Une œuvre, une édition et une offre sont trois objets différents : leurs droits, prix, DRM et disponibilités peuvent donc être différents.</p>
        <div class="cta-row">
            <a class="text-link" href="/">← Revenir à la recherche</a>
            <a class="text-link" href="/ebooks.php">Parcourir le storefront ebook</a>
            <a class="text-link" href="/feedback.php?kind=catalog">Signaler une erreur sur cette fiche</a>
        </div>
    </article>
<?php endif; ?>
</main>

<footer>
    <div class="footer-links"><a href="/ebooks.php">Ebooks</a><a href="/autoedition.php">Autoédition</a><a href="/feedback.php">Donner un retour</a></div>
    <p>Prototype sans publicité, sans traqueur et sans moteur de recommandation opaque.</p>
</footer>
</body>
</html>
