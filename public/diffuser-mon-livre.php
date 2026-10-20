<?php
declare(strict_types=1);
$config = require dirname(__DIR__) . '/src/discovery-bootstrap.php';
header('Referrer-Policy: no-referrer');
?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <meta name="description" content="Parcours simple pour vendre son livre autoédité : référencement après validation, fiche de présentation, librairies proches et message personnalisé prêt à envoyer.">
    <title>Faire connaître et vendre mon livre — Librairie universelle</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/css/app.css?v=20261009-2">
    <link rel="stylesheet" href="/assets/css/diffusion-auteur.css?v=20261020-1">
    <script src="/assets/js/diffusion-auteur.js?v=20261020-1" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<?php $currentNav = 'diffusion'; require dirname(__DIR__) . '/src/views/site-header.php'; ?>
<main id="main" class="author-sales-page">
    <section class="page-hero compact">
        <p class="eyebrow">Après la création · un parcours concret</p>
        <h1>Votre livre est prêt. Comment le faire découvrir et vendre ?</h1>
        <p class="lede">Nous vous guidons pour présenter votre livre aux librairies, en commençant près de chez vous. Vous gardez la main sur les contacts, les conditions commerciales et les exemplaires.</p>
        <div class="cta-row">
            <a class="button-link" href="#fiche-libraire">Préparer ma présentation</a>
            <a class="button-link secondary" href="/autoedition.php?partie=project">Déposer mon livre au catalogue</a>
        </div>
    </section>

    <section class="sales-steps" aria-labelledby="sales-steps-title">
        <h2 id="sales-steps-title">Quatre étapes, sans démarche compliquée</h2>
        <div class="sales-step-grid">
            <article class="sales-step">
                <span class="sales-step-number" aria-hidden="true">1</span>
                <h3>Déposer mon livre</h3>
                <p>Renseignez le titre, l'auteur, le résumé, le prix et les fichiers disponibles dans le <a href="/autoedition.php?partie=project">parcours d'autoédition</a>. Vous pouvez utiliser les outils de préparation gratuitement.</p>
                <p class="sales-step-note"><strong>Catalogue :</strong> après acceptation de votre dépôt par une personne et publication par l'équipe, sa fiche est créée automatiquement dans le catalogue de Librairie universelle. Le dépôt seul ne la rend pas publique.</p>
            </article>
            <article class="sales-step">
                <span class="sales-step-number" aria-hidden="true">2</span>
                <h3>Organiser l'impression</h3>
                <p>Si vous souhaitez vendre une version papier, préparez votre dossier, demandez un devis à l'imprimeur de votre choix et vérifiez l'ISBN papier, le prix public et les obligations applicables à votre édition.</p>
                <p><a href="/dossier-impression.php">Préparer les fichiers pour l'imprimeur →</a></p>
            </article>
            <article class="sales-step">
                <span class="sales-step-number" aria-hidden="true">3</span>
                <h3>Contacter quelques librairies locales</h3>
                <p>Commencez par trois ou quatre librairies proches dont le public pourrait aimer votre livre. Préparez une courte fiche, personnalisez votre message, puis demandez si elles souhaitent examiner l'ouvrage.</p>
                <p><a href="/#librairies">Trouver des librairies près de chez moi →</a></p>
            </article>
            <article class="sales-step">
                <span class="sales-step-number" aria-hidden="true">4</span>
                <h3>Convenir des modalités et suivre</h3>
                <p>Voyez directement avec chaque librairie si elle préfère commander des exemplaires ou envisager un dépôt-vente, ainsi que la remise, les invendus, les retours et le règlement. Notez ses réponses et ne relancez pas une librairie qui refuse.</p>
            </article>
        </div>
        <p class="sales-truth"><strong>À ce stade, une fiche au catalogue ne signifie pas que le livre est achetable en ligne.</strong> La prise de commande, le paiement, l'approvisionnement et la réservation locale ne sont pas encore automatisés pour les livres déposés. Vous convenez des ventes directement avec les librairies jusqu'à l'ouverture de ces fonctions.</p>
    </section>

    <section id="fiche-libraire" class="sales-tool" aria-labelledby="fiche-libraire-title">
        <div class="sales-tool-intro">
            <p class="eyebrow">Un petit coup de main, sans compte</p>
            <h2 id="fiche-libraire-title">Préparer ma fiche et mon message pour une librairie</h2>
            <p>Complétez les renseignements dont vous disposez. L'outil compose une présentation courte et un e-mail personnalisable. <strong>Rien n'est envoyé ni enregistré sur notre serveur.</strong> Vous choisissez ensuite à qui transmettre votre proposition.</p>
        </div>
        <div class="sales-tool-layout">
            <form id="sales-pitch-form" class="sales-pitch-form">
                <label for="sales-bookstore">Nom de la librairie <span class="optional">facultatif</span>
                    <input id="sales-bookstore" name="bookstore" maxlength="120" placeholder="La librairie que je souhaite contacter">
                </label>
                <div class="sales-field-grid">
                    <label for="sales-title">Titre du livre
                        <input id="sales-title" name="title" maxlength="200" required placeholder="Titre de mon livre">
                    </label>
                    <label for="sales-author">Nom d'auteur ou autrice
                        <input id="sales-author" name="author" maxlength="140" required placeholder="Nom de plume ou nom public">
                    </label>
                    <label for="sales-isbn">ISBN papier <span class="optional">si attribué</span>
                        <input id="sales-isbn" name="isbn" inputmode="numeric" maxlength="24" placeholder="978…">
                    </label>
                    <label for="sales-price">Prix public du livre <span class="optional">si fixé</span>
                        <input id="sales-price" name="price" maxlength="35" placeholder="Ex. 14,90 €">
                    </label>
                    <label for="sales-format">Format du livre <span class="optional">facultatif</span>
                        <input id="sales-format" name="format" maxlength="70" placeholder="Ex. A5, broché, 180 pages">
                    </label>
                    <label for="sales-contact">Contact à indiquer <span class="optional">facultatif</span>
                        <input id="sales-contact" name="contact" maxlength="180" placeholder="Votre e-mail professionnel">
                    </label>
                </div>
                <label for="sales-summary">De quoi parle votre livre ?
                    <textarea id="sales-summary" name="summary" maxlength="850" rows="4" required placeholder="Quelques phrases sur l'histoire, le sujet et le public auquel il s'adresse."></textarea>
                </label>
                <label for="sales-url">Lien vers la fiche publique <span class="optional">uniquement après publication</span>
                    <input id="sales-url" name="url" type="url" maxlength="400" placeholder="https://librairie.lepotager.org/work.php?id=…">
                </label>
                <p class="sales-field-help">Vous n'avez pas encore d'ISBN ni de stock imprimé ? Vous pouvez préparer votre présentation, mais confirmez les informations commerciales et l'approvisionnement avant de proposer une vente.</p>
                <button type="submit" class="sales-primary">Créer mon message et ma fiche</button>
            </form>
            <div class="sales-pitch-output" aria-labelledby="sales-output-title">
                <h3 id="sales-output-title">Votre présentation prête à personnaliser</h3>
                <p id="sales-output-status" role="status" aria-live="polite">Remplissez le formulaire pour obtenir une fiche et un message à envoyer vous-même.</p>
                <div id="sales-output-result" hidden>
                    <h4>Message pour la librairie</h4>
                    <pre id="sales-message" class="sales-preview" tabindex="0"></pre>
                    <button type="button" id="sales-copy-message" class="sales-secondary">Copier le message</button>
                    <h4>Fiche de présentation du livre</h4>
                    <pre id="sales-sheet" class="sales-preview" tabindex="0"></pre>
                    <button type="button" id="sales-copy-sheet" class="sales-secondary">Copier la fiche</button>
                    <button type="button" id="sales-download-sheet" class="sales-secondary">Télécharger la fiche (.txt)</button>
                    <p class="sales-privacy">Les textes restent dans ce navigateur. Aucun e-mail n'est envoyé et aucune librairie n'est contactée automatiquement. Relisez et adaptez la proposition à chaque établissement.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="sales-after" aria-labelledby="sales-after-title">
        <h2 id="sales-after-title">Et après le premier contact ?</h2>
        <div class="sales-after-grid">
            <div><h3>Si la librairie est intéressée</h3><p>Précisez avec elle le prix de vente, sa rémunération, qui fournit les livres, la quantité, les délais, la facturation, les retours et les invendus. Ne livrez pas d'exemplaires avant d'avoir convenu des conditions.</p></div>
            <div><h3>Si elle ne répond pas</h3><p>Une relance polie peut suffire. Vous pouvez aussi rencontrer un libraire local avec une fiche courte et, si demandé, un exemplaire. Évitez les envois identiques à grande échelle.</p></div>
            <div><h3>Si vous souhaitez être accompagné</h3><p>La préparation et la diffusion autonomes restent gratuites. Une aide humaine à la correction ou à la préparation du dossier pourra être proposée séparément, uniquement sur devis accepté.</p></div>
        </div>
        <div class="cta-row">
            <a class="button-link" href="/#librairies">Repérer des librairies proches</a>
            <a class="text-link" href="/autoedition.php?partie=project">Revenir à mon dépôt</a>
        </div>
    </section>
</main>
<footer>
    <div class="footer-links"><a href="/autoedition.php">Autoédition</a><a href="/projet.php">Le projet</a><a href="/feedback.php?kind=self_publishing">Donner un retour</a></div>
    <p>Un réseau de proximité se construit avec les librairies, pas à leur place.</p>
</footer>
</body></html>
