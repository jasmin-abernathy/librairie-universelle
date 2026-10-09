<?php

declare(strict_types=1);

[$config, $pdo] = require dirname(__DIR__) . '/src/bootstrap.php';
$service = new SelfPublishingService($pdo, $config);
$errors = [];
$successId = null;
$sourceTool = isset($_GET['source']) ? trim((string) $_GET['source']) : '';
$fromAtelier = $sourceTool === 'atelier-epub';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
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

$printPreparationRequested = !empty($_POST['print_preparation_requested']);
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
                <p>Référence interne : <strong>#<?= (int) $successId ?></strong>. Le livre n’est pas encore publié : il passe maintenant par la validation humaine.</p>
            </div>
        </section>
    <?php else: ?>
        <?php if ($errors !== []): ?>
            <div class="error-box" role="alert">
                <strong>Le dépôt n’a pas pu être enregistré.</strong>
                <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form class="submission-form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">

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

            <fieldset>
                <legend>4. Version numérique</legend>
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

            <fieldset>
                <legend>5. Version papier et impression</legend>

                <div class="resource-card important-resource">
                    <div>
                        <strong>Composer la version papier ici.</strong>
                        <p>Choisissez l’EPUB à l’étape 4, puis utilisez l’aperçu ci-dessous. La Librairie extrait les chapitres, génère le sommaire et calcule la pagination recto/verso sans modifier votre EPUB.</p>
                    </div>
                </div>

                <label class="check-row">
                    <input id="print-preparation-requested" type="checkbox" name="print_preparation_requested" value="1" aria-controls="print-layout-settings" aria-expanded="<?= $printPreparationRequested ? 'true' : 'false' ?>"<?= checked('print_preparation_requested') ?>>
                    <span><strong>Je veux définir la mise en page de ma future version imprimée.</strong><br><span class="field-help">Ces choix deviennent le cahier de composition conservé avec votre soumission. Le formulaire ne fabrique pas encore le PDF : le rendu paginé sera assuré par Atelier EPUB.</span></span>
                </label>

                <div id="print-layout-settings"<?= $printPreparationRequested ? '' : ' hidden' ?>>
                    <div class="form-grid two-columns">
                        <label>Format du livre
                            <select name="print_trim_size">
                                <option value="140x210"<?= ($_POST['print_trim_size'] ?? '140x210') === '140x210' ? ' selected' : '' ?>>14 × 21 cm</option>
                                <option value="a5"<?= ($_POST['print_trim_size'] ?? '') === 'a5' ? ' selected' : '' ?>>A5 — 14,8 × 21 cm</option>
                                <option value="135x215"<?= ($_POST['print_trim_size'] ?? '') === '135x215' ? ' selected' : '' ?>>13,5 × 21,5 cm</option>
                                <option value="152x229"<?= ($_POST['print_trim_size'] ?? '') === '152x229' ? ' selected' : '' ?>>15,2 × 22,9 cm / 6 × 9 pouces</option>
                            </select>
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
                    <p class="note"><strong>Principe :</strong> les numéros du sommaire ne sont jamais devinés à partir de l’EPUB. Ils sont calculés après composition du PDF, quand le format, les marges, les images et les sauts de page sont définitifs.</p>
                    <div class="submit-row print-preview-row">
                        <button type="submit" class="secondary-action" formaction="/print-preview.php" formtarget="_blank" formnovalidate>Prévisualiser la version imprimée</button>
                        <span>Utilise l’EPUB sélectionné à l’étape 4. L’aperçu s’ouvre dans un nouvel onglet et ne publie rien.</span>
                    </div>
                </div>

                <div class="form-grid two-columns">
                    <label>PDF déjà prêt à imprimer <span class="optional">facultatif</span>
                        <input type="file" name="print_pdf" accept="application/pdf,.pdf">
                        <span class="field-help">Si vous avez déjà un PDF final conforme aux exigences de votre imprimeur, vous pouvez le joindre directement.</span>
                    </label>
                    <label>Prix papier envisagé (€) <span class="optional">facultatif</span>
                        <input name="paper_price" inputmode="decimal" value="<?= old('paper_price') ?>" placeholder="14,90">
                    </label>
                </div>

                <div class="resource-section" aria-labelledby="printers-title">
                    <h3 id="printers-title">Besoin d’un imprimeur ou d’impression à la demande ?</h3>
                    <p>Ces liens sont fournis comme ressources pratiques, <strong>sans partenariat ni classement sponsorisé</strong>. Comparez les coûts, formats, conditions de distribution, propriété des ISBN et exemplaires tests avant de choisir.</p>
                    <div class="resource-grid">
                        <a class="resource-card" href="https://www.bookelis.com/imprimer-un-livre/" target="_blank" rel="noopener noreferrer"><strong>Bookelis</strong><span>Impression à la demande et petites quantités ↗</span></a>
                        <a class="resource-card" href="https://www.coollibri.com/auto-edition-livre" target="_blank" rel="noopener noreferrer"><strong>CoolLibri</strong><span>Impression en France, dès de petites quantités ↗</span></a>
                        <a class="resource-card" href="https://www.bod.fr/livre/publier-un-livre" target="_blank" rel="noopener noreferrer"><strong>BoD — Books on Demand</strong><span>Impression à la demande et services de diffusion ↗</span></a>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>6. Déclarations avant envoi</legend>
                <label class="check-row"><input type="checkbox" name="rights_confirmed" value="1" required<?= !empty($_POST['rights_confirmed']) ? ' checked' : '' ?>> <span>Je confirme disposer des droits nécessaires sur le texte, la couverture et les fichiers transmis, ou des autorisations correspondantes.</span></label>
                <label class="check-row"><input type="checkbox" name="quality_confirmed" value="1" required<?= !empty($_POST['quality_confirmed']) ? ' checked' : '' ?>> <span>Je confirme qu’il s’agit d’un véritable projet éditorial et non d’un dépôt industriel ou automatisé, d’une série de variantes répétitives sans valeur, d’un contenu trompeur ou manifestement bâclé destiné à remplir artificiellement le catalogue.</span></label>
                <p class="field-help"><strong>La publication reste une décision humaine.</strong> Un fichier techniquement valide peut être refusé pour des raisons éditoriales. Aucun score d’IA ne décide de l’acceptation.</p>
            </fieldset>

            <div class="submit-row">
                <button type="submit"<?= !$config['self_publishing_enabled'] ? ' disabled' : '' ?>>Envoyer pour validation humaine</button>
                <span>Aucune publication automatique.</span>
            </div>
        </form>
    <?php endif; ?>
</main>

<footer>
    <div class="footer-links"><a href="/ebooks.php">Ebooks</a><a href="/feedback.php?kind=self_publishing">Donner un retour</a><a href="/projet.php">Le projet</a></div>
    <p>Autoédition accompagnée et filtrée humainement — sans classement automatique opaque.</p>
</footer>
<script src="/assets/js/autoedition-print.js"></script>
</body>
</html>
