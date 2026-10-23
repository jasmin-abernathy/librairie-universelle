<?php
declare(strict_types=1);
$config = require dirname(__DIR__) . '/src/discovery-bootstrap.php';
?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <title>Assistant de publication papier — <?= htmlspecialchars((string) $config['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
    <meta name="description" content="Composer séparément la première et la quatrième de couverture d'un livre papier, sans modifier votre manuscrit ni votre EPUB.">
    <link rel="stylesheet" href="/assets/css/app.css?v=20261009-2">
    <link rel="stylesheet" href="/assets/css/paper-assistant.css?v=20261018-2">
    <style id="paper-sheet-size"></style>
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<?php $currentNav = 'autoedition'; require dirname(__DIR__) . '/src/views/site-header.php'; ?>
<main id="main">
    <section class="page-hero compact">
        <p class="eyebrow">Livre papier · atelier pratique</p>
        <h1>Préparer la première et la quatrième de couverture.</h1>
        <p class="lede">Concevez les deux faces séparément, puis utilisez l'impression du navigateur pour les enregistrer en PDF. Votre manuscrit et votre EPUB ne sont jamais modifiés.</p>
        <p><a href="/autoedition.php">← Retour au formulaire d’autoédition</a></p>
    </section>
    <section class="content-section paper-guidance" aria-labelledby="guidance-heading">
        <h2 id="guidance-heading">Ce que demande l'imprimeur</h2>
        <ol>
            <li><strong>Intérieur :</strong> PDF simple, pages dans l'ordre de lecture, avec les pages blanches utiles pour le recto verso. L'imprimeur s'occupe de l'imposition.</li>
            <li><strong>Couverture :</strong> première et quatrième fournies individuellement, dans le format du livre.</li>
            <li><strong>Tranche :</strong> ne calculez pas sa largeur. Corep Metz déterminera l'épaisseur réelle selon l'intérieur et les caractéristiques du papier.</li>
            <li><strong>Contrôle :</strong> un BAT numérique peut servir d'étape de vérification ; un BAT papier est facturé séparément au prix d'un exemplaire.</li>
        </ol>
        <p>Le papier 80 g proposé par Corep est certifié FSC/PEFC ; le 90 g nécessite un autre devis. Demandez confirmation pour les fonds perdus, les couleurs, les polices et toute contrainte particulière avant l'impression finale.</p>
    </section>
    <div class="paper-workspace">
        <section class="paper-controls" aria-labelledby="paper-controls-title">
            <h2 id="paper-controls-title">Votre couverture, étape par étape</h2>
            <p class="paper-help">Tout se prépare dans ce navigateur. Les images ne sont pas envoyées sur un serveur tant que vous ne les joignez pas volontairement à une soumission.</p>
            <fieldset>
                <legend>1. Format et présentation</legend>
                <label>Format du livre
                    <select id="paper-size" aria-describedby="paper-cover-a5-help">
                        <option value="148x210" selected>A5 — 14,8 × 21 cm (format imposé)</option>
                    </select>
                    <span class="paper-help" id="paper-cover-a5-help">Les deux faces sont préparées en A5. Corep précisera les exigences éventuelles de fond perdu et de tranche pour le dos carré collé.</span>
                </label>
                <label>Titre du livre
                    <input id="paper-title" type="text" maxlength="180" placeholder="Le titre de votre livre" value="">
                </label>
                <label>Nom figurant sur la couverture
                    <input id="paper-author" type="text" maxlength="140" placeholder="Prénom Nom / nom de plume">
                </label>
                <label>Sous-titre <span class="optional">facultatif</span>
                    <input id="paper-subtitle" type="text" maxlength="180" placeholder="Un sous-titre si nécessaire">
                </label>
                <div class="paper-colors">
                    <label>Couleur de fond <input id="paper-bg" type="color" value="#28543f"></label>
                    <label>Couleur du texte <input id="paper-ink" type="color" value="#ffffff"></label>
                </div>
            </fieldset>
            <fieldset>
                <legend>2. Première de couverture</legend>
                <label>Image de couverture <span class="optional">facultatif</span>
                    <input id="paper-front-image" type="file" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp">
                </label>
                <p id="paper-front-image-check" class="paper-help" role="status">JPEG, PNG ou WebP, 15 Mo maximum. Si l'image contient déjà le titre, vous pouvez masquer les textes ajoutés.</p>
                <label class="paper-check"><input id="paper-front-ready" type="checkbox"> Mon image contient déjà les textes, ne pas en superposer</label>
            </fieldset>
            <fieldset>
                <legend>3. Quatrième de couverture</legend>
                <label>Résumé / présentation
                    <textarea id="paper-description" maxlength="1050" rows="8" placeholder="Quelques paragraphes pour donner envie de découvrir le livre."></textarea>
                </label>
                <label>Prix public TTC <span class="optional">facultatif</span>
                    <input id="paper-price" type="number" min="0" max="9999" step="0.01" inputmode="decimal" placeholder="Ex. 14,90">
                    <span class="paper-help">Si le livre est commercialisé, indiquez le prix de vente fixé par l’éditeur. Il devra être visible sur le livre.</span>
                </label>
                <label>ISBN papier <span class="optional">facultatif</span>
                    <input id="paper-isbn" inputmode="numeric" maxlength="24" placeholder="978…">
                </label>
                <label>Image de fond pour la quatrième <span class="optional">facultatif</span>
                    <input id="paper-back-image" type="file" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp">
                </label>
                <p id="paper-back-image-check" class="paper-help" role="status">Le texte est composé par-dessus l'image éventuelle. Laissez-la vide pour un fond uni.</p>
                <label>Image du véritable code-barres ISBN <span class="optional">facultatif</span>
                    <input id="paper-barcode-image" type="file" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp">
                </label>
                <p id="paper-isbn-check" class="paper-help" role="status" aria-live="polite">Saisissez votre ISBN-13 attribué pour calculer automatiquement son code EAN-13 vérifié. Vous pouvez aussi fournir votre propre image.</p>
                <p class="paper-help">Le code EAN-13 est calculé depuis votre ISBN, jamais attribué par l'outil. Vérifiez l'ISBN officiel, la lisibilité du code imprimé et les dimensions finales auprès de l'imprimeur.</p>
            </fieldset>
            <fieldset>
                <legend>4. Enregistrer les deux faces</legend>
                <p>Chaque bouton ouvre la boîte d'impression : choisissez « Enregistrer au format PDF », à l'échelle 100 %, sans en-tête ni pied de page, avec les graphiques d'arrière-plan.</p>
                <p class="paper-help" id="paper-export-format-help"><strong>Attention au format :</strong> dans la fenêtre d'impression, ouvrez « Plus de paramètres » si nécessaire, puis « Taille du papier » et choisissez <strong>A5 (148 × 210 mm)</strong>, et non A4 (souvent sélectionné par défaut). Choisissez aussi les <strong>marges « Aucune »</strong>. Le format A5 est imposé à cette étape. <strong>Avant d'enregistrer chaque PDF, vérifiez que l'aperçu montre une seule couverture à la bonne taille, sans marge blanche.</strong> Certains navigateurs ignorent le format demandé automatiquement par le site.</p>
                <div class="paper-actions">
                    <button type="button" id="paper-export-front">PDF : première de couverture</button>
                    <button type="button" id="paper-export-back" class="secondary-action">PDF : quatrième de couverture</button>
                </div>
                <div class="paper-draft-actions">
                    <button type="button" id="paper-save-draft" class="secondary-action">Sauvegarder la couverture</button>
                    <button type="button" id="paper-restore-draft" class="secondary-action">Reprendre la couverture</button>
                    <button type="button" id="paper-clear-draft" class="secondary-action">Effacer la sauvegarde</button>
                </div>
                <p id="paper-draft-status" role="status" class="paper-help">Les textes et couleurs peuvent être conservés localement. Les images ne seront pas sauvegardées.</p>
                <p class="paper-help" role="status" id="paper-print-check">Contrôlez toujours les PDF exportés et faites valider les réglages particuliers par l'imprimeur.</p>
                <p>Vous pourrez ensuite joindre séparément les deux PDF depuis <a href="/autoedition.php">l’étape « Version papier » du formulaire</a>. Aucun envoi ni commande ne part de cet assistant.</p>
                <p><a href="/dossier-impression.php">Mes deux PDF sont prêts : préparer le dossier d'impression →</a></p>
            </fieldset>
        </section>
        <section class="paper-previews" aria-label="Aperçu des couvertures">
            <h2>Visualiser avant l'export</h2>
            <div class="paper-preview-grid">
                <div class="paper-preview-item">
                    <p><strong>Première de couverture</strong></p>
                    <article class="paper-cover-sheet paper-cover-front" id="paper-front-preview" aria-label="Aperçu première de couverture">
                        <img id="paper-front-visual" class="paper-cover-visual" alt="" hidden>
                        <div id="paper-front-text" class="paper-cover-front-copy">
                            <p id="paper-front-author">Nom de l'auteur</p>
                            <h3 id="paper-front-title">Titre du livre</h3>
                            <p id="paper-front-subtitle"></p>
                        </div>
                    </article>
                </div>
                <div class="paper-preview-item">
                    <p><strong>Quatrième de couverture</strong></p>
                    <article class="paper-cover-sheet paper-cover-back" id="paper-back-preview" aria-label="Aperçu quatrième de couverture">
                        <img id="paper-back-visual" class="paper-cover-visual" alt="" hidden>
                        <div class="paper-cover-back-copy">
                            <h3 id="paper-back-title">Titre du livre</h3>
                            <p id="paper-back-description">Votre résumé apparaîtra ici.</p>
                            <div id="paper-barcode-zone" class="paper-barcode-zone">
                                <img id="paper-barcode-visual" alt="Code-barres ISBN fourni par l'auteur" hidden>
                                <span id="paper-back-isbn">Emplacement réservé au code-barres</span>
                                <span id="paper-back-price" class="paper-back-price" hidden></span>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
            <p class="paper-help">Aperçu visuel. Un fond perdu ou une finition particulière peut exiger des dimensions différentes : les demandes spécifiques restent à valider avec Corep.</p>
        </section>
    </div>
</main>
<footer><div class="footer-links"><a href="/autoedition.php">Autoédition</a><a href="/projet.php">Le projet</a></div><p>L'impression finale et le BAT restent soumis à la validation de l'imprimeur.</p></footer>
<script src="/assets/js/paper-barcode.js?v=20261018-1"></script>
<script src="/assets/js/paper-assistant.js?v=20261018-3"></script>
</body></html>
