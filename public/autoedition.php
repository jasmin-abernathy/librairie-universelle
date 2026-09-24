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
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Déposer un livre indépendant pour validation humaine : EPUB, couverture, ISBN et version papier, sans publication automatique.">
    <title>Autoédition — <?= e($config['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<header class="site-header">
    <a class="brand" href="/">📚 <span>Librairie universelle <small>nom de travail</small></span></a>
    <nav class="site-nav" aria-label="Navigation principale">
        <a href="/ebooks.php">Ebooks</a>
        <a href="/autoedition.php" aria-current="page">Autoédition</a>
        <a href="/projet.php">Le projet</a>
        <a href="/sans-ia.php">Sans IA</a>
    </nav>
</header>

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
                <div class="form-grid two-columns">
                    <label>PDF prêt à imprimer <span class="optional">facultatif</span>
                        <input type="file" name="print_pdf" accept="application/pdf,.pdf">
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
</body>
</html>
