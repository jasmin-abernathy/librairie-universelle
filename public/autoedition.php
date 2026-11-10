<?php

declare(strict_types=1);

[$config, $pdo] = require dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/UploadLimits.php';
$service = new SelfPublishingService($pdo, $config);
$errors = [];
$successId = null;
$sourceTool = isset($_GET['source']) ? trim((string) $_GET['source']) : '';
$fromAtelier = $sourceTool === 'atelier-epub';
// Initialiser le cookie et le jeton CSRF avant toute sortie HTML.
// Sans cela, certains hébergements n’envoient pas PHPSESSID et les imports échouent.
require_once dirname(__DIR__) . '/src/Csrf.php';
$paperCsrfToken = Csrf::token();
header('Cache-Control: private, no-store');

// L'ancien formulaire n'accepte plus de manuscrits, même si la variable legacy est active.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(410);
    $errors[] = 'La Librairie ne reçoit plus de manuscrits. Pour référencer votre livre, proposez uniquement une fiche descriptive.';
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function old(string $key): string
{
    return e(isset($_POST[$key]) ? (string) $_POST[$key] : '');
}

function checked(string $key, bool $default = false): string
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return !empty($_POST[$key]) ? ' checked' : '';
    }
    return $default ? ' checked' : '';
}

$selectedPrinter = ($_POST['print_provider'] ?? 'corep_metz') === 'bookelis' ? 'bookelis' : 'corep_metz';
$selectedTrim = (string)($_POST['print_trim_size'] ?? 'a5');
$bookelisFormats = ['130x180' => '13 × 18 cm — poche', '156x234' => '15,6 × 23,4 cm', '170x244' => '17 × 24,4 cm', '170x170' => '17 × 17 cm — carré', '190x150' => '19 × 15 cm — paysage', 'a4' => 'A4 — 21 × 29,7 cm'];
$printPreparationRequested = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? !empty($_POST['print_preparation_requested']) : true;
require_once dirname(__DIR__) . '/src/MosellePrintGridClient.php';
$publicPrintGrids = (new MosellePrintGridClient((string) ($config['moselle_base_url'] ?? '')))->publishedGrids();
// Copie locale autorisée seulement si aucun pilote Moselle n'est configuré.
// Une API configurée renvoyant [] (retrait, panne, expiration) prime toujours.
if ($publicPrintGrids === [] && (string) ($config['moselle_base_url'] ?? '') === '') {
    require_once dirname(__DIR__) . '/src/ApprovedPrintPriceSnapshot.php';
    $publicPrintGrids = ApprovedPrintPriceSnapshot::grids();
}
?>
<!doctype html>
<html lang="fr">
<head>
<link rel="canonical" href="https://librairie.lepotager.org/autoedition.php">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <meta name="description" content="Créer son EPUB, préparer ses PDF d’impression et faire découvrir son livre grâce à une fiche vérifiée avant publication. Aucun dépôt de manuscrit.">
    <title>Autoédition — <?= e($config['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css?v=20261104-nav2">
    <link rel="stylesheet" href="/assets/css/printing-estimate.css?v=20261013-1">
    <link rel="stylesheet" href="/assets/css/autoedition-tabs.css?v=20261018-2">
    <link rel="stylesheet" href="/assets/css/author-draft.css?v=20261018-3">
    <link rel="stylesheet" href="/assets/css/autoedition-paper.css?v=20261019-1">
    <link rel="stylesheet" href="/assets/css/print-provider-flow.css?v=20261110-3">
    <link rel="stylesheet" href="/assets/css/paper-wizard.css?v=20261110-4">
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<?php $currentNav = 'autoedition'; require dirname(__DIR__) . '/src/views/site-header.php'; ?>

<main id="main">
    <section class="page-hero compact">
        <p class="eyebrow">Auteurs et autrices indépendants</p>
        <h1>Préparer mon livre et le faire découvrir.</h1>
        <p class="lede">Préparez votre EPUB ou vos PDF pour l’impression, puis téléchargez-les librement. Pour être référencé, <strong>proposez uniquement une fiche descriptive</strong> : aucun manuscrit n’est transmis à la Librairie, et tu vérifies chaque fiche avant publication.</p>
        <p><a class="button-link" href="/proposer-livre.php">Proposer la fiche de mon livre →</a></p>
        <?php if ((string) $config['atelier_epub_url'] !== ''): ?>
            <p><a class="button-link secondary" href="<?= e((string) $config['atelier_epub_url']) ?>" target="_blank" rel="noopener noreferrer">Créer ou corriger mon EPUB dans Atelier EPUB ↗</a></p>
        <?php endif; ?>
        <?php if ($fromAtelier): ?>
            <div class="resource-card important-resource">
                <div>
                    <strong>Vous arrivez d’Atelier EPUB.</strong>
                    <p>Vous pouvez télécharger votre EPUB depuis Atelier EPUB. Pour figurer au catalogue, <a href="/proposer-livre.php">proposez sa fiche descriptive</a>, sans transmettre votre manuscrit ni votre EPUB.</p>
                    <p><strong>Cette décision n’est pas déléguée à une IA.</strong></p>
                </div>
            </div>
        <?php endif; ?>
        <p class="note"><strong>Deux parcours complémentaires :</strong> <a href="/proposer-livre.php">proposer votre livre au catalogue</a> et <a href="/diffuser-mon-livre.php">préparer vos contacts avec des librairies</a>. Les EPUB, PDF et dossiers ZIP restent téléchargeables. Aucun fichier n’est transmis automatiquement à une librairie ou à un imprimeur.</p>
    </section>

    <?php if ($successId !== null): ?>
        <section class="content-section">
            <div class="success-box" role="status">
                <h2>Dépôt reçu.</h2>
                <p>Référence interne : <strong>#<?= (int) $successId ?></strong>. Le livre n’est pas encore publié : la fiche doit d’abord être examinée avant publication. Une fois validé et publié, sa fiche apparaîtra dans le catalogue.</p>
                <p>Vous pouvez déjà préparer la suite, sans contacter les librairies avant d'avoir confirmé les informations de vente : <a href="/diffuser-mon-livre.php"><strong>préparer ma fiche de présentation et mon premier contact avec les librairies →</strong></a></p>
            </div>
        </section>
    <?php else: ?>
        <?php if ($errors !== []): ?>
            <div class="error-box" role="alert">
                <strong>Le dépôt de manuscrit est désactivé.</strong>
                <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <section class="autoedition-entry" aria-labelledby="autoedition-entry-heading">
            <div class="autoedition-entry-main">
                <p class="autoedition-entry-eyebrow">Votre livre, à votre rythme</p>
                <h2 id="autoedition-entry-heading">Par où souhaitez-vous commencer ?</h2>
                <p>Un livre papier, un EPUB ou les deux : préparez vos fichiers et téléchargez-les. Les champs de préparation ne soumettent aucun manuscrit à la Librairie. Pour figurer au catalogue, <a href="/proposer-livre.php">proposez une fiche descriptive</a>.</p>
                <p><a class="text-link" href="/diffuser-mon-livre.php">Et après ? Découvrir le parcours pour faire connaître et vendre mon livre →</a></p>
                <p><a class="text-link" href="/prestations-auteurs.php">Besoin de faire vérifier vos PDF (79 € HT) ou de corriger votre texte (dès 179 € HT) ? Découvrir nos prestations facultatives →</a></p>
            </div>
            <div class="autoedition-entry-promise">
                <span aria-hidden="true" class="autoedition-entry-promise-icon">✓</span>
                <span><strong>Mise en page papier incluse</strong><small>Importez un DOCX, un ODT ou un EPUB. Notre générateur compose les pages avant votre export PDF.</small></span>
            </div>
        </section>
        <nav class="autoedition-tabs" role="tablist" aria-label="Parties du parcours d’autoédition">
            <button type="button" class="autoedition-tab" id="tab-paper" role="tab" aria-controls="panel-paper" aria-selected="true" tabindex="0" data-panel="paper">
                <span class="autoedition-tab-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6.5c-2.7-1.8-5.3-2.3-9-1.5v14c3.7-.8 6.3-.3 9 1.5m0-14c2.7-1.8 5.3-2.3 9-1.5v14c-3.7-.8-6.3-.3-9 1.5m0-14v14"/></svg></span>
                <span class="autoedition-tab-copy"><strong>Livre papier</strong><small>Mise en page et impression</small></span>
            </button>
            <button type="button" class="autoedition-tab" id="tab-project" role="tab" aria-controls="panel-project" aria-selected="false" tabindex="-1" data-panel="project">
                <span class="autoedition-tab-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm7 0v6h5M9 13h6M9 17h7"/></svg></span>
                <span class="autoedition-tab-copy"><strong>Mon livre</strong><small>Auteur, titre, ISBN</small></span>
            </button>
            <button type="button" class="autoedition-tab" id="tab-digital" role="tab" aria-controls="panel-digital" aria-selected="false" tabindex="-1" data-panel="digital">
                <span class="autoedition-tab-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="2.5" width="12" height="19" rx="2"/><path d="M10 18h4"/></svg></span>
                <span class="autoedition-tab-copy"><strong>Numérique</strong><small>Version EPUB</small></span>
            </button>

        </nav>
        <p class="autoedition-tab-context" id="autoedition-tab-context" role="status" aria-live="polite">Livre papier · Composez votre manuscrit et préparez son impression.</p>
        <form class="submission-form" method="post" enctype="multipart/form-data" id="autoedition-form">
            <input type="hidden" name="csrf_token" value="<?= e($paperCsrfToken) ?>">
            <input type="hidden" name="active_panel" value="<?= e((string) ($_POST['active_panel'] ?? 'paper')) ?>" id="active-panel">

            <section class="autoedition-panel" id="panel-project" role="tabpanel" aria-labelledby="tab-project" data-panel-name="project">
            <fieldset>
                <legend>1. Qui publie ?</legend>
                <div class="form-grid two-columns">
                    <label>Nom civil ou nom de l’autoéditeur
                        <input name="author_name" required maxlength="180" value="<?= old('author_name') ?>" autocomplete="name">
                    </label>
                    <label>Pseudonyme <span class="optional">facultatif</span>
                        <input name="pseudonym" maxlength="180" value="<?= old('pseudonym') ?>">
                    </label>
                    <label>Adresse e-mail
                        <input type="email" name="email" required maxlength="254" value="<?= old('email') ?>" autocomplete="email">
                    </label>
                </div>
            </fieldset>

            <fieldset>
                <legend>2. Le livre</legend>
                <div class="form-grid two-columns">
                    <label>Titre
                        <input name="title" required maxlength="300" value="<?= old('title') ?>">
                    </label>
                    <label>Sous-titre <span class="optional">facultatif</span>
                        <input name="subtitle" maxlength="300" value="<?= old('subtitle') ?>">
                    </label>
                    <label>Langue
                        <select name="language">
                            <option value="fr"<?= ($_POST['language'] ?? 'fr') === 'fr' ? ' selected' : '' ?>>Français</option>
                            <option value="en"<?= ($_POST['language'] ?? '') === 'en' ? ' selected' : '' ?>>Anglais</option>
                        </select>
                    </label>
                </div>
                <label>Présentation du livre
                    <textarea name="description" required rows="8" maxlength="6000" placeholder="Résumé, genre, public visé, contexte éditorial…"><?= old('description') ?></textarea>
                </label>
            </fieldset>

            <fieldset>
                <legend>3. ISBN</legend>
                <p class="field-help">Vous pouvez déposer le dossier avant d’avoir tous vos ISBN. En revanche, chaque manifestation commercialisée séparément doit être identifiée correctement avant publication.</p>
                <div class="resource-card important-resource">
                    <div>
                        <strong>Vous n’avez pas encore d’ISBN ?</strong>
                        <p>L’AFNIL propose le formulaire officiel pour les particuliers autoédités. L’AFNIL précise aussi qu’un EPUB et une version papier commercialisés séparément utilisent des ISBN distincts.</p>
                    </div>
                    <a class="button-link secondary" href="https://www.afnil.org/formulaire/demande_isbn_particulier/" target="_blank" rel="noopener noreferrer">Demander des ISBN à l’AFNIL ↗</a>
                </div>
                <div class="form-grid two-columns">
                    <label>ISBN de l’EPUB <span class="optional">facultatif au dépôt</span>
                        <input name="isbn_ebook" inputmode="numeric" maxlength="24" value="<?= old('isbn_ebook') ?>" placeholder="978…">
                    </label>
                    <label>ISBN papier <span class="optional">facultatif au dépôt</span>
                        <input name="isbn_paper" inputmode="numeric" maxlength="24" value="<?= old('isbn_paper') ?>" placeholder="978…">
                    </label>
                </div>
            </fieldset>

            </section>
            <section class="autoedition-panel" id="panel-digital" role="tabpanel" aria-labelledby="tab-digital" data-panel-name="digital">
            <p class="field-help">La publication numérique est indépendante de l’impression papier. Si vous ne souhaitez qu’un livre papier, passez directement à l’onglet « Livre papier ».</p>
            <?php if ((string) ($config['atelier_epub_url'] ?? '') !== ''): ?>
                <p class="autoedition-epub-generator-link">
                    <a class="button-link secondary" href="<?= e((string) $config['atelier_epub_url']) ?>" target="_blank" rel="noopener noreferrer">Ouvrir le générateur EPUB <span aria-hidden="true">↗</span></a>
                    <span class="field-help">Créez ou corrigez votre EPUB dans un nouvel onglet, puis revenez ici pour l’importer.</span>
                </p>
            <?php endif; ?>
            <fieldset>
                <legend>Version numérique — EPUB</legend>
                <div class="form-grid two-columns">
                    <label>EPUB
                        <input type="file" name="ebook" accept=".epub,application/epub+zip">
                        <span class="field-help">EPUB uniquement. Le serveur contrôle type, taille et structure lorsque les extensions PHP nécessaires sont disponibles.</span>
                    </label>
                    <label>Mode envisagé
                        <select name="ebook_distribution">
                            <option value="paid"<?= ($_POST['ebook_distribution'] ?? 'paid') === 'paid' ? ' selected' : '' ?>>Payant</option>
                            <option value="free"<?= ($_POST['ebook_distribution'] ?? '') === 'free' ? ' selected' : '' ?>>Gratuit</option>
                        </select>
                    </label>
                    <label>Prix ebook envisagé (€) <span class="optional">si payant</span>
                        <input name="ebook_price" inputmode="decimal" value="<?= old('ebook_price') ?>" placeholder="4,99">
                    </label>
                    <label>Couverture
                        <input type="file" name="cover" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp">
                    </label>
                </div>
            </fieldset>

            </section>
            <section class="autoedition-panel" id="panel-paper" role="tabpanel" aria-labelledby="tab-paper" data-panel-name="paper">
            <fieldset>
                <legend>Publication papier : préparation et impression</legend>
                <nav class="paper-step-nav" aria-label="Accès aux étapes de préparation papier">
                    <a href="#paper-interior-step"><span>1</span> Manuscrit</a>
                    <a href="#paper-printer-step"><span>2</span> Imprimeur et format</a>
                    <a href="#paper-pdfx-step"><span>3</span> PDF/X <small>facultatif</small></a>
                    <a href="#paper-covers-step"><span>4</span> Couverture</a>
                    <a href="#print-cost-estimator"><span>5</span> Prix</a>
                    <a href="#paper-dossier-step"><span>6</span> Préparer les fichiers</a>
                    <a href="#paper-corep-step"><span>7</span> Transmettre</a>
                </nav>

                <div class="resource-card important-resource">
                    <div>
                        <strong>Notre générateur réalise la mise en page de votre manuscrit.</strong>
                        <p><strong>Ajoutez votre livre en DOCX, ODT ou EPUB</strong> : la Librairie recomposera l'intérieur en pages de livre, avec sommaire, marges de reliure, pagination recto-verso et pages blanches nécessaires. Vous pourrez ensuite enregistrer le résultat en PDF pour l'imprimeur, sans modifier votre fichier d'origine.</p>
                        <p>Le manuscrit source reste inchangé. La mise en page est recalculée au format choisi pour l'imprimeur : A5 pour Corep, ou l'un des formats proposés par Bookelis. Ce n'est pas une simple réduction de page. La conversion n'est pas une reproduction exacte du document bureautique : relisez l'aperçu, en particulier les tableaux, les notes, les images et la taille des caractères, puis faites valider le PDF final par l'imprimeur.</p>
                    </div>
                </div>

                <div class="resource-card important-resource" id="paper-interior-step">
                    <div>
                        <strong>Choisissez le fichier de votre livre</strong>
                        <p>Formats acceptés : <strong>.pdf, .docx, .odt, .epub</strong>. Un PDF déjà mis en page sera conservé tel quel. Un DOCX, ODT ou EPUB sans DRM sera recomposé pour l'impression. Les fichiers TXT et les EPUB protégés ne sont pas pris en charge.</p>
                        <label>Manuscrit papier à mettre en page
                            <input type="file" name="office_manuscript" accept=".docx,.odt,.epub,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text,application/epub+zip">
                            <span class="field-help">Fichier lu temporairement pour l'aperçu, jamais envoyé à l'imprimeur automatiquement. Taille maximale selon votre serveur : <?= e(UploadLimits::officeLabel($config)) ?>. Contrôlez les images, notes, tableaux et styles complexes après recomposition.</span>
                        </label>
                    </div>
                </div>

                <label class="check-row">
                    <input id="print-preparation-requested" type="checkbox" name="print_preparation_requested" value="1" aria-controls="print-layout-settings" aria-expanded="<?= $printPreparationRequested ? 'true' : 'false' ?>"<?= checked('print_preparation_requested', true) ?>>
                    <span><strong>Je veux préparer la mise en page de mon livre papier.</strong><br><span class="field-help">L’assistant travaille uniquement sur la version papier : l’EPUB d’origine n’est ni réécrit ni corrigé ici. Vous pouvez ensuite imprimer l’aperçu au format PDF.</span></span>
                </label>

                <div id="print-layout-settings"<?= $printPreparationRequested ? '' : ' hidden' ?>>
                    <fieldset class="paper-provider-fieldset" id="paper-printer-step">
                        <legend>2. Choisir où imprimer votre livre</legend>
                        <p>Choisissez d’abord le <strong>mode de fabrication</strong> de votre livre. Le format et le papier déterminent la pagination et les fichiers de couverture nécessaires. Vous pourrez revenir sur ce choix sans réimporter votre manuscrit.</p>
                        <div class="paper-provider-paths">
                            <section class="paper-provider-group paper-provider-group-local" aria-labelledby="local-printers-title">
                                <h3 id="local-printers-title">Imprimeries locales</h3>
                                <p>Pour faire fabriquer un tirage auprès d’un atelier de la région, récupérer les livres localement et traiter directement avec l’imprimeur.</p>
                                <div class="paper-provider-grid">
                                    <label class="paper-provider-card">
                                        <input type="radio" name="print_provider" value="corep_metz"<?= $selectedPrinter === 'corep_metz' ? ' checked' : '' ?>>
                                        <span class="paper-provider-title"><strong>Corep Metz</strong><small>Atelier local · livre A5</small></span>
                                        <span class="paper-provider-detail">Broché A5, intérieur noir et blanc sur offset 80 g, première et quatrième de couverture séparées. 680 pages recto-verso maximum d’après les consignes reçues.</span>
                                        <span class="paper-provider-price" id="printer-corep-quote">Tarif indicatif selon la grille autorisée, si disponible.</span>
                                    </label>
                                </div>
                            </section>
                            <section class="paper-provider-group paper-provider-group-pod" aria-labelledby="bookelis-path-title">
                                <h3 id="bookelis-path-title">Autoédition et impression à la demande</h3>
                                <p>Pour gérer un projet sur une plateforme d’autoédition, avec ses propres choix de fabrication, son gabarit et, selon l’offre, des services de distribution.</p>
                                <div class="paper-provider-grid">
                                    <label class="paper-provider-card paper-provider-card-pod">
                                        <input type="radio" name="print_provider" value="bookelis"<?= $selectedPrinter === 'bookelis' ? ' checked' : '' ?>>
                                        <span class="paper-provider-title"><strong>Bookelis</strong><small>Plateforme d’autoédition · impression à la demande</small></span>
                                        <span class="paper-provider-detail">Plusieurs formats et papiers, couverture complète à plat selon le gabarit officiel, puis dépôt des PDF sur le site Bookelis.</span>
                                        <span class="paper-provider-price" id="printer-bookelis-quote">Devis à calculer chez Bookelis, sans tarif automatique dans la Librairie.</span>
                                    </label>
                                </div>
                            </section>
                        </div>
                        <p class="field-help">Les deux parcours sont indépendants. Pour l’impression locale, d’autres prestataires pourront être ajoutés après confirmation de leurs caractéristiques et tarifs. Aucun devis n’est garanti ni aucune commande transmise depuis la Librairie.</p>
                        <div class="paper-stock-choice">
                            <label for="print-paper-stock"><strong>Type de papier intérieur</strong></label>
                            <select id="print-paper-stock" name="print_paper_stock" aria-describedby="paper-stock-help">
                                <option value="offset80">Offset blanc 80 g — Corep ou Bookelis</option>
                                <option value="olin90" data-bookelis-paper="1">Olin Regular blanc 90 g — Bookelis</option>
                                <option value="bouffant80" data-bookelis-paper="1">Bouffant blanc 80 g — Bookelis</option>
                                <option value="recycle90" data-bookelis-paper="1">Recyclé Cyclus Offset 90 g — Bookelis</option>
                                <option value="couche115" data-bookelis-paper="1" data-sizes="small">Couché demi-mat 115 g — Bookelis, petits formats</option>
                                <option value="couche150" data-bookelis-paper="1" data-sizes="large">Couché demi-mat 150 g — Bookelis, grands formats</option>
                            </select>
                            <p class="field-help" id="paper-stock-help">Le grammage ne suffit pas à connaître l’épaisseur du dos. Choisissez d’abord le papier ; l’imprimeur précisera son épaisseur réelle.</p>
                        </div>
                    </fieldset>
                    <div class="form-grid two-columns">
                        <label>Format fini du livre
                            <select id="print-trim-size" name="print_trim_size" aria-describedby="paper-a5-help">
                                <option value="a5"<?= $selectedTrim === 'a5' ? ' selected' : '' ?>>A5 — 14,8 × 21 cm (Corep ou Bookelis)</option>
                                <?php foreach ($bookelisFormats as $size => $label): ?>
                                    <option value="<?= e($size) ?>" data-bookelis-size="1"<?= $selectedTrim === $size ? ' selected' : '' ?><?= $selectedPrinter === 'bookelis' ? '' : ' disabled hidden' ?>><?= e($label) ?> (Bookelis)</option>
                                <?php endforeach; ?>
                            </select>
                            <span class="field-help" id="paper-a5-help">Corep : A5 uniquement. Bookelis : 7 formats possibles. Le manuscrit est recomposé à la bonne taille, jamais réduit depuis un A4 ; le nombre de pages peut changer.</span>
                        </label>
                        <label>Reliure
                            <select name="print_binding">
                                <option value="paperback"<?= ($_POST['print_binding'] ?? 'paperback') === 'paperback' ? ' selected' : '' ?>>Broché</option>
                                <option value="hardcover"<?= ($_POST['print_binding'] ?? '') === 'hardcover' ? ' selected' : '' ?>>Relié</option>
                            </select>
                        </label>
                        <input type="hidden" name="print_chapter_start" value="right">
                        <label>Numéros de page
                            <select name="print_page_number_position">
                                <option value="outside"<?= ($_POST['print_page_number_position'] ?? 'outside') === 'outside' ? ' selected' : '' ?>>Côté extérieur — gauche sur page gauche, droite sur page droite</option>
                                <option value="center"<?= ($_POST['print_page_number_position'] ?? '') === 'center' ? ' selected' : '' ?>>Centrés en bas</option>
                                <option value="none"<?= ($_POST['print_page_number_position'] ?? '') === 'none' ? ' selected' : '' ?>>Aucun numéro visible</option>
                            </select>
                        </label>
                        <label>Pages liminaires
                            <select name="print_front_matter_numbering">
                                <option value="roman"<?= ($_POST['print_front_matter_numbering'] ?? 'roman') === 'roman' ? ' selected' : '' ?>>Chiffres romains, puis 1 au début du texte</option>
                                <option value="hidden"<?= ($_POST['print_front_matter_numbering'] ?? '') === 'hidden' ? ' selected' : '' ?>>Comptées mais numéros masqués</option>
                                <option value="arabic"<?= ($_POST['print_front_matter_numbering'] ?? '') === 'arabic' ? ' selected' : '' ?>>Numérotation continue en chiffres arabes</option>
                            </select>
                        </label>
                        <input type="hidden" name="print_bleed_mm" value="0">
                        <p class="field-help">Fonds perdus gérés par le profil d’impression : 3 mm dans le réglage initial Corep, aucun choix manuel. Pour Bookelis, le générateur ne certifie pas les fonds perdus : leurs consignes exigent 5 mm sur l’intérieur si des images atteignent le bord, et un gabarit distinct pour la couverture. Un livre uniquement textuel n’a normalement pas besoin de fonds perdus.</p>
                        <label>Marge intérieure / reliure
                            <select id="print-gutter-mode" name="print_gutter_mode">
                                <option value="auto"<?= ($_POST['print_gutter_mode'] ?? 'auto') === 'auto' ? ' selected' : '' ?>>Automatique selon le nombre de pages</option>
                                <option value="custom"<?= ($_POST['print_gutter_mode'] ?? '') === 'custom' ? ' selected' : '' ?>>Personnalisée</option>
                            </select>
                        </label>
                        <label id="print-gutter-custom"<?= ($_POST['print_gutter_mode'] ?? 'auto') === 'custom' ? '' : ' hidden' ?>>Marge intérieure personnalisée (mm)
                            <input id="print-gutter-mm" name="print_gutter_mm" inputmode="decimal" value="<?= old('print_gutter_mm') ?>" placeholder="ex. 18">
                            <span class="field-help">Entre 5 et 40 mm.</span>
                        </label>
                    </div>

                    <label class="check-row"><input type="checkbox" name="print_toc_enabled" value="1"<?= checked('print_toc_enabled', true) ?>> <span><strong>Sommaire automatique</strong> — construit depuis les chapitres et recalculé après pagination.</span></label>
                    <label class="check-row"><input type="checkbox" name="print_hide_chapter_openers" value="1"<?= checked('print_hide_chapter_openers', true) ?>> <span>Masquer le numéro visible sur la première page de chaque chapitre.</span></label>
                    <p class="note"><strong>Principe :</strong> le manuscrit DOCX, ODT ou EPUB est recomposé en pages ; les numéros du sommaire sont recalculés après pagination, et non repris du fichier source. Les sauts recto/verso sont pris en charge par la composition.</p>
                    <div class="submit-row print-preview-row">
                        <button type="submit" class="secondary-action" formaction="/print-preview.php" formtarget="_blank" formnovalidate id="paper-generate-button">Générer et prévisualiser le PDF intérieur</button>
                        <span>Le générateur lit le DOCX, l’ODT ou l’EPUB sélectionné ci-dessus, applique vos réglages Corep ou Bookelis et ouvre un aperçu paginé. Utilisez « Enregistrer en PDF » depuis la boîte d'impression. Vérifiez le résultat avant l'envoi à l’imprimeur.</span>
                    </div>
                </div>

                <h3>Votre PDF intérieur, une fois la mise en page terminée</h3>
                <p class="field-help">Après la génération, enregistrez l'aperçu en PDF et joignez-le ici pour une éventuelle soumission. La génération n'envoie rien automatiquement.</p>
                <div class="form-grid two-columns">
                    <label>PDF déjà prêt à imprimer <span class="optional">facultatif</span>
                        <input type="file" name="print_pdf" accept="application/pdf,.pdf">
                        <span class="field-help">Si vous avez déjà un PDF final conforme aux exigences de votre imprimeur, vous pouvez le joindre directement.</span>
                    </label>
                    <label>Prix papier envisagé (€) <span class="optional">facultatif</span>
                        <input name="paper_price" inputmode="decimal" value="<?= old('paper_price') ?>" placeholder="14,90">
                    </label>
                </div>



                <div class="resource-card important-resource paper-step-card paper-pdfx-step" id="paper-pdfx-step">
                    <div>
                        <p class="paper-step-eyebrow">Étape 2 · facultative</p>
                        <h3>Convertir votre PDF au format PDF/X</h3>
                        <p>Après avoir enregistré le PDF intérieur, vous pouvez le convertir en PDF/X-4 ou PDF/X-1a <strong>si votre imprimeur le demande</strong>. L'atelier peut nécessiter une configuration supplémentaire : votre PDF standard reste utilisable si l'imprimeur l'accepte.</p>
                        <p class="field-help">Les couvertures pourront être converties de la même manière, une fois leurs PDF créés. Le PDF/X obtenu doit toujours être contrôlé avant impression.</p>
                    </div>
                    <div class="paper-step-actions">
                        <a class="button-link secondary" href="/export-pdfx.php">Ouvrir l'export PDF/X <span aria-hidden="true">↗</span></a>
                    </div>
                </div>

                <div data-printer-only="corep_metz" class="paper-provider-specific">
                <div class="resource-card important-resource paper-step-card paper-covers-step" id="paper-covers-step" aria-labelledby="paper-covers-title">
                    <div>
                        <h3 id="paper-covers-title">3. Préparer la première et la quatrième de couverture</h3>
                        <p>Un assistant dédié permet de composer les deux faces, de les prévisualiser et de les enregistrer en PDF séparés. Renseignez le titre, l’auteur, le résumé, l’ISBN et ajoutez éventuellement vos images. <strong>La tranche n’est pas dessinée au hasard :</strong> l’imprimeur la dimensionnera après réception de l’intérieur.</p>
                        <p><strong>Consignes confirmées par Rachel (Corep Metz) :</strong> première et quatrième de couverture en deux fichiers séparés, <strong>sans traits de coupe ni fonds perdus</strong>. Si elles figurent déjà dans le PDF intérieur, Corep peut également les en extraire ; les fichiers séparés restent plus simples à vérifier. L’intérieur est transmis dans l’ordre de lecture, sans imposition. Le BAT papier, s’il est demandé, est facturé séparément.</p>
                    </div>
                    <div class="paper-step-actions"><a class="button-link secondary" href="/assistant-papier.php">Ouvrir l’assistant de couvertures <span aria-hidden="true">↗</span></a></div>
                </div>
                </div>
                <div data-printer-only="bookelis" class="paper-provider-specific resource-card paper-step-card" id="paper-bookelis-cover-step" hidden>
                    <div><h3>4. Préparer une couverture Bookelis complète</h3>
                        <p>Bookelis demande un fichier <strong>à plat : quatrième + tranche + première</strong>. Le nouveau calculateur ci-dessous peut assembler une couverture à partir du format, du papier, du nombre de pages et des illustrations de l’auteur.</p>
                        <p>Après votre aperçu PDF, relevez le <strong>nombre réel de pages</strong> et obtenez la cote du dos auprès de Bookelis, ou l’épaisseur réelle d’une feuille. Le calculateur crée un PDF de travail. Comparez-le ensuite au <strong>gabarit officiel</strong> avant impression.</p>
                        <a class="button-link secondary" href="https://livres.bookelis.com/generation-couverture" target="_blank" rel="noopener noreferrer">Obtenir le gabarit officiel Bookelis ↗</a>
                        <?php require dirname(__DIR__) . '/src/views/cover-spread-editor.php'; ?>
                    </div>
                </div>
                <div class="form-grid two-columns" data-printer-only="corep_metz">
                    <label>Première de couverture papier <span class="optional">facultatif</span>
                        <input type="file" name="paper_cover_front" accept=".pdf,application/pdf,.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <span class="field-help">PDF individuel exporté depuis l’assistant ou image déjà prête. Distinct de la couverture de l’ebook.</span>
                    </label>
                    <label>Quatrième de couverture papier <span class="optional">facultatif</span>
                        <input type="file" name="paper_cover_back" accept=".pdf,application/pdf,.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <span class="field-help">PDF individuel ou image. Ne créez pas de tranche : Corep s’occupe de son épaisseur et de l’imposition.</span>
                    </label>
                </div>
                <p class="field-help" data-printer-only="corep_metz"><strong>Lisibilité et pagination Corep :</strong> en A5, prévoir des caractères de <strong>10 pt de préférence</strong> (9 pt au minimum pour les éléments secondaires). Le générateur utilise 10,5 pt pour le corps du texte et au moins 9 pt pour les éléments annexes. <strong>Limite communiquée : 680 pages en recto-verso sur papier 80 g (340 feuilles)</strong> ; le nombre de pages réel est contrôlé après pagination. Au-delà ou avec un papier particulier, demandez conseil à l’agence. Les couvertures séparées ne nécessitent pas de fonds perdus ni de traits de coupe.</p>

                <section class="print-cost-panel" id="print-cost-estimator" aria-labelledby="print-cost-title" data-grids="<?= e((string) json_encode($publicPrintGrids, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP)) ?>">
                    <h3 id="print-cost-title">5. Comparer les coûts d'impression</h3>
                    <p>Pour Corep, la grille concerne uniquement les tirages de <strong>120, 200 ou 300 pages</strong> et les quantités exactes affichées, en A5 broché N&B 80 g. Le nombre de pages est relevé pendant le pré-calcul ; en dehors des paliers autorisés, un devis est nécessaire. Nous avons reçu de <strong>Corep Metz</strong> les caractéristiques techniques et l’autorisation de présenter une grille TTC indicative. Si elle est disponible et encore publiable, ses montants sont affichés ci-dessous ; le devis réel reste à confirmer par l’imprimeur.</p>
                    <div class="print-cost-controls">
                        <label>Nombre de pages
                            <input id="print-estimate-pages" type="number" min="1" max="5000" step="1" value="" placeholder="Calcul après import" inputmode="numeric">
                            <span class="field-help" id="print-pages-feedback">Nombre de pages calculé avant l’export final, puis à contrôler dans le PDF imprimable.</span>
                        </label>
                        <label>Tirage
                            <select id="print-estimate-quantity">
                                <option value="10">10 exemplaires</option><option value="30" selected>30 exemplaires</option><option value="50">50 exemplaires</option><option value="100">100 exemplaires</option>
                            </select>
                        </label>
                    </div>
                    <p id="print-estimate-status" class="print-cost-status" role="status" aria-live="polite">Recherche des grilles disponibles…</p>
                    <dl class="print-cost-results">
                        <div><dt>Corep Metz — prix total TTC indicatif</dt><dd id="print-estimate-total">—</dd></div>
                        <div><dt>Corep Metz — coût unitaire indicatif</dt><dd id="print-estimate-unit">—</dd></div>
                        <div style="grid-column: 1 / -1"><dt>Solde indicatif par exemplaire avec une hypothèse de remise libraire de 30 %</dt><dd id="print-estimate-balance">—</dd></div>
                    </dl>
                    <p class="print-cost-note" id="print-estimate-source">Tarif indicatif à confirmer avant commande.</p>
                    <div class="bookelis-quote-note">
                        <strong>Et chez Bookelis ?</strong> Le calculateur officiel donne un devis à partir du nombre de pages, du format, du tirage, du papier, des couleurs et de la couverture. Nous ne pouvons pas en récupérer le tarif en direct : <a href="https://livres.bookelis.com/imprimer" target="_blank" rel="noopener noreferrer">calculer le tarif officiel ↗</a>.
                        <span class="field-help">Pour comparaison uniquement : Bookelis cite un exemple public de livre de 200 pages en petit format, N&B et couverture souple à 7,80 € l'unité. Ce n'est ni un tarif universel ni une estimation pour votre configuration.</span>
                    </div>
                    <p class="print-cost-note"><strong>Corep Metz :</strong> un premier accord de publication des tarifs indicatifs et des échanges techniques, <strong>pas un partenariat commercial ou une commande intégrée</strong>. Si aucune grille n’est actuellement affichable, vous pouvez <a href="https://www.corep.fr/fr/agence/corep-metz/" target="_blank" rel="noopener noreferrer">demander un devis directement à l’agence</a>.</p>
                    <p class="print-cost-note">Le BAT papier est hors grille Corep : une épreuve papier est facturée à l’unité. Le BAT numérique est possible et la tranche est calculée par l’imprimeur.</p>
                    <p class="print-cost-note">Le solde n’est pas un bénéfice : le prix public est fixé par l’autoéditeur, et les frais de livraison, la fiscalité, la rémunération de l’auteur, les invendus et les frais de plateforme ne sont pas encore déduits. Aucune commande n’est déclenchée ici.</p>
                </section>

                <div class="resource-card important-resource paper-step-card paper-dossier-step" id="paper-dossier-step" data-printer-only="corep_metz">
                    <div>
                        <p class="paper-step-eyebrow">Dernière étape</p>
                        <h3>5. Constituer le dossier pour l'imprimeur</h3>
                        <p>Une fois votre PDF intérieur et vos deux couvertures prêts, contrôlez les trois fichiers et téléchargez votre dossier ZIP. Aucun document n'est transmis automatiquement à Corep ou à un autre prestataire.</p>
                    </div>
                    <div class="paper-step-actions">
                        <a class="button-link secondary" href="/dossier-impression.php">Préparer le dossier d'impression <span aria-hidden="true">↗</span></a>
                    </div>
                </div>



                <div class="resource-card important-resource paper-step-card paper-corep-step" id="paper-corep-step" data-printer-only="corep_metz">
                    <div>
                        <p class="paper-step-eyebrow">Après avoir préparé vos fichiers</p>
                        <h3>6. Transmettre vos PDF à Corep Metz pour un devis</h3>
                        <p>Vous avez votre PDF intérieur et vos deux couvertures ? Corep permet de <strong>joindre vos fichiers à une demande de devis</strong> sur son site officiel. Sélectionnez <strong>l’agence de Metz</strong>, indiquez le format et le tirage souhaités, puis ajoutez les <strong>trois PDF séparément</strong>.</p>
                        <p class="field-help">Le formulaire s’ouvre chez Corep : <strong>aucun fichier n’est envoyé depuis la Librairie</strong>. Le devis, la conformité des PDF et le BAT sont à confirmer avec l’imprimeur. Ce lien ne constitue pas un partenariat commercial ni une commande intégrée.</p>
                    </div>
                    <div class="paper-step-actions">
                        <a class="button-link" href="https://www.corep.fr/fr/devis-sur-mesure/" target="_blank" rel="noopener noreferrer">Déposer mes PDF pour un devis chez Corep <span aria-hidden="true">↗</span></a>
                        <a class="button-link secondary" href="mailto:metz@corep.fr?subject=Demande%20de%20devis%20-%20impression%20de%20livre">Écrire à Corep Metz</a>
                    </div>
                </div>

                <div class="resource-card paper-step-card" data-printer-only="bookelis" hidden>
                    <div><h3>6–7. Contrôler et transmettre les fichiers à Bookelis</h3>
                        <p>Conservez le <strong>PDF intérieur</strong> au format choisi, puis le <strong>PDF de couverture à plat</strong> élaboré avec le gabarit Bookelis. Contrôlez le nombre de pages, les marges, les polices, les images, les fonds perdus et les pages blanches.</p>
                        <p>Bookelis calcule son prix selon le nombre de pages <em>et</em> les caractéristiques de fabrication. Saisissez les mêmes choix dans son calculateur, puis déposez-y les PDF. <strong>Pas de transfert automatique, de prix garanti ni de partenariat technique.</strong></p>
                        <div class="paper-step-actions">
                            <a class="button-link" href="https://livres.bookelis.com/imprimer" target="_blank" rel="noopener noreferrer">Calculer le prix et commander chez Bookelis ↗</a>
                            <a class="button-link secondary" href="https://www.bookelis.com/auto-edition/gabarits-livre/" target="_blank" rel="noopener noreferrer">Consulter les gabarits intérieurs ↗</a>
                        </div>
                    </div>
                </div>
                <div class="resource-section" aria-labelledby="printers-title">
                    <h3 id="printers-title">À qui confier l’impression de son livre ?</h3>
                    <p>Ressources pratiques, <strong>sans partenariat commercial ni classement sponsorisé</strong>. Les ateliers locaux et les plateformes nationales d’autoédition remplissent des fonctions différentes.</p>
                    <h4>Imprimerie locale à Metz</h4>
                    <div class="resource-grid">
                        <a class="resource-card" href="https://www.corep.fr/fr/agence/corep-metz/" target="_blank" rel="noopener noreferrer"><strong>Corep Metz</strong><span>Atelier local, retrait possible. Conseils techniques reçus ; devis et BAT à confirmer directement ↗</span></a>
                    </div>
                    <p class="field-help">Un autre imprimeur local pourra être ajouté après confirmation de ses conditions techniques et commerciales.</p>
                    <h4>Plateformes d’impression et d’autoédition</h4>
                    <div class="resource-grid">
                        <a class="resource-card" href="https://www.bookelis.com/imprimer-un-livre/" target="_blank" rel="noopener noreferrer"><strong>Bookelis</strong><span>Autoédition, impression à la demande, plusieurs formats et services de diffusion ↗</span></a>
                        <a class="resource-card" href="https://www.coollibri.com/auto-edition-livre" target="_blank" rel="noopener noreferrer"><strong>CoolLibri</strong><span>Impression de livres en petites quantités, avec commande en ligne ↗</span></a>
                        <a class="resource-card" href="https://www.bod.fr/livre/publier-un-livre" target="_blank" rel="noopener noreferrer"><strong>BoD — Books on Demand</strong><span>Impression à la demande et services de diffusion ↗</span></a>
                    </div>
                </div>
            </fieldset>

            </section>
        </form>
        <section class="paper-draft-toolbar" aria-labelledby="draft-heading">
            <div><h3 id="draft-heading">Votre brouillon, sans compte</h3>
            <p>Enregistrez les champs sur cet appareil ou exportez-les en fichier JSON. Les manuscrits, couvertures et PDF <strong>ne sont jamais sauvegardés</strong> par cette fonction : vous devrez les sélectionner à nouveau. Évitez la sauvegarde locale sur un ordinateur partagé.</p></div>
            <div class="paper-draft-actions">
                <button type="button" id="draft-save">Enregistrer ici</button>
                <button type="button" id="draft-restore">Reprendre</button>
                <button type="button" id="draft-export">Exporter</button>
                <input class="draft-file-input" type="file" id="draft-import" accept=".json,application/json" aria-describedby="draft-import-name">
                <label class="draft-file-label" for="draft-import">Importer un brouillon (.json)</label>
                <span id="draft-import-name" class="draft-import-name" role="status" aria-live="polite">Aucun fichier sélectionné</span>
                <button type="button" id="draft-clear">Effacer le brouillon</button>
            </div>
            <p id="draft-status" role="status" aria-live="polite">Aucune donnée envoyée au serveur lors de la sauvegarde d'un brouillon.</p>
        </section>
    <?php endif; ?>
</main>

<footer>
    <div class="footer-links"><a href="/ebooks.php">Ebooks</a><a href="/feedback.php?kind=self_publishing">Donner un retour</a><a href="/projet.php">Le projet</a></div>
    <p>Préparez vos livres, découvrez des auteurs indépendants et trouvez des imprimeurs.</p>
</footer>
<script src="/assets/js/autoedition-print.js"></script>
<script src="/assets/js/autoedition-tabs.js?v=20261018-2"></script>
<script src="/assets/js/author-draft.js?v=20261018-3"></script>
<script src="/assets/js/paper-barcode.js?v=20261018-1"></script>
<script src="/assets/js/printer-kit.js?v=20261018-1"></script>
<script src="/assets/js/printer-zip.js?v=20261018-1"></script>
<script src="/assets/js/paper-wizard.js?v=20261110-3"></script>
<script src="/assets/js/cover-spread.js?v=20261110-1"></script>
<script src="/assets/js/printing-estimate.js?v=20261102-1"></script>
</body>
</html>
