<?php
declare(strict_types=1);

[$config,$pdo] = require dirname(__DIR__) . '/src/bootstrap.php';
$listingService = new AuthorBookListings($pdo);
Csrf::token();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $_SESSION['author_listing_loaded_at'] = time();
}
$errors = [];
$accepted = false;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $loadedAt = (int)($_SESSION['author_listing_loaded_at'] ?? 0);
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expirée. Actualisez la page puis réessayez.';
    } elseif (($_POST['website'] ?? '') !== '') {
        $errors[] = 'La proposition ne peut pas être traitée.';
    } elseif ($loadedAt < 1 || time() - $loadedAt < 3) {
        $errors[] = 'Prenez un instant pour relire les informations avant l’envoi.';
    } elseif (time() - (int)($_SESSION['author_listing_last_sent'] ?? 0) < 120) {
        $errors[] = 'Une proposition vient d’être reçue. Réessayez plus tard.';
    } elseif (trim((string)($config['admin_token'] ?? '')) === '') {
        $errors[] = 'Le service de validation n’est pas encore configuré. Aucun contenu n’a été envoyé.';
    } else {
        try {
            $listingService->submit($_POST);
            $_SESSION['author_listing_last_sent'] = time();
            $accepted = true;
        } catch (InvalidArgumentException | DomainException | RuntimeException $e) {
            $errors[] = $e->getMessage();
        } catch (PDOException $e) {
            error_log('author_listings_submission: '.$e->getCode());
            $errors[] = 'Enregistrement impossible pour le moment. Réessayez ultérieurement.';
        }
    }
}
$e = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$old = static fn(string $key): string => htmlspecialchars(is_string($_POST[$key]??null)?$_POST[$key]:'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
header('Referrer-Policy: no-referrer');
?>
<!doctype html>
<html lang="fr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,follow">
<meta name="referrer" content="no-referrer">
<title>Proposer la fiche de mon livre — Librairie universelle</title>
<meta name="description" content="Proposez une fiche de votre livre indépendant, sans envoyer de manuscrit. Chaque fiche est examinée avant publication.">
<link rel="stylesheet" href="/assets/css/app.css?v=20261104-nav2">
<link rel="stylesheet" href="/assets/css/author-listings.css?v=20261104-2">
<script src="/assets/js/author-listing-form.js?v=20261104-3" defer></script>
</head><body>
<a class="skip-link" href="#main">Aller au contenu</a>
<?php $currentNav='propose'; require dirname(__DIR__).'/src/views/site-header.php'; ?>
<main id="main" class="author-books-page">
<section class="page-hero compact">
<p class="eyebrow">Catalogue indépendant · référencement humain</p>
<h1>Proposer la fiche de mon livre</h1>
<p class="lede">Décrivez votre ouvrage et indiquez comment les lecteurs peuvent vous contacter ou l’acheter directement. <strong>Ne transmettez aucun manuscrit, EPUB ni PDF.</strong> La Librairie n’encaisse rien et ne vend rien en votre nom.</p>
<div class="cta-row"><a class="text-link" href="/livres-auteurs.php">Voir les livres déjà référencés →</a><a class="text-link" href="/diffuser-mon-livre.php">Découvrir le parcours vers les librairies →</a></div>
</section>
<section class="content-section">
<h2>Avant l’envoi</h2>
<p>La proposition est <strong>privée tant que l’administrateur ne l’a pas validée</strong>. La vérification porte sur les informations de la fiche, pas sur l’intégralité du manuscrit. Tu conserves la responsabilité des prix, du paiement, de la livraison, des retours et des obligations légales liées à la vente.</p>
<p>L’adresse de suivi reste privée. L’adresse de contact publique et le lien de vente seront visibles par tous <strong>seulement après validation</strong>. Ne renseignez pas d’adresse personnelle que vous ne souhaitez pas diffuser.</p>
</section>
<?php if($accepted): ?>
<section class="content-section"><div class="success-box" role="status"><h2>Proposition reçue</h2><p>Ta fiche est en attente de vérification. Elle n’apparaît pas encore dans le catalogue. Aucune vente ni transmission de fichier n’a été déclenchée.</p><p><a href="/livres-auteurs.php">Voir le catalogue →</a></p></div></section>
<?php else: ?>
<?php if($errors): ?><div class="error-box" role="alert"><strong>La fiche n’a pas été envoyée.</strong><ul><?php foreach($errors as $err): ?><li><?= $e($err) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<section class="content-section">
<form class="author-book-form" method="post" action="/proposer-livre.php" enctype="application/x-www-form-urlencoded">
<input type="hidden" name="csrf_token" value="<?= $e(Csrf::token()) ?>">
<div class="hp-field" aria-hidden="true"><label>Site web <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
<fieldset><legend>Le livre</legend>
<div class="form-grid two-columns">
<label>Titre <input required maxlength="200" name="title" value="<?= $old('title') ?>"></label>
<label>Sous-titre (facultatif) <input maxlength="200" name="subtitle" value="<?= $old('subtitle') ?>"></label>
<label>Nom public de l’auteur ou de l’autrice <input required maxlength="140" name="author_name" value="<?= $old('author_name') ?>"></label>
<label>Format
<select name="book_format" required>
<?php foreach(['paper'=>'Papier','ebook'=>'Numérique','both'=>'Papier et numérique'] as $val=>$label): ?><option value="<?= $val ?>"<?= ($_POST['book_format']??'paper')===$val?' selected':'' ?>><?= $label ?></option><?php endforeach; ?>
</select></label>
<label>ISBN (facultatif, si attribué) <input name="isbn" maxlength="24" value="<?= $old('isbn') ?>" inputmode="numeric"></label>
<label>Prix public en euros (0 si gratuit) <input name="price" required maxlength="12" inputmode="decimal" placeholder="15,00" value="<?= $old('price') ?>"></label>
</div>
<label>Résumé (30 à 1 600 caractères) <textarea required minlength="30" maxlength="1600" name="description" rows="6"><?= $old('description') ?></textarea></label>
</fieldset>
<fieldset><legend>Le vendeur et la prise de contact</legend>
<div class="form-grid two-columns">
<label>Je vends en tant que
<select name="seller_type" id="listing-seller-type" required><option value="individual"<?= ($_POST['seller_type']??'individual')==='individual'?' selected':'' ?>>Particulier</option><option value="professional"<?= ($_POST['seller_type']??'')==='professional'?' selected':'' ?>>Professionnel</option></select></label>
<div id="professional-siret-field"<?= ($_POST['seller_type']??'individual')==='professional'?'':' hidden' ?>>
<label for="listing-siret">SIRET (obligatoire pour les professionnels)
<input name="siret" id="listing-siret" type="text" inputmode="numeric" minlength="14" maxlength="14" pattern="[0-9]{14}" autocomplete="off" placeholder="14 chiffres" value="<?= $old('siret') ?>"<?= ($_POST['seller_type']??'individual')==='professional'?' required':' disabled' ?> aria-describedby="siret-help">
</label>
<p id="siret-help" class="field-help">Recherchez votre entreprise dans l’<a href="https://annuaire-entreprises.data.gouv.fr/" target="_blank" rel="noopener noreferrer">Annuaire officiel</a>, puis sélectionnez le résultat. Le SIRET et la dénomination seront affichés après validation. Cette recherche vérifie l’immatriculation publique, pas votre identité ni votre habilitation à représenter l’entreprise.</p>
<button id="siret-search-button" class="button-link secondary" type="button">Rechercher l’entreprise</button>
<div id="siret-search-results" role="status" aria-live="polite"></div>
<input type="hidden" id="selected-company-siret" name="selected_company_siret" value="">
</div>
<label>E-mail de suivi (jamais affiché publiquement) <input type="email" required maxlength="254" name="private_email" autocomplete="email" value="<?= $old('private_email') ?>"></label>
<label>Adresse e-mail publique pour les lecteurs (facultatif) <input type="email" maxlength="254" name="public_email" value="<?= $old('public_email') ?>"></label>
<label>Lien HTTPS vers votre boutique ou page de contact (facultatif) <input type="url" maxlength="500" name="seller_url" placeholder="https://…" value="<?= $old('seller_url') ?>"></label>
</div>
<p class="field-help">Indiquez au moins une adresse publique ou un lien HTTPS. Pour les professionnels, la consultation du registre officiel est obligatoire avant l’envoi. La Librairie ne fournit pas de commande, de paiement ni de livraison.</p>
</fieldset>
<fieldset><legend>Confirmation avant modération</legend>
<label class="check-row"><input type="checkbox" name="rights_confirmed" value="1" required<?= !empty($_POST['rights_confirmed'])?' checked':'' ?>> Je confirme être habilité à présenter ce livre et ses informations.</label>
<label class="check-row"><input type="checkbox" name="public_contact_confirmed" value="1" required<?= !empty($_POST['public_contact_confirmed'])?' checked':'' ?>> J’accepte la publication de la fiche, du moyen de contact choisi et, si je suis professionnel, du SIRET et de la dénomination officielle après validation humaine. Mon adresse de suivi reste privée.</label>
<label class="check-row"><input type="checkbox" name="seller_responsibility_confirmed" value="1" required<?= !empty($_POST['seller_responsibility_confirmed'])?' checked':'' ?>> Je comprends que la Librairie met uniquement en relation et que je gère directement toute vente éventuelle avec le lecteur.</label>
</fieldset>
<button type="submit" class="button-link author-book-submit">Envoyer ma fiche pour vérification</button>
</form>
</section>
<?php endif; ?>
</main><footer><div class="footer-links"><a href="/livres-auteurs.php">Livres d’auteurs</a><a href="/autoedition.php">Préparer un livre</a><a href="/diffuser-mon-livre.php">Diffuser mon livre</a></div><p>Aucune publication automatique, aucun paiement intégré.</p></footer>
</body></html>
