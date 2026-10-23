<?php

declare(strict_types=1);

[$config, $pdo] = require dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/UploadLimits.php';
$service = new SelfPublishingService($pdo, $config);
$errors = [];
$successId = null;
$sourceTool = isset($_GET['source']) ? trim((string) $_GET['source']) : '';
$fromAtelier = $sourceTool === 'atelier-epub';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (UploadLimits::postExceeded((int) ($_SERVER['CONTENT_LENGTH'] ?? 0))) {
        $errors[] = 'Fichiers trop volumineux pour cet envoi. Réduisez la taille totale ou transmettez les fichiers séparément.';
    } elseif (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La session du formulaire a expiré. Rechargez la page puis réessayez.';
    } else {
        try {
            $successId = $service->submit($_POST, $_FILES);
        } catch (Throwable $error) {
            $errors[] = $error->getMessage();
        }
    }
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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <meta name="description" content="Déposer un livre indépendant pour validation humaine : EPUB, couverture, ISBN et version papier, sans publication automatique.">
    <title>Autoédition — <?= e($config['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css?v=20261009-2">
    <link rel="stylesheet" href="/assets/css/printing-estimate.css?v=20261013-1">
    <link rel="stylesheet" href="/assets/css/autoedition-tabs.css?v=20261018-2">
    <link rel="stylesheet" href="/assets/css/author-draft.css?v=20261018-3">
    <link rel="stylesheet" href="/assets/css/autoedition-paper.css?v=20261019-1">
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<?php $currentNav = 'autoedition'; require dirname(__DIR__) . '/src/views/site-header.php'; ?>

<main id="main">
    <section class="page-hero compact">
        <p class="eyebrow">Auteurs et autrices indépendants</p>
        <h1>Publier sans être noyé dans une décharge d’autoédition.</h1>
        <p class="lede">Le dépôt ne déclenche jamais une publication automatique. Les fichiers et métadonnées sont contrôlés, puis une personne valide ou demande des corrections avant l’entrée dans le catalogue.</p>
        <?php if ((string) $config['atelier_epub_url'] !== ''): ?>
            <p><a class="button-link secondary" href="<?= e((string) $config['atelier_epub_url']) ?>" rel="noopener noreferrer">Créer ou corriger mon EPUB dans Atelier EPUB ↗</a></p>
        <?php endif; ?>
        <?php if ($fromAtelier): ?>
            <div class="resource-card important-resource">
                <div>
                    <strong>Vous arrivez d’Atelier EPUB.</strong>
                    <p>Un EPUB techniquement valide n’est pas une promesse de publication. Chaque dossier est relu par une personne. La librairie assume une sélection éditoriale et peut refuser notamment les dépôts industriels ou automatisés, les variantes répétitives sans valeur, les contenus trompeurs ou illégaux, les ouvrages manifestement bâclés et, plus largement, ce qui ne présente pas de véritable travail éditorial.</p>
                    <p><strong>Cette décision n’est pas déléguée à une IA.</strong></p>
                </div>
            </div>
        <?php endif; ?>
        <?php if (!$config['self_publishing_enabled']): ?>
            <p class="note"><strong>Pré-ouverture :</strong> le parcours est prêt mais les envois sont encore fermés sur cet environnement. Le formulaire reste visible pour préparer l’alpha.</p>
        <?php endif; ?>
    </section>

    <?php if ($successId !== null): ?>
        <section class="content-section">
            <div class="success-box" role="status">
                <h2>Dépôt reçu.</h2>
                <p>Référence interne : <strong>#<?= (int) $successId ?></strong>. Le livre n’est pas encore publié : il passe maintenant par la validation humaine. Une fois validé et publié, sa fiche apparaîtra dans le catalogue.</p>
                <p>Vous pouvez déjà préparer la suite, sans contacter les librairies avant d'avoir confirmé les informations de vente : <a href="/diffuser-mon-livre.php"><strong>préparer ma fiche de présentation et mon premier contact avec les librairies →</strong></a></p>
            </div>
        </section>
    <?php else: ?>
        <?php if ($errors !== []): ?>
            <div class="error-box" role="alert">
                <strong>Le dépôt n’a pas pu être enregistré.</strong>
                <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <section class="autoedition-entry" aria-labelledby="autoedition-entry-heading">
            <div class="autoedition-entry-main">
                <p class="autoedition-entry-eyebrow">Votre livre, à votre rythme</p>
                <h2 id="autoedition-entry-heading">Par où souhaitez-vous commencer ?</h2>
                <p>Un livre papier, un EPUB ou les deux : choisissez votre parcours. Vous pourrez changer d’onglet à tout moment.</p>
                <p><a class="text-link" href="/diffuser-mon-livre.php">Et après ? Découvrir le parcours pour faire connaître et vendre mon livre →</a></p>
                <p><a class="text-link" href="/prestations-auteurs.php">Besoin de faire vérifier vos PDF (79 € HT) ou de corriger votre texte (dès 179 € HT) ? Découvrir nos prestations facultatives →</a></p>
            </div>
            <div class="autoedition-entry-promise">
                <span aria-hidden="true" class="autoedition-entry-promise-icon">✓</span>
                <span><strong>Mise en page papier incluse</strong><small>Importez un DOCX ou ODT. Notre générateur compose les pages avant votre export PDF.</small></span>
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
            <button type="button" class="autoedition-tab" id="tab-submit" role="tab" aria-controls="panel-submit" aria-selected="false" tabindex="-1" data-panel="submit">
                <span class="autoedition-tab-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3h8l3 3v15H5V6l3-3zm0 10 3 3 5-5"/></svg></span>
                <span class="autoedition-tab-copy"><strong>Déposer</strong><small>Vérifier et envoyer</small></span>
            </button>
        </nav>
        <p class="autoedition-tab-context" id="autoedition-tab-context" role="status" aria-live="polite">Livre papier · Composez votre manuscrit et préparez son impression.</p>
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
        <form class="submission-form" method="post" enctype="multipart/form-data" id="autoedition-form">
            <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
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
                    <a href="#paper-interior-step"><span>1</span> Intérieur PDF</a>
                    <a href="#paper-pdfx-step"><span>2</span> PDF/X <small>facultatif</small></a>
                    <a href="#paper-covers-step"><span>3</span> Couvertures</a>
                    <a href="#print-cost-estimator"><span>4</span> Coût estimé</a>
                    <a href="#paper-dossier-step"><span>5</span> Dossier imprimeur</a>
                    <a href="#paper-corep-step"><span>6</span> Envoyer à l’imprimeur</a>
                </nav>

                <div class="resource-card important-resource">
                    <div>
                        <strong>Notre générateur réalise la mise en page de votre manuscrit.</strong>
                        <p><strong>Ajoutez votre livre en DOCX ou ODT</strong> : la Librairie recomposera l'intérieur en pages de livre, avec sommaire, marges de reliure, pagination recto-verso et pages blanches nécessaires. Vous pourrez ensuite enregistrer le résultat en PDF pour l'imprimeur. Aucun EPUB n'est nécessaire.</p>
                        <p>Le manuscrit source reste inchangé. La mise en page est toujours réalisée au format A5 (148 × 210 mm), même si le DOCX/ODT était en A4. Ce n'est pas une simple réduction de page. La conversion n'est pas une reproduction exacte du document bureautique : relisez l'aperçu, en particulier les tableaux, les notes, les images et la taille des caractères, puis faites valider le PDF final par l'imprimeur.</p>
                    </div>
                </div>

                <div class="resource-card important-resource" id="paper-interior-step">
                    <div>
                        <strong>1. Importer votre manuscrit pour composer l'intérieur PDF</strong>
                        <p>Formats actuellement convertibles : <strong>.docx, .odt</strong> (Word ou LibreOffice). Pour un ancien fichier <code>.doc</code>, enregistrez-le d'abord en DOCX ou ODT. Les fichiers TXT ne sont pas pris en charge.</p>
                        <label>Manuscrit papier à mettre en page
                            <input type="file" name="office_manuscript" accept=".docx,.odt,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.oasis.opendocument.text">
                            <span class="field-help">Fichier lu temporairement pour l'aperçu, jamais envoyé à l'imprimeur automatiquement. Maximum affiché selon votre serveur : <?= e(UploadLimits::officeLabel($config)) ?>. Si la limite du serveur est inférieure à 30 Mo, elle prévaut.</span>
                        </label>
                    </div>
                </div>

                <label class="check-row">
                    <input id="print-preparation-requested" type="checkbox" name="print_preparation_requested" value="1" aria-controls="print-layout-settings" aria-expanded="<?= $printPreparationRequested ? 'true' : 'false' ?>"<?= checked('print_preparation_requested', true) ?>>
                    <span><strong>Je veux préparer la mise en page de mon livre papier.</strong><br><span class="field-help">L’assistant travaille uniquement sur la version papier : l’EPUB d’origine n’est ni réécrit ni corrigé ici. Vous pouvez ensuite imprimer l’aperçu au format PDF.</span></span>
                </label>

                <div id="print-layout-settings"<?= $printPreparationRequested ? '' : ' hidden' ?>>
                    <div class="form-grid two-columns">
                        <label>Format du livre — A5 obligatoire
                            <select name="print_trim_size" aria-describedby="paper-a5-help">
                                <option value="a5" selected>A5 — 14,8 × 21 cm (Corep Metz)</option>
                            </select>
                            <span class="field-help" id="paper-a5-help">Le générateur compose directement en A5, même si votre DOCX ou ODT a été préparé en A4. Il ne réduit pas un PDF A4 : la pagination et les marges sont recalculées. Le nombre de pages peut augmenter : vérifiez la lisibilité et l’aperçu avant impression.</span>
                        </label>
                        <label>Reliure
                            <select name="print_binding">
                                <option value="paperback"<?= ($_POST['print_binding'] ?? 'paperback') === 'paperback' ? ' selected' : '' ?>>Broché</option>
                                <option value="hardcover"<?= ($_POST['print_binding'] ?? '') === 'hardcover' ? ' selected' : '' ?>>Relié</option>
                            </select>
                        </label>
                        <label>Début des chapitres
                            <select name="print_chapter_start">
                                <option value="right"<?= ($_POST['print_chapter_start'] ?? 'right') === 'right' ? ' selected' : '' ?>>Toujours sur une page de droite</option>
                                <option value="next"<?= ($_POST['print_chapter_start'] ?? '') === 'next' ? ' selected' : '' ?>>À la page suivante, gauche ou droite</option>
                            </select>
                        </label>
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
                        <label>Fond perdu
                            <select name="print_bleed_mm">
                                <option value="0"<?= (string) ($_POST['print_bleed_mm'] ?? '0') === '0' ? ' selected' : '' ?>>0 mm — livre sans éléments jusqu’au bord</option>
                                <option value="3"<?= (string) ($_POST['print_bleed_mm'] ?? '') === '3' ? ' selected' : '' ?>>3 mm</option>
                            </select>
                        </label>
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
                    <p class="note"><strong>Principe :</strong> le manuscrit DOCX/ODT est recomposé en pages ; les numéros du sommaire sont calculés après pagination, et non copiés depuis Word. Les sauts recto/verso sont pris en charge par la composition.</p>
                    <div class="submit-row print-preview-row">
                        <button type="submit" class="secondary-action" formaction="/print-preview.php" formtarget="_blank" formnovalidate id="paper-generate-button">Générer et prévisualiser le PDF intérieur</button>
                        <span>Le générateur lit le DOCX ou l'ODT choisi ci-dessus, applique vos réglages et ouvre un aperçu paginé. Vous pourrez utiliser « Enregistrer en PDF » depuis la boîte d'impression. Vérifiez le résultat avant l'envoi à Corep.</span>
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

                <div class="resource-card important-resource paper-step-card paper-covers-step" id="paper-covers-step" aria-labelledby="paper-covers-title">
                    <div>
                        <h3 id="paper-covers-title">3. Préparer la première et la quatrième de couverture</h3>
                        <p>Un assistant dédié permet de composer les deux faces, de les prévisualiser et de les enregistrer en PDF séparés. Renseignez le titre, l’auteur, le résumé, l’ISBN et ajoutez éventuellement vos images. <strong>La tranche n’est pas dessinée au hasard :</strong> l’imprimeur la dimensionnera après réception de l’intérieur.</p>
                        <p>Corep Metz accepte les faces séparées. L’intérieur est transmis en PDF simple dans l’ordre de lecture, sans imposition. Le BAT papier, s’il est demandé, est facturé séparément.</p>
                    </div>
                    <div class="paper-step-actions"><a class="button-link secondary" href="/assistant-papier.php">Ouvrir l’assistant de couvertures <span aria-hidden="true">↗</span></a></div>
                </div>
                <div class="form-grid two-columns">
                    <label>Première de couverture papier <span class="optional">facultatif</span>
                        <input type="file" name="paper_cover_front" accept=".pdf,application/pdf,.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <span class="field-help">PDF individuel exporté depuis l’assistant ou image déjà prête. Distinct de la couverture de l’ebook.</span>
                    </label>
                    <label>Quatrième de couverture papier <span class="optional">facultatif</span>
                        <input type="file" name="paper_cover_back" accept=".pdf,application/pdf,.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <span class="field-help">PDF individuel ou image. Ne créez pas de tranche : Corep s’occupe de son épaisseur et de l’imposition.</span>
                    </label>
                </div>
                <p class="field-help"><strong>Conseil Corep :</strong> papier intérieur 80 g certifié FSC/PEFC ; 90 g possible sur devis spécifique. L’imprimeur valide les fichiers, la tranche, les éventuels fonds perdus et les choix particuliers avant le BAT.</p>

                <section class="print-cost-panel" id="print-cost-estimator" aria-labelledby="print-cost-title" data-grids="<?= e((string) json_encode($publicPrintGrids, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP)) ?>">
                    <h3 id="print-cost-title">4. Estimer le coût d’impression</h3>
                    <p>Estimez un tirage A5 broché, avec intérieur noir et blanc de 80 g et couverture couleur. Nous avons reçu de <strong>Corep Metz</strong> les caractéristiques techniques et l’autorisation de présenter une grille TTC indicative. Si elle est disponible et encore publiable, ses montants sont affichés ci-dessous ; le devis réel reste à confirmer par l’imprimeur.</p>
                    <div class="print-cost-controls">
                        <label>Nombre de pages
                            <select id="print-estimate-pages">
                                <option value="120">120 pages</option><option value="200" selected>200 pages</option><option value="300">300 pages</option>
                            </select>
                        </label>
                        <label>Tirage
                            <select id="print-estimate-quantity">
                                <option value="10">10 exemplaires</option><option value="30" selected>30 exemplaires</option><option value="50">50 exemplaires</option><option value="100">100 exemplaires</option>
                            </select>
                        </label>
                    </div>
                    <p id="print-estimate-status" class="print-cost-status" role="status" aria-live="polite">Recherche des grilles disponibles…</p>
                    <dl class="print-cost-results">
                        <div><dt>Prix total TTC</dt><dd id="print-estimate-total">—</dd></div>
                        <div><dt>Coût d’impression par exemplaire</dt><dd id="print-estimate-unit">—</dd></div>
                        <div style="grid-column: 1 / -1"><dt>Solde indicatif par exemplaire avec une hypothèse de remise libraire de 30 %</dt><dd id="print-estimate-balance">—</dd></div>
                    </dl>
                    <p class="print-cost-note" id="print-estimate-source">Tarif indicatif à confirmer avant commande.</p>
                    <p class="print-cost-note"><strong>Corep Metz :</strong> un premier accord de publication des tarifs indicatifs et des échanges techniques, <strong>pas un partenariat commercial ou une commande intégrée</strong>. Si aucune grille n’est actuellement affichable, vous pouvez <a href="https://www.corep.fr/fr/agence/corep-metz/" target="_blank" rel="noopener noreferrer">demander un devis directement à l’agence</a>.</p>
                    <p class="print-cost-note">Le BAT papier est hors grille Corep : une épreuve papier est facturée à l’unité. Le BAT numérique est possible et la tranche est calculée par l’imprimeur.</p>
                    <p class="print-cost-note">Le solde n’est pas un bénéfice : le prix public est fixé par l’autoéditeur, et les frais de livraison, la fiscalité, la rémunération de l’auteur, les invendus et les frais de plateforme ne sont pas encore déduits. Aucune commande n’est déclenchée ici.</p>
                </section>

                <div class="resource-card important-resource paper-step-card paper-dossier-step" id="paper-dossier-step">
                    <div>
                        <p class="paper-step-eyebrow">Dernière étape</p>
                        <h3>5. Constituer le dossier pour l'imprimeur</h3>
                        <p>Une fois votre PDF intérieur et vos deux couvertures prêts, contrôlez les trois fichiers et téléchargez votre dossier ZIP. Aucun document n'est transmis automatiquement à Corep ou à un autre prestataire.</p>
                    </div>
                    <div class="paper-step-actions">
                        <a class="button-link secondary" href="/dossier-impression.php">Préparer le dossier d'impression <span aria-hidden="true">↗</span></a>
                    </div>
                </div>



                <div class="resource-card important-resource paper-step-card paper-corep-step" id="paper-corep-step">
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

                <div class="resource-section" aria-labelledby="printers-title">
                    <h3 id="printers-title">Besoin d’un imprimeur ou d’impression à la demande ?</h3>
                    <p>Ces liens sont fournis comme ressources pratiques, <strong>sans partenariat ni classement sponsorisé</strong>. Comparez les coûts, formats, conditions de distribution, propriété des ISBN et exemplaires tests avant de choisir.</p>
                    <div class="resource-grid">
                        <a class="resource-card" href="https://www.corep.fr/fr/agence/corep-metz/" target="_blank" rel="noopener noreferrer"><strong>Corep Metz — référence locale</strong><span>Conseils techniques reçus et autorisation de publier une grille A5 indicative. Devis et BAT à confirmer directement ↗</span></a>
                        <a class="resource-card" href="https://www.bookelis.com/imprimer-un-livre/" target="_blank" rel="noopener noreferrer"><strong>Bookelis</strong><span>Impression à la demande et petites quantités ↗</span></a>
                        <a class="resource-card" href="https://www.coollibri.com/auto-edition-livre" target="_blank" rel="noopener noreferrer"><strong>CoolLibri</strong><span>Impression en France, dès de petites quantités ↗</span></a>
                        <a class="resource-card" href="https://www.bod.fr/livre/publier-un-livre" target="_blank" rel="noopener noreferrer"><strong>BoD — Books on Demand</strong><span>Impression à la demande et services de diffusion ↗</span></a>
                    </div>
                </div>
            </fieldset>

            </section>
            <section class="autoedition-panel" id="panel-submit" role="tabpanel" aria-labelledby="tab-submit" data-panel-name="submit">
            <fieldset>
                <legend>Vérifications et envoi</legend>
                <label class="check-row"><input type="checkbox" name="rights_confirmed" value="1" required<?= !empty($_POST['rights_confirmed']) ? ' checked' : '' ?>> <span>Je confirme disposer des droits nécessaires sur le texte, la couverture et les fichiers transmis, ou des autorisations correspondantes.</span></label>
                <label class="check-row"><input type="checkbox" name="quality_confirmed" value="1" required<?= !empty($_POST['quality_confirmed']) ? ' checked' : '' ?>> <span>Je confirme qu’il s’agit d’un véritable projet éditorial et non d’un dépôt industriel ou automatisé, d’une série de variantes répétitives sans valeur, d’un contenu trompeur ou manifestement bâclé destiné à remplir artificiellement le catalogue.</span></label>
                <p class="field-help"><strong>La publication reste une décision humaine.</strong> Un fichier techniquement valide peut être refusé pour des raisons éditoriales. Aucun score d’IA ne décide de l’acceptation.</p>
                <p class="field-help"><strong>Un dernier regard avant publication ?</strong> <a href="/prestations-auteurs.php">Voir la vérification humaine des fichiers et la correction accompagnée, sur devis</a>. Le générateur reste gratuit.</p>
                <p class="field-help"><strong>Et pour vendre ?</strong> Une fois le dépôt accepté et publié, le livre entre dans le catalogue. Vous pouvez dès maintenant <a href="/diffuser-mon-livre.php">préparer une fiche pour les librairies et un message de présentation local</a>. La vente et la réservation en ligne ne sont pas encore automatisées.</p>
            </fieldset>

            <div class="submit-row">
                <button type="submit"<?= !$config['self_publishing_enabled'] ? ' disabled' : '' ?>>Envoyer pour validation humaine</button>
                <span>Aucune publication automatique.</span>
            </div>
            </section>
        </form>
    <?php endif; ?>
</main>

<footer>
    <div class="footer-links"><a href="/ebooks.php">Ebooks</a><a href="/feedback.php?kind=self_publishing">Donner un retour</a><a href="/projet.php">Le projet</a></div>
    <p>Autoédition accompagnée et filtrée humainement — sans classement automatique opaque.</p>
</footer>
<script src="/assets/js/autoedition-print.js"></script>
<script src="/assets/js/autoedition-tabs.js?v=20261018-2"></script>
<script src="/assets/js/author-draft.js?v=20261018-3"></script>
<script src="/assets/js/printing-estimate.js?v=20261013-1"></script>
</body>
</html>
