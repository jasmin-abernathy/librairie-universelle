<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/src/discovery-bootstrap.php';
// Un manuscrit non publié doit être paginé sans exécuter de script distant.
header('Content-Security-Policy: default-src \'none\'; script-src \'self\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data: blob:; font-src \'self\' data:; connect-src \'none\'; base-uri \'none\'; frame-ancestors \'none\'; form-action \'self\'; object-src \'none\'');
header('Referrer-Policy: no-referrer');
require_once dirname(__DIR__) . '/src/Csrf.php';
require_once dirname(__DIR__) . '/src/PrintSettings.php';
require_once dirname(__DIR__) . '/src/EpubPrintSource.php';
require_once dirname(__DIR__) . '/src/OfficePrintSource.php';
require_once dirname(__DIR__) . '/src/UploadLimits.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$error = null;
$book = null;
$settings = null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $error = 'Ouvrez cet outil depuis le formulaire d’autoédition.';
} elseif (UploadLimits::postExceeded((int) ($_SERVER['CONTENT_LENGTH'] ?? 0))) {
    $error = 'Le manuscrit est trop volumineux pour la limite HTTP du serveur. Réduisez le fichier puis réessayez.';
} elseif (!Csrf::validate($_POST['csrf_token'] ?? null)) {
    $error = 'La session du formulaire a expiré. Revenez à la page d’autoédition puis réessayez.';
} else {
    try {
        $settings = PrintSettings::normalize($_POST);
        $office = $_FILES['office_manuscript'] ?? [];
        if (($office['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $book = OfficePrintSource::fromUpload($office, UploadLimits::office($config));
        } elseif (($_FILES['ebook']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            // Compatibilité des anciennes compositions utilisant un EPUB existant.
            $book = EpubPrintSource::fromUpload($_FILES['ebook'], (int) $config['max_epub_bytes']);
        } else {
            throw new RuntimeException('Ajoutez votre manuscrit DOCX ou ODT dans l’onglet « Livre papier » avant de générer le PDF.');
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

if ($error !== null || $book === null || $settings === null):
?>
<!doctype html>
<html lang="fr">
<head>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Composition papier — Librairie universelle</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<main>
    <section class="page-hero compact">
        <p class="eyebrow">Composition papier</p>
        <h1>Impossible de préparer l’aperçu.</h1>
        <div class="error-box" role="alert"><?= e($error ?? 'Erreur inconnue.') ?></div>
        <p><a class="button-link" href="/autoedition.php">Retour à l’autoédition</a></p>
    </section>
</main>
</body>
</html>
<?php
exit;
endif;

$estimatedPages = max(1, (int) ceil(((int) $book['word_count']) / 280));
$styleQuery = http_build_query([
    'print_provider' => $settings['provider'],
    'print_trim_size' => $settings['trim_size'],
    'print_binding' => $settings['binding'],
    'print_chapter_start' => $settings['chapter_start'],
    'print_page_number_position' => $settings['page_number_position'],
    'print_front_matter_numbering' => $settings['front_matter_numbering'],
    'print_bleed_mm' => $settings['bleed_mm'],
    'print_gutter_mode' => $settings['gutter_mode'],
    'print_gutter_mm' => $settings['gutter_mm'],
    'print_hide_chapter_openers' => $settings['hide_chapter_openers'],
    'estimated_pages' => $estimatedPages,
]);
[$pageWidth, $pageHeight] = PrintSettings::trimDimensions($settings['trim_size']);
$gutter = $settings['gutter_mode'] === 'custom'
    ? (float) $settings['gutter_mm']
    : PrintSettings::automaticGutter($estimatedPages, $settings['binding']);
?>
<!doctype html>
<html lang="fr">
<head>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title><?= e($book['title']) ?> — aperçu papier</title>
    <link rel="stylesheet" href="/assets/css/print-preview.css">
    <link rel="stylesheet" href="/print-style.php?<?= e($styleQuery) ?>">
</head>
<body data-print-provider="<?= e((string)$settings['provider']) ?>" data-print-trim="<?= e((string)$settings['trim_size']) ?>" data-corep-max-pages="<?= PrintSettings::COREP_MAX_PAGES ?>">
<div class="print-toolbar">
    <div>
        <strong><?= e($book['title']) ?></strong>
        <small id="render-status">Pagination en cours…</small>
    </div>
    <div class="actions">
        <a href="/autoedition.php">Retour aux réglages</a>
        <button id="print-button" type="button" disabled>Enregistrer l’intérieur en PDF</button>
        <?php if ($settings['provider'] !== 'bookelis'): ?>
        <a href="/export-pdfx.php" title="Après avoir enregistré le PDF, le convertir en PDF/X">Puis convertir en PDF/X</a>
        <?php endif; ?>
    </div>
</div>

<p class="preview-message">
    Format <?= (int) $pageWidth ?> × <?= (int) $pageHeight ?> mm ·
    <?= $settings['binding'] === 'hardcover' ? 'relié' : 'broché' ?> ·
    marge intérieure <?= number_format($gutter, 1, ',', ' ') ?> mm<?= $settings['gutter_mode'] === 'auto' ? ' (auto)' : '' ?>.
    Profil <?= $settings['provider'] === 'bookelis' ? 'Bookelis' : 'Corep Metz' ?> · Notre générateur a recomposé votre manuscrit en pages de livre. Sélectionnez « Enregistrer au format PDF » dans le navigateur, avec les arrière-plans, à 100 %, sans en-têtes/pieds de page automatiques. Vérifiez soigneusement les pages et faites valider les fonds perdus, les polices et le BAT par l’imprimeur.
</p>
<?php if ($settings['provider'] === 'corep_metz'): ?>
<p class="corep-preset-note" role="note">
    <strong>Profil Corep Metz :</strong> texte courant composé en 10,5 pt ; éléments secondaires à partir de <?= number_format(PrintSettings::COREP_MIN_FONT_PT, 0, ',', ' ') ?> pt,
    avec <?= number_format(PrintSettings::COREP_RECOMMENDED_FONT_PT, 0, ',', ' ') ?> pt conseillés pour le confort de lecture.
    <strong>Limite : <?= PrintSettings::COREP_MAX_PAGES ?> pages A5 en recto-verso (340 feuilles sur papier 80 g).</strong>
    Le nombre de pages sera vérifié après la pagination. Les deux couvertures sont à transmettre séparément, sans fonds perdus ni traits de coupe.
</p>
<?php endif; ?>

<section class="preview-checklist" aria-labelledby="preview-check-title">
    <h2 id="preview-check-title">Contrôle de votre composition</h2>
    <p>Ce contrôle couvre la <strong>pagination visible</strong>, pas la conformité industrielle du PDF. Le BAT définitif reste à vérifier avec l’imprimeur.</p>
    <ul id="preview-audit" aria-live="polite"><li>Analyse des pages en cours…</li></ul>
    <label class="preview-confirm"><input id="preview-reviewed" type="checkbox"> J’ai parcouru les pages et vérifié la position des chapitres, les images, le sommaire et les pages blanches.</label>
    <p id="preview-review-status" role="status">L’export sera disponible après pagination et validation visuelle.</p>
</section>
<?php if ($settings['provider'] === 'bookelis'): ?>
<section class="bookelis-cover-handoff" id="bookelis-cover-handoff" aria-labelledby="bookelis-cover-heading" hidden>
    <p class="bookelis-step-number">Étape suivante · Bookelis</p>
    <h2 id="bookelis-cover-heading">Préparer votre couverture avec le gabarit officiel</h2>
    <p>Votre intérieur est paginé : <strong><span id="bookelis-real-pages">—</span> pages</strong> au format <strong><?= (int)$pageWidth ?> × <?= (int)$pageHeight ?> mm</strong>. Après avoir enregistré votre PDF intérieur et vérifié son nombre de pages, ouvrez le générateur de gabarits Bookelis.</p>
    <div class="bookelis-transfer" aria-labelledby="bookelis-transfer-heading">
        <h3 id="bookelis-transfer-heading">Vos informations déjà préparées</h3>
        <dl class="bookelis-transfer-data">
            <div><dt>Nombre de pages</dt><dd><strong id="bookelis-transfer-page-count">—</strong> <button type="button" id="bookelis-copy-pages">Copier le nombre</button></dd></div>
            <div><dt>Format à sélectionner chez Bookelis</dt><dd><strong id="bookelis-transfer-format"><?= (int)$pageWidth ?> × <?= (int)$pageHeight ?> mm</strong></dd></div>
            <div><dt>Couverture souhaitée</dt><dd><strong id="bookelis-transfer-binding"><?= $settings['binding'] === 'hardcover' ? 'Cartonnée' : 'Souple' ?></strong> (à confirmer chez Bookelis)</dd></div>
        </dl>
        <button type="button" id="bookelis-copy-summary" class="bookelis-transfer-copy">Copier le récapitulatif</button>
        <p id="bookelis-copy-feedback" role="status" aria-live="polite">Les autres caractéristiques (papier, couleurs, pelliculage, commercialisation) restent à choisir directement chez Bookelis.</p>
    </div>
    <ol>
        <li>Copiez le <strong>nombre réel de pages du PDF enregistré</strong>, puis sélectionnez le format correspondant chez Bookelis.</li>
        <li>Indiquez chez Bookelis le papier, le type de couverture, le pelliculage et le mode de commercialisation souhaités.</li>
        <li>Téléchargez leur gabarit, puis créez dessus <strong>un seul fichier de couverture à plat</strong> : quatrième, tranche et première.</li>
        <li>Contrôlez les images, les fonds perdus et les polices, puis déposez l'intérieur et la couverture directement dans votre espace Bookelis.</li>
    </ol>
    <p class="bookelis-cover-warning">Le gabarit Bookelis ne réalise pas la couverture à votre place. La largeur de tranche dépend de la pagination et du papier : <strong>ne la calculez pas au hasard</strong>. Aucun fichier n'est envoyé automatiquement à Bookelis et leur contrôle final reste nécessaire.</p>
    <a class="bookelis-cover-cta" href="https://livres.bookelis.com/generation-couverture" target="_blank" rel="noopener noreferrer">Ouvrir le générateur de gabarits Bookelis ↗</a>
</section>
<?php endif; ?>
<?php if ($book['warnings'] !== []): ?>
<div class="preview-warnings" role="status">
    <strong>À vérifier dans l’aperçu</strong>
    <ul><?php foreach ($book['warnings'] as $warning): ?><li><?= e($warning) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<template id="book-template">
    <article class="print-book">
        <?php if ((int) $settings['toc_enabled'] === 1): ?>
        <section class="frontmatter" id="table-of-contents">
            <h1>Sommaire</h1>
            <ol class="toc-list">
                <?php foreach ($book['chapters'] as $chapter): ?>
                    <li><a href="#<?= e($chapter['id']) ?>"><?= e($chapter['title']) ?></a></li>
                <?php endforeach; ?>
            </ol>
        </section>
        <?php endif; ?>

        <?php foreach ($book['chapters'] as $index => $chapter): ?>
        <section class="chapter<?= $index === 0 ? ' first-chapter' : '' ?>" id="<?= e($chapter['id']) ?>">
            <?= $chapter['html'] ?>
        </section>
        <?php endforeach; ?>
    </article>
</template>

<div id="book-pages" aria-live="polite"></div>

<script src="/assets/js/print-preview-config.js"></script>
<script src="/assets/vendor/pagedjs/paged.polyfill.js?v=0.4.3"></script>
<script src="/assets/js/print-preview.js"></script>
</body>
</html>
