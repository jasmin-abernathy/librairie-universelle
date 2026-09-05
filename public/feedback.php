<?php

declare(strict_types=1);

[$config, $pdo] = require dirname(__DIR__) . '/src/bootstrap.php';
$errors = [];
$sent = false;
$defaultKind = isset($_GET['kind']) ? (string) $_GET['kind'] : 'general';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        $errors[] = 'La session du formulaire a expiré.';
    } else {
        try {
            FeedbackService::submit($pdo, $config, $_POST);
            $sent = true;
        } catch (Throwable $error) {
            $errors[] = $error->getMessage();
        }
    }
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Donner un retour — <?= e($config['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<header class="site-header">
    <a class="brand" href="/">📚 <span>Librairie universelle <small>nom de travail</small></span></a>
    <nav class="site-nav" aria-label="Navigation principale">
        <a href="/ebooks.php">Ebooks</a>
        <a href="/autoedition.php">Autoédition</a>
        <a href="/projet.php">Le projet</a>
    </nav>
</header>
<main id="main">
    <section class="page-hero compact">
        <p class="eyebrow">Alpha</p>
        <h1>Qu’est-ce qui coince ?</h1>
        <p class="lede">Une erreur de catalogue, une incompréhension, un problème d’accessibilité ou simplement quelque chose d’agaçant : c’est exactement ce qu’on veut trouver pendant l’alpha.</p>
        <?php if (!$config['feedback_enabled']): ?><p class="note"><strong>Canal fermé sur cet environnement :</strong> le formulaire sera activé au déploiement alpha.</p><?php endif; ?>
    </section>

    <?php if ($sent): ?>
        <div class="success-box" role="status"><h2>Merci.</h2><p>Le retour a été enregistré.</p></div>
    <?php else: ?>
        <?php if ($errors !== []): ?><div class="error-box" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <form class="submission-form narrow-form" method="post">
            <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
            <label>Type de retour
                <select name="kind">
                    <?php foreach (['general' => 'Retour général', 'error' => 'Bug', 'accessibility' => 'Accessibilité', 'catalog' => 'Catalogue / livre', 'self_publishing' => 'Autoédition'] as $value => $label): ?>
                        <option value="<?= e($value) ?>"<?= (($sent ? '' : ($_POST['kind'] ?? $defaultKind)) === $value) ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Ce qui s’est passé
                <textarea name="message" rows="8" maxlength="5000" required><?= e(isset($_POST['message']) ? (string) $_POST['message'] : '') ?></textarea>
            </label>
            <label>Page concernée <span class="optional">facultatif</span>
                <input name="page_url" maxlength="500" value="<?= e(isset($_POST['page_url']) ? (string) $_POST['page_url'] : '') ?>" placeholder="/work.php?id=…">
            </label>
            <label>E-mail <span class="optional">facultatif, uniquement si une réponse est utile</span>
                <input type="email" name="email" maxlength="254" value="<?= e(isset($_POST['email']) ? (string) $_POST['email'] : '') ?>">
            </label>
            <button type="submit"<?= !$config['feedback_enabled'] ? ' disabled' : '' ?>>Envoyer le retour</button>
        </form>
    <?php endif; ?>
</main>
<footer><p>Aucun identifiant publicitaire ni profil comportemental n’est associé aux retours.</p></footer>
</body>
</html>
