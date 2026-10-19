<?php
declare(strict_types=1);
$config=require dirname(__DIR__) . '/src/discovery-bootstrap.php';
header('Referrer-Policy: no-referrer');
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="referrer" content="no-referrer">
<title>Préparer le dossier d'impression — Librairie universelle</title>
<meta name="description" content="Vérifier et réunir votre PDF intérieur et les deux couvertures pour demander une impression sans envoyer vos fichiers à un tiers.">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/css/app.css?v=20261009-2">
<link rel="stylesheet" href="/assets/css/printer-kit.css?v=20261018-1">
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<?php $currentNav='autoedition'; require dirname(__DIR__) . '/src/views/site-header.php'; ?>
<main id="main">
<section class="page-hero compact">
<p class="eyebrow">Livre papier · dernière étape</p>
<h1>Votre dossier pour l'imprimeur</h1>
<p class="lede">Rassemblez trois PDF distincts : l'intérieur, la première et la quatrième de couverture. L'assistant contrôle leur présence et leur format puis prépare un récapitulatif à joindre à votre demande.</p>
<p><a class="text-link" href="/autoedition.php?partie=paper">← Retour à la composition papier</a></p>
<p class="kit-note"><strong>Besoin d'un PDF/X ?</strong> <a href="/export-pdfx.php">Convertir les PDF individuellement au format professionnel, si l'outil est activé</a>. Garde toujours tes originaux.</p>
</section>
<section class="kit-intro" aria-label="Fonctionnement du dossier">
  <div><span aria-hidden="true">1</span><strong>Composer</strong><p>Générez votre PDF intérieur et vos deux faces de couverture.</p></div>
  <div><span aria-hidden="true">2</span><strong>Vérifier</strong><p>Joignez les trois fichiers et relisez les conditions d'impression.</p></div>
  <div><span aria-hidden="true">3</span><strong>Transmettre</strong><p>Récupérez la fiche récapitulative et contactez l'imprimeur choisi.</p></div>
