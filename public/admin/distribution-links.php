<?php
declare(strict_types=1);
[$config,$pdo]=require dirname(__DIR__,2).'/src/bootstrap.php';
AdminAuth::enforce($config);
Csrf::token();
header('X-Robots-Tag: noindex,nofollow');
header('Cache-Control: no-store');
$links=new DistributionLinks($pdo);
$h=static fn(mixed $s):string=>htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!Csrf::validate($_POST['csrf_token']??null)){
        $error='Jeton de session invalide, opération non enregistrée.';
    }else{
        try{
            if(($_POST['action']??'add')==='hide'){
                $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
                if(!$id)throw new InvalidArgumentException('Lien invalide.');
                $links->hide((int)$id);
            }elseif(($_POST['action']??'')==='add'){
                $links->add($_POST);
            }else{
                throw new InvalidArgumentException('Action inconnue.');
            }
            header('Location: /admin/distribution-links.php?updated=1',true,303);
            exit;
        }catch(InvalidArgumentException|PDOException $e){
            $error=$e->getMessage();
        }
    }
}
$rows=$links->recent(150);
$editions=$pdo->query(<<<'SQL'
SELECT e.id, e.isbn13, e.medium,
       COALESCE(NULLIF(e.title,''), w.title) AS title
FROM editions e JOIN works w ON w.id=e.work_id
ORDER BY e.id DESC LIMIT 200
SQL)->fetchAll(PDO::FETCH_ASSOC);
$authors=$pdo->query("SELECT id,title,author_name FROM author_book_listings WHERE status='published' ORDER BY id DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
?><!doctype html><html lang="fr"><head><meta charset="utf-8">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Rattacher plusieurs plateformes — Administration</title>
<link rel="stylesheet" href="/assets/css/app.css">
</head><body><a class="skip-link" href="#main">Aller au contenu</a>
<header class="site-header"><a class="brand" href="/admin/">Administration</a>
<nav class="site-nav"><a href="/admin/">Tableau de bord</a><a href="/" target="_blank" rel="noopener noreferrer">Site public ↗</a></nav></header>
<main id="main" class="page-shell">
<section class="content-section">
<p class="eyebrow">Catalogue multicanal</p>
<h1>Un livre, plusieurs plateformes</h1>
<p>Associer plusieurs liens <strong>réels, vérifiés et non exclusifs</strong> à une même édition ou une fiche d’auteur déjà publiée. Ce référencement ne constitue ni un partenariat technique, ni une vérification de stock, de prix ou de livraison. Aucune adresse de fiche n’est fabriquée automatiquement.</p>
<?php if($error!==''): ?><p class="error-box" role="alert"><?= $h($error) ?></p><?php endif; ?>
<?php if(isset($_GET['updated'])): ?><p class="success-box" role="status">Lien enregistré ou masqué.</p><?php endif; ?>
</section>
<section class="content-section">
<h2>Ajouter une destination vérifiée</h2>
<form method="post" action="/admin/distribution-links.php" style="display:grid;gap:12px;max-width:720px">
<input type="hidden" name="csrf_token" value="<?= $h(Csrf::token()) ?>">
<input type="hidden" name="action" value="add">
<label>Type de fiche
<select name="scope" required><option value="edition">Édition bibliographique</option><option value="author_book">Livre proposé par son auteur</option></select></label>
<label>ID de l'édition ou de la fiche publiée
<input type="number" name="book_id" min="1" required placeholder="Identifiant figurant ci-dessous"></label>
<p class="field-help">Les identifiants sont distincts suivant le type de fiche. Aucun rapprochement automatique sur un simple titre.</p>
<details><summary>Afficher les identifiants disponibles</summary>
<h3>Éditions bibliographiques (200 dernières)</h3>
<ul><?php foreach($editions as $item): ?><li>ID <?= (int)$item['id'] ?> · <?= $h($item['title']) ?> · <?= $h($item['medium']) ?><?= $item['isbn13']?' · '.$h($item['isbn13']):'' ?></li><?php endforeach; ?></ul>
<h3>Fiches d'auteurs publiées</h3>
<ul><?php foreach($authors as $item): ?><li>ID <?= (int)$item['id'] ?> · <?= $h($item['title']) ?> — <?= $h($item['author_name']) ?></li><?php endforeach; ?></ul>
</details>
<label>Plateforme ou librairie
<input type="text" name="platform" required maxlength="100" placeholder="Ex. leslibraires.fr"></label>
<label>Nature du circuit
<select name="channel" required>
<option value="independent_portal">Réseau de librairies indépendantes</option>
<option value="bookseller">Librairie ou détaillant</option>
<option value="publisher">Éditeur / diffuseur</option>
<option value="author">Site de l'auteur</option>
<option value="other">Autre distributeur</option>
</select></label>
<label>Format concerné
<select name="medium" required>
<option value="paper">Papier</option>
<option value="ebook">Ebook</option>
<option value="audio">Audio</option>
<option value="other">Autre</option>
</select></label>
<label>Adresse HTTPS de la <strong>fiche réelle du livre</strong>
<input type="url" name="url" required maxlength="1000" placeholder="https://exemple.fr/livre/…"></label>
<label class="check-row"><input type="checkbox" name="verified" value="1" required> J'ai ouvert le lien, vérifié qu'il renvoie au bon livre et au bon format, sans en déduire la disponibilité ni un partenariat.</label>
<button type="submit" class="button-link">Ajouter cette destination</button>
</form>
</section>
<section class="content-section">
<h2>Liens enregistrés (<?= count($rows) ?> affichés)</h2>
<?php if(!$rows): ?><p>Aucune destination saisie ; aucune plateforme n'est présentée comme intégrée.</p><?php else: ?>
<div class="table-wrap"><table><thead><tr><th>Livre</th><th>Plateforme</th><th>Format</th><th>Contrôle</th><th>Destination</th><th>Action</th></tr></thead><tbody>
<?php foreach($rows as $l): ?><tr>
<td><?= $l['edition_id']?'Édition n°'.(int)$l['edition_id']:'Fiche auteur n°'.(int)$l['author_book_id'] ?>
<br><?= $h($l['work_title']??$l['author_book_title']??$l['edition_title']??'') ?></td>
<td><?= $h($l['platform']) ?><br><small><?= $h($l['channel']) ?></small></td>
<td><?= $h($l['medium']) ?></td>
<td><?= $h($l['status']) ?><br><small><?= $h($l['checked_at']) ?></small></td>
<td><a href="<?= $h($l['url']) ?>" target="_blank" rel="noopener noreferrer">Ouvrir ↗</a></td>
<td><?php if($l['status']==='checked'): ?><form method="post" action="/admin/distribution-links.php">
<input type="hidden" name="csrf_token" value="<?= $h(Csrf::token()) ?>">
<input type="hidden" name="action" value="hide"><input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
<button type="submit">Masquer</button></form><?php else: ?>Masqué<?php endif; ?></td>
</tr><?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</section></main></body></html>
