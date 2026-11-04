<?php
declare(strict_types=1);
[$config,$pdo] = require dirname(__DIR__).'/src/bootstrap.php';
$service=new AuthorBookListings($pdo);
$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
$item=$id?$service->visible((int)$id,'',1):[];
if(!$item){http_response_code(404);header('X-Robots-Tag: noindex');header('Content-Type: text/html; charset=UTF-8');echo '<!doctype html><html lang="fr"><meta charset="utf-8"><title>Livre introuvable</title><main><h1>Fiche introuvable</h1><p>Ce livre n’est pas publié ou n’existe plus.</p><a href="/livres-auteurs.php">Retour aux livres d’auteurs</a></main></html>';exit;}
$book=$item[0];
$e=static fn(mixed $v):string=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$price=number_format((int)$book['price_cents']/100,2,',',' ').' €';
$format=['paper'=>'Papier','ebook'=>'Numérique','both'=>'Papier et numérique'][$book['book_format']]??'Livre';
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $e($book['title']) ?> — <?= $e($book['author_name']) ?> — Librairie universelle</title>
<meta name="description" content="<?= $e(mb_strimwidth($book['description'],0,155,'…','UTF-8')) ?>">
<link rel="canonical" href="https://librairie.lepotager.org/livre-auteur.php?id=<?= (int)$book['id'] ?>">
<link rel="stylesheet" href="/assets/css/app.css"><link rel="stylesheet" href="/assets/css/author-listings.css">
</head><body><a class="skip-link" href="#main">Aller au contenu</a>
<?php $currentNav='authorbooks'; require dirname(__DIR__).'/src/views/site-header.php'; ?>
<main id="main" class="author-books-page">
<section class="page-hero compact"><p class="eyebrow">Fiche vérifiée par la Librairie</p>
<h1><?= $e($book['title']) ?></h1>
<?php if(!empty($book['subtitle'])): ?><p class="lede"><?= $e($book['subtitle']) ?></p><?php endif; ?>
<p>Par <strong><?= $e($book['author_name']) ?></strong></p>
<a class="text-link" href="/livres-auteurs.php">← Retour aux livres d’auteurs</a></section>
<section class="content-section author-book-detail">
<div><h2>À propos de ce livre</h2><p class="author-book-description"><?= nl2br($e($book['description'])) ?></p></div>
<aside class="author-book-contact">
<h2>Contacter l’auteur ou le vendeur</h2>
<dl><dt>Format</dt><dd><?= $e($format) ?></dd><dt>Prix annoncé par le vendeur</dt><dd><?= $e($price) ?></dd>
<dt>Statut déclaré</dt><dd><?= $book['seller_type']==='professional'?'Professionnel':'Particulier' ?></dd>
<?php if($book['isbn']): ?><dt>ISBN déclaré</dt><dd><?= $e($book['isbn']) ?></dd><?php endif; ?></dl>
<?php if($book['seller_url']): ?><p><a class="button-link" href="<?= $e($book['seller_url']) ?>" target="_blank" rel="noopener noreferrer nofollow">Ouvrir la page du vendeur ↗</a></p><?php endif; ?>
<?php if($book['public_email']): ?><p><a href="mailto:<?= $e($book['public_email']) ?>">Écrire à l’auteur ou au vendeur</a></p><?php endif; ?>
<p class="field-help">Achat, paiement, expédition, droit de rétractation éventuel et réclamations se traitent directement avec le vendeur. La Librairie ne perçoit pas le paiement et ne garantit ni les prix ni la disponibilité.</p>
</aside></section></main>
<footer><div class="footer-links"><a href="/livres-auteurs.php">Livres indépendants</a><a href="/proposer-livre.php">Proposer mon livre</a></div></footer>
</body></html>
