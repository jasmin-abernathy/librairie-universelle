<?php
declare(strict_types=1);
[$config,$pdo] = require dirname(__DIR__,2).'/src/bootstrap.php';
AdminAuth::enforce($config);
Csrf::token(); // La session doit démarrer avant le premier octet HTML.
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
$service=new AuthorBookListings($pdo);
$e=static fn(mixed $v):string=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$allowed=['pending','published','rejected','hidden'];
$status=(string)($_GET['status']??'pending');
if(!in_array($status,$allowed,true))$status='pending';
$message='';
$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 if(!Csrf::validate($_POST['csrf_token']??null)){
  $error='Session expirée : aucune modification enregistrée.';
 }else{
  $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
  $action=is_string($_POST['action']??null)?$_POST['action']:'';
  $note=is_string($_POST['review_note']??null)?$_POST['review_note']:'';
  try{
   if (!$id) throw new RuntimeException('Fiche introuvable.');
   if ($action === 'mark_signed') {
     if (($_POST['signed_confirmation']??null) !== '1') {
       throw new InvalidArgumentException('Confirmez la réception des signatures des deux parties.');
     }
     $service->recordSignature((int)$id,(string)($_POST['signature_reference']??''));
   } elseif (!$service->moderate((int)$id,$action,$note)) {
     throw new RuntimeException('Fiche introuvable.');
   }
   header('Location: /admin/livres-auteurs.php?status='.rawurlencode($status).'&saved=1',true,303);
   exit;
  }catch(InvalidArgumentException|RuntimeException $ex){$error=$ex->getMessage();}
 }
}
if(isset($_GET['saved']))$message='Décision enregistrée.';
$counts=$service->counts();
$items=$service->pendingReview($status);
?>
<!doctype html><html lang="fr"><head>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>Modérer les livres d’auteurs — Librairie universelle</title>
<link rel="stylesheet" href="/assets/css/app.css"><link rel="stylesheet" href="/assets/css/author-listings.css?v=20261104-2"></head>
<body><a class="skip-link" href="#main">Aller au contenu</a>
<header class="site-header"><a class="brand" href="/admin/">Administration</a><nav class="site-nav"><a href="/admin/">Tableau de bord</a><a href="/admin/submissions.php">Anciens dépôts</a><a href="/livres-auteurs.php" target="_blank" rel="noopener noreferrer">Voir le catalogue ↗</a></nav></header>
<main id="main" class="author-books-page">
<section class="page-hero compact"><p class="eyebrow">Fiches à examiner</p><h1>Valider les fiches d’auteurs</h1>
<p>Pour chaque vendeur : <strong>télécharger la convention PDF préremplie</strong>, l'envoyer à son adresse de contact, puis confirmer la signature reçue avant de publier. Une convention signée couvre tous ses livres ; les futurs partenaires librairies auront un autre cadre.</p></section>
<?php if($error): ?><div class="error-box" role="alert"><?= $e($error) ?></div><?php endif; ?>
<?php if($message): ?><div class="success-box" role="status"><?= $e($message) ?></div><?php endif; ?>
<nav class="author-book-status-nav" aria-label="Statut des fiches">
<?php foreach(['pending'=>'À examiner','published'=>'Publiées','rejected'=>'Refusées','hidden'=>'Masquées'] as $key=>$label): ?>
<a href="/admin/livres-auteurs.php?status=<?= $key ?>"<?= $status===$key?' aria-current="page"':'' ?>><?= $label ?> (<?= $counts[$key] ?>)</a>
<?php endforeach; ?>
</nav>
<?php if(!$items): ?><section class="content-section"><p>Aucune fiche dans cette catégorie.</p></section>
<?php else: ?><div class="author-book-grid">
<?php foreach($items as $item): ?>
<article class="author-book-card"><p class="eyebrow">N° <?= (int)$item['id'] ?> · <?= $e($item['status']) ?> · <?= $e($item['submitted_at']) ?></p>
<h2><?= $e($item['title']) ?></h2><p><strong><?= $e($item['author_name']) ?></strong></p>
<?php if($item['subtitle']): ?><p><?= $e($item['subtitle']) ?></p><?php endif; ?>
<p><?= nl2br($e($item['description'])) ?></p>
<dl><dt>Prix</dt><dd><?= $e(number_format((int)$item['price_cents']/100,2,',',' ')) ?> €</dd>
<dt>Format</dt><dd><?= $e($item['book_format']) ?></dd>
<dt>Statut du vendeur (déclaré)</dt><dd><?= $e($item['seller_type']) ?></dd>
<?php if($item['seller_type']==='professional'): ?>
<dt>SIRET confirmé via le registre</dt><dd><?= $e($item['siret']??'Non vérifié') ?></dd>
<dt>Dénomination officielle</dt><dd><?= $e($item['registered_company_name']??'Inconnue') ?></dd>
<dt>Consulté le</dt><dd><?= $e($item['siret_verified_at']??'—') ?></dd>
<?php if(!empty($item['siret'])): ?><dt>Annuaire officiel</dt><dd><a href="https://annuaire-entreprises.data.gouv.fr/entreprise/<?= $e(substr($item['siret'],0,9)) ?>" target="_blank" rel="noopener noreferrer">Vérifier l’immatriculation ↗</a></dd><?php endif; ?>
<?php endif; ?>
<dt>Vendeur / signataire (privé)</dt><dd><?= $e($item['seller_legal_name']??'Non renseigné') ?></dd>
<dt>Adresse contractuelle (privée)</dt><dd><?= nl2br($e($item['seller_postal_address']??'Non renseignée')) ?></dd>
<dt>Téléphone de signature</dt><dd><?= $e($item['seller_phone']??'—') ?></dd>
<dt>E-mail privé de suivi / signature</dt><dd><?= $e($item['private_email']) ?></dd>
<dt>E-mail public</dt><dd><?= $e($item['public_email']??'—') ?></dd>
<dt>ISBN</dt><dd><?= $e($item['isbn']??'—') ?></dd>
<?php if($item['seller_url']): ?><dt>Contact ou boutique</dt><dd><a href="<?= $e($item['seller_url']) ?>" target="_blank" rel="noopener noreferrer"><?= $e($item['seller_url']) ?></a></dd><?php endif; ?></dl>
<section class="author-agreement-box" aria-label="Convention de référencement">
<h3>Convention de mise en relation (3 %)</h3>
<?php if(!empty($item['agreement_signed_at'])): ?>
<p><strong>Convention signée, vérifiée le <?= $e($item['agreement_signed_at']) ?></strong></p>
<p class="field-help">Référence : <?= $e($item['agreement_signature_reference']??'') ?>. Valable pour tous les livres de ce vendeur.</p>
<?php elseif(!empty($item['seller_key']) && !empty($item['seller_legal_name']) && !empty($item['seller_postal_address'])): ?>
<p>À signer avant toute publication. Ce PDF est prérempli avec les coordonnées privées du vendeur ; il ne sera pas rendu public.</p>
<a class="button-link secondary" href="/admin/contrat-auteur.php?id=<?= (int)$item['id'] ?>" download>Générer le contrat PDF prérempli</a>
<p class="field-help">Envoyer la demande de signature à : <a href="mailto:<?= $e($item['private_email']) ?>"><?= $e($item['private_email']) ?></a><?= !empty($item['seller_phone'])?' · '.$e($item['seller_phone']):'' ?></p>
<form method="post" action="/admin/livres-auteurs.php?status=<?= $e($status) ?>">
<input type="hidden" name="csrf_token" value="<?= $e(Csrf::token()) ?>">
<input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
<label>Référence du contrat signé et conservé (privée) <input type="text" name="signature_reference" required minlength="3" maxlength="160" placeholder="Référence ou identifiant du contrat signé"></label>
<label class="check-row"><input type="checkbox" name="signed_confirmation" value="1" required> Je confirme avoir reçu et contrôlé les signatures des deux parties.</label>
<button type="submit" class="button-link" name="action" value="mark_signed">Enregistrer la convention signée</button>
</form>
<?php else: ?>
<p class="field-help">Les coordonnées contractuelles manquent à cette ancienne proposition. Demander au vendeur de soumettre une fiche complète avant de la publier.</p>
<?php endif; ?>
</section>
<form method="post" action="/admin/livres-auteurs.php?status=<?= $e($status) ?>">
<input type="hidden" name="csrf_token" value="<?= $e(Csrf::token()) ?>">
<input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
<label>Note de vérification (privée)<textarea name="review_note" maxlength="1000" rows="2"><?= $e($item['review_note']??'') ?></textarea></label>
<div class="author-book-actions">
<?php if($item['status']!=='published'): ?><button type="submit" class="button-link" name="action" value="publish" <?= empty($item['agreement_signed_at'])?'disabled aria-disabled="true"':'' ?>>Publier après vérification</button><?php endif; ?>
<?php if($item['status']!=='rejected'): ?><button type="submit" name="action" value="reject" class="button-link secondary">Refuser</button><?php endif; ?>
<?php if($item['status']==='published'): ?><button type="submit" name="action" value="hide" class="button-link secondary">Masquer</button><?php endif; ?>
</div></form>
</article>
<?php endforeach; ?></div><?php endif; ?>
</main></body></html>
