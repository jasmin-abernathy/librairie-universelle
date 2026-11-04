<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/Isbn.php';
require dirname(__DIR__).'/src/AuthorBookListings.php';
$pdo=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$pdo->exec('PRAGMA foreign_keys=ON');
$pdo->exec((string)file_get_contents(dirname(__DIR__).'/database/schema.sql'));
$service=new AuthorBookListings($pdo);
$assert=static function(bool $ok,string $why):void{if(!$ok){fwrite(STDERR,$why."\n");exit(1);}};
$valid=[
'title'=>'Un livre indépendant','author_name'=>'Autrice publique','subtitle'=>'',
'description'=>'Un livre descriptif de test dont le synopsis est suffisamment long pour le catalogue public.',
'book_format'=>'both','isbn'=>'','price'=>'15,50','seller_type'=>'individual',
'private_email'=>'secret@example.org','public_email'=>'public@example.org',
'seller_url'=>'https://example.org/acheter','rights_confirmed'=>'1',
'public_contact_confirmed'=>'1','seller_responsibility_confirmed'=>'1'];
$id=$service->submit($valid);
$assert($id>0,'Proposition non enregistrée');
$assert($service->visible()===[],'Fiche en attente déjà publique');
$assert($service->counts()['pending']===1,'File de modération absente');
$assert($service->moderate($id,'publish','Relecture humaine'),'Approbation impossible');
$public=$service->visible($id);
$assert(count($public)===1,'Fiche approuvée invisible');
$assert((int)$public[0]['price_cents']===1550,'Prix incorrect');
$assert(!array_key_exists('private_email',$public[0]),'E-mail de suivi exposé publiquement');
$assert(!array_key_exists('review_note',$public[0]),'Note privée exposée');
$assert($public[0]['seller_url']==='https://example.org/acheter','Contact direct absent');
$assert(count($service->visible(null,'Autrice'))===1,'Recherche impossible');
$assert($service->moderate($id,'hide'),'Masquage impossible');
$assert($service->visible()===[],'Fiche masquée toujours publique');
$assert($service->moderate($id,'publish'),'Republication impossible');
$assert($service->moderate($id,'reject'),'Refus impossible');
$assert($service->visible()===[],'Fiche refusée toujours publique');
$missingContact=$valid;$missingContact['public_email']='';$missingContact['seller_url']='';
try{$service->submit($missingContact);$assert(false,'Contact manquant autorisé');}
catch(InvalidArgumentException $e){}
$unsafe=$valid;$unsafe['seller_url']='javascript:alert(1)';
try{$service->submit($unsafe);$assert(false,'URL dangereuse acceptée');}
catch(InvalidArgumentException $e){}
$noRights=$valid;$noRights['rights_confirmed']='';
try{$service->submit($noRights);$assert(false,'Droits non confirmés acceptés');}
catch(InvalidArgumentException $e){}
$linkOnly=$valid;$linkOnly['public_email']='';$linkOnly['private_email']='another@example.org';
$service->submit($linkOnly);
$assert($service->counts()['pending']===1,'Contact par URL seule refusé');
$root=dirname(__DIR__);
$form=(string)file_get_contents($root.'/public/proposer-livre.php');
$admin=(string)file_get_contents($root.'/public/admin/livres-auteurs.php');
$legacy=(string)file_get_contents($root.'/public/autoedition.php');
$index=(string)file_get_contents($root.'/public/index.php');
$listing=(string)file_get_contents($root.'/public/livres-auteurs.php');
$detail=(string)file_get_contents($root.'/public/livre-auteur.php');
$assert(str_contains($form,'Csrf::validate('),'CSRF absent');
$assert(str_contains($form,'name="website"'),'Anti-spam absent');
$assert(!str_contains($form,'type="file"'),'Le formulaire reçoit encore des manuscrits');
$assert(str_contains($admin,'AdminAuth::enforce('),'Administration non protégée');
$assert(str_contains($admin,'value="publish"'),'Approbation manuelle absente');
$assert(str_contains($legacy,'http_response_code(410)'),'Ancien dépôt non désactivé');
$assert(!str_contains($legacy,'$service->submit($_POST, $_FILES)'),'Ancienne réception de fichiers encore active');
$assert(str_contains($index,'/livres-auteurs.php'),'Catalogue non relié à l’accueil');
$assert(str_contains($listing,'$service->visible('),'Catalogue non limité aux publications');
$assert(str_contains($detail,'$service->visible('),'Fiche non publiée accessible');
echo "Fiches d’auteurs : dépôt sans fichiers, modération, confidentialité et accès public OK\n";
