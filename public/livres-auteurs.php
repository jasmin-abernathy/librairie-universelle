<?php
declare(strict_types=1);
[$config,$pdo] = require dirname(__DIR__).'/src/bootstrap.php';
$service=new AuthorBookListings($pdo);
$q=is_string($_GET['q']??null)?trim(mb_substr($_GET['q'],0,80)):'';
$items=$service->visible(null,$q);
$e=static fn(mixed $v):string=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$price=static fn(int $cents):string=>number_format($cents/100,2,',',' ').' €';
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="description" content="Découvrez les livres présentés par leurs auteurs indépendants et vérifiés par la Librairie. Vente directe : contactez chaque auteur.">
<title>Livres d’auteurs indépendants — Librairie universelle</title>
<link rel="stylesheet" href="/assets/css/app.css?v=20261104-nav2"><link rel="stylesheet" href="/assets/css/author-listings.css?v=20261104-2">
</head><body><a class="skip-link" href="#main">Aller au contenu</a>
<?php $currentNav='authorbooks'; require dirname(__DIR__).'/src/views/site-header.php'; ?>
<main id="main" class="author-books-page">
<section class="page-hero compact"><p class="eyebrow">Livres proposés · vérification humaine</p>
<h1>Les livres des auteurs indépendants</h1>
<p class="lede">Des fiches proposées par les auteurs et examinées avant leur mise en ligne. La Librairie ne vend pas ces livres : l’achat ou la demande se fait directement auprès du vendeur indiqué.</p>
<a class="button-link" href="/proposer-livre.php">Proposer la fiche de mon livre →</a>
</section>
<section class="content-section">
<form class="author-book-search" action="/livres-auteurs.php" method="get">
<label>Rechercher par livre ou auteur <input type="search" name="q" maxlength="80" value="<?= $e($q) ?>" placeholder="Titre ou auteur"></label>
<button type="submit" class="button-link">Rechercher</button>
</form>
<?php if(!$items): ?><p class="note"><?= $q!==''?'Aucun livre publié ne correspond à cette recherche.':'Aucune fiche d’auteur n’est encore publiée. Les propositions attendent une vérification humaine.' ?></p>
<?php else: ?><div class="author-book-grid">
<?php foreach($items as $item): ?><article class="author-book-card">
<p class="eyebrow"><?= $e($item['book_format']==='both'?'Papier et numérique':($item['book_format']==='ebook'?'Numérique':'Papier')) ?> · <?= $e($item['seller_type']==='professional'?'Vendeur professionnel':'Vendeur particulier') ?></p>
<h2><a href="/livre-auteur.php?id=<?= (int)$item['id'] ?>"><?= $e($item['title']) ?></a></h2>
<p><strong><?= $e($item['author_name']) ?></strong></p>
<p><?= $e(mb_strimwidth($item['description'],0,235,'…','UTF-8')) ?></p>
<p><strong><?= $price((int)$item['price_cents']) ?></strong></p>
<p><a href="/livre-auteur.php?id=<?= (int)$item['id'] ?>">Consulter la fiche et contacter le vendeur →</a></p>
</article><?php endforeach; ?>
</div><?php endif; ?>
</section></main>
<footer><div class="footer-links"><a href="/proposer-livre.php">Proposer un livre</a><a href="/diffuser-mon-livre.php">Diffuser auprès des librairies</a></div><p>Les ventes sont conclues directement avec les auteurs.</p></footer>
</body></html>