</section>
<div class="kit-grid">
  <form id="printer-kit-form" class="kit-form" novalidate>
    <fieldset>
      <legend>Votre ouvrage</legend>
      <div class="kit-fields">
        <label>Titre du livre <input id="kit-title" name="title" maxlength="180" required placeholder="Titre figurant sur la couverture"></label>
        <label>Auteur ou autrice <input id="kit-author" name="author" maxlength="140" required placeholder="Nom affiché sur le livre"></label>
        <label>ISBN papier <small>facultatif</small><input id="kit-isbn" name="isbn" maxlength="24" inputmode="numeric" placeholder="978…"></label>
        <label>Format <select id="kit-format" name="format"><option value="a5">A5 — 148 × 210 mm</option><option value="140x210">140 × 210 mm</option><option value="135x215">135 × 215 mm</option><option value="152x229">152 × 229 mm</option></select></label>
        <label>Papier intérieur <select id="kit-paper" name="paper"><option value="80g">80 g noir et blanc</option><option value="90g">90 g — sur devis</option><option value="other">Autre — à préciser</option></select></label>
        <label>Nombre de pages <small>si connu</small><input id="kit-pages" name="pages" type="number" min="1" max="5000" step="1" placeholder="Ex. 200"></label>
        <label>Tirage demandé <small>si connu</small><input id="kit-quantity" name="quantity" type="number" min="1" max="10000" step="1" placeholder="Ex. 30"></label>
      </div>
    </fieldset>
    <fieldset>
      <legend>Les trois fichiers PDF</legend>
      <p class="kit-note">Ces fichiers restent sur votre appareil. Leurs débuts et fins sont lus localement pour repérer les marqueurs PDF. Seuls leurs noms et tailles figurent dans la fiche générée.</p>
      <label>PDF de l'intérieur <span class="kit-required">obligatoire</span>
        <input id="kit-interior" type="file" accept="application/pdf,.pdf" required>
        <small>Pages simples dans l'ordre de lecture, avec pages blanches nécessaires au recto verso. L'imprimeur s'occupe de l'imposition.</small>
      </label>
      <label>PDF de la première de couverture <span class="kit-required">obligatoire</span>
        <input id="kit-front" type="file" accept="application/pdf,.pdf" required>
      </label>
      <label>PDF de la quatrième de couverture <span class="kit-required">obligatoire</span>
        <input id="kit-back" type="file" accept="application/pdf,.pdf" required>
        <small>Ne prévoyez pas de tranche arbitraire : l'imprimeur calcule son épaisseur réelle.</small>
      </label>
    </fieldset>
    <fieldset>
      <legend>Dernières vérifications</legend>
      <label class="kit-check"><input id="kit-check-pages" type="checkbox"> <span>J'ai relu les pages du PDF intérieur, leur ordre et les éventuelles pages blanches.</span></label>
      <label class="kit-check"><input id="kit-check-covers" type="checkbox"> <span>J'ai vérifié les deux faces de couverture et les mentions utiles (ISBN, prix, code-barres si nécessaire).</span></label>
      <label class="kit-check"><input id="kit-check-printer" type="checkbox"> <span>Je sais que le format, les fonds perdus, la tranche, les polices et le BAT doivent être validés par l'imprimeur.</span></label>
      <p class="kit-note">Pour Corep Metz, les tarifs connus sont indicatifs. Un BAT papier est facturé séparément ; le papier 90 g nécessite un devis. Une validation sur cette page ne constitue ni commande ni confirmation de prix.</p>
    </fieldset>
    <button type="submit" class="kit-primary" id="kit-review">Préparer mon dossier</button>
    <p id="kit-status" role="status" aria-live="polite">Prêt à vérifier vos trois PDF.</p>
  </form>
  <aside class="kit-results" aria-labelledby="kit-results-title">
    <p class="eyebrow">Votre récapitulatif</p>
    <h2 id="kit-results-title">Un dossier clair pour l'imprimeur</h2>
    <p>Après le contrôle, téléchargez le ZIP comprenant vos trois PDF et la fiche de transmission, ou récupérez la fiche seule. Cette préparation reste locale à votre appareil.</p>
    <ul id="kit-file-report"><li>Intérieur PDF : en attente</li><li>Première de couverture : en attente</li><li>Quatrième de couverture : en attente</li></ul>
    <div class="kit-actions">
      <button type="button" id="kit-zip" disabled>Télécharger les trois PDF et la fiche (.zip)</button>
      <button type="button" id="kit-download" disabled>Télécharger la fiche seule (.json)</button>
      <button type="button" id="kit-copy" disabled>Copier le message pour l'imprimeur</button>
    </div>

    <section class="kit-handoff" id="kit-corep-handoff" aria-labelledby="kit-corep-title">
      <p class="eyebrow">Votre dossier est prêt ?</p>
      <h3 id="kit-corep-title">Demander l’impression à Corep Metz</h3>
      <p>Vous pouvez maintenant demander un devis directement à l’imprimerie. Sur le formulaire Corep, <strong>choisissez l’agence de Metz</strong>, joignez vos <strong>trois PDF séparément</strong> et précisez le tirage souhaité. Le bouton « Copier le message pour l’imprimeur » ci-dessus prépare le texte de votre demande.</p>
      <div class="paper-pro-links">
        <a class="button-link" href="https://www.corep.fr/fr/devis-sur-mesure/" target="_blank" rel="noopener noreferrer">Déposer mes PDF sur Corep <span aria-hidden="true">↗</span></a>
        <a class="button-link secondary" href="mailto:metz@corep.fr?subject=Demande%20de%20devis%20-%20impression%20de%20livre">Contacter Corep Metz par e-mail</a>
      </div>
      <p class="kit-note">Le ZIP conserve vos fichiers et la fiche sur votre appareil : le formulaire de Corep peut demander les PDF individuellement. Aucun document n’est transmis automatiquement et aucune commande n’est passée depuis ce site. Corep reste libre de confirmer ses tarifs et la faisabilité de l’impression.</p>
    </section>
    <div class="kit-warning">
      <strong>Ce que l'outil ne prétend pas vérifier</strong>
      <p>La conformité PDF/X, la structure complète des pages, l'incorporation des polices, la taille réelle des pages, la définition des images, les couleurs et le BAT. Le contrôle des fichiers reste à confirmer par l'imprimeur. Le dossier ne transmet aucun document automatiquement.</p>
    </div>
    <p><a href="/assistant-papier.php">Reprendre la création des couvertures →</a></p>
  </aside>
</div>
</main>
<footer><div class="footer-links"><a href="/autoedition.php">Autoédition</a><a href="/projet.php">Le projet</a></div><p>Pas de transfert automatique de manuscrit ni de commande.</p></footer>
<script src="/assets/js/paper-barcode.js?v=20261018-1"></script>
<script src="/assets/js/printer-zip.js?v=20261018-1"></script>
<script src="/assets/js/printer-kit.js?v=20261018-3"></script>
</body></html>
