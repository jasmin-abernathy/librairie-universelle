<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/Isbn.php';
require dirname(__DIR__).'/src/OfficialSiretLookup.php';
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

$officialResponse = [
    'results' => [[
        'siren'=>'123456789','nom_complet'=>'MAISON DES LIVRES TEST',
        'etat_administratif'=>'A',
        'siege'=>['siret'=>'12345678900012','libelle_commune'=>'METZ','etat_administratif'=>'A'],
    ]],
];
$lookup = new OfficialSiretLookup(static function(string $url) use ($officialResponse): array {
    if (!str_contains($url, 'recherche-entreprises.api.gouv.fr/search?q=12345678900012')) {
        throw new RuntimeException('Mauvaise requête officielle.');
    }
    return $officialResponse;
});
$verified = $lookup->search('123 456 789 00012');
$assert($verified['name']==='MAISON DES LIVRES TEST','Dénomination officielle absente');
$assert($verified['siret']==='12345678900012','SIRET trouvé incorrect');
$pro = $valid;
$pro['seller_type']='professional';
$pro['siret']='12345678900012';
$pro['selected_company_siret']='12345678900012';
$pro['private_email']='professionnel@example.org';
$professionalService = new AuthorBookListings($pdo, $lookup);
$proId = $professionalService->submit($pro);
$assert($proId>0,'Professionnel vérifié non accepté');
$statement = $pdo->prepare('SELECT status,siret,registered_company_name,siret_verified_at FROM author_book_listings WHERE id=:id');
$statement->execute([':id'=>$proId]);
$stored=$statement->fetch();
$assert($stored['siret']==='12345678900012','SIRET non enregistré');
$assert($stored['registered_company_name']==='MAISON DES LIVRES TEST','Dénomination du registre non sauvegardée');
$assert($stored['siret_verified_at']!==null,'Date de vérification absente');
$assert($professionalService->visible($proId)===[],'Professionnel visible avant modération');
$assert($professionalService->moderate($proId,'publish'),'Publication humaine du professionnel impossible');
$assert(count($professionalService->visible($proId))===1,'Publication professionnelle non visible');
$missingSiret=$pro;$missingSiret['siret']='';
try {$professionalService->submit($missingSiret);$assert(false,'SIRET professionnel facultatif');}
catch(InvalidArgumentException $e) {}
$unselected=$pro;$unselected['selected_company_siret']='';
try {$professionalService->submit($unselected);$assert(false,'Entreprise non sélectionnée acceptée');}
catch(InvalidArgumentException $e) {}
$forged=$pro;$forged['selected_company_siret']='99999999999999';
try {$professionalService->submit($forged);$assert(false,'Sélection d’une autre entreprise acceptée');}
catch(InvalidArgumentException $e) {}
$invalidSiret=$pro;$invalidSiret['siret']='123';
try {$professionalService->submit($invalidSiret);$assert(false,'SIRET invalide accepté');}
catch(InvalidArgumentException $e) {}
$inactive = new OfficialSiretLookup(static fn(string $url):array => [
    'results'=>[['siren'=>'123456789','nom_complet'=>'FERMÉE','etat_administratif'=>'C']],
]);
try {(new AuthorBookListings($pdo,$inactive))->submit($pro);$assert(false,'Entreprise cessée acceptée');}
catch(DomainException $e) {}
$noResults = new OfficialSiretLookup(static fn(string $url):array => ['results'=>[]]);
try {(new AuthorBookListings($pdo,$noResults))->submit($pro);$assert(false,'SIRET introuvable accepté');}
catch(DomainException $e) {}
$badTransport = new OfficialSiretLookup(static function(string $url):array {throw new RuntimeException('API hors service');});
try {(new AuthorBookListings($pdo,$badTransport))->submit($pro);$assert(false,'Professionnel accepté sans consultation officielle');}
catch(RuntimeException $e) {}
$legacyProfessional=$pdo->prepare("INSERT INTO author_book_listings (title,author_name,description,book_format,price_cents,seller_type,private_email,public_email,rights_confirmed,public_contact_confirmed,seller_responsibility_confirmed) VALUES ('ANCIEN','Autrice','Résumé manuscrit de longueur suffisante pour le test','paper',1200,'professional','historique@example.org','public@example.org',1,1,1)");
$legacyProfessional->execute();
$oldId=(int)$pdo->lastInsertId();
try {$professionalService->moderate($oldId,'publish');$assert(false,'Fiche professionnelle historique sans SIRET publiée');}
catch(InvalidArgumentException $e) {}
$individualWithFakeSiret=$valid;
$individualWithFakeSiret['private_email']='particulier@example.org';
$individualWithFakeSiret['siret']='00000000000000';
$individualWithFakeSiret['selected_company_siret']='00000000000000';
$individualId=$professionalService->submit($individualWithFakeSiret);
$stmt=$pdo->query('SELECT siret FROM author_book_listings WHERE id='.$individualId);
$assert($stmt->fetchColumn()===null,'SIRET particulier enregistré sans raison');

$root=dirname(__DIR__);
$form=(string)file_get_contents($root.'/public/proposer-livre.php');
$admin=(string)file_get_contents($root.'/public/admin/livres-auteurs.php');
$legacy=(string)file_get_contents($root.'/public/autoedition.php');
$index=(string)file_get_contents($root.'/public/index.php');
$listing=(string)file_get_contents($root.'/public/livres-auteurs.php');
$detail=(string)file_get_contents($root.'/public/livre-auteur.php');
$assert(str_contains($form,'Csrf::validate('),'CSRF absent');
$assert(str_contains($form,'name="website"'),'Anti-spam absent');
$assert(str_contains($form,'id="listing-siret"'),'Champ SIRET conditionnel absent');
$assert(str_contains($form,'id="siret-search-button"'),'Recherche officielle absente');
$assert(str_contains($form,'name="selected_company_siret"'),'Choix explicite de la société absent');
$assert(str_contains($form,'author-listing-form.js'),'Script de recherche absent');
$assert(str_contains($form,'class="button-link author-book-submit"'),'Bouton de validation sans le composant visuel du site');
$assert(str_contains($form,'class="button-link secondary" type="button"'),'Bouton de recherche SIRET sans le style secondaire du site');
$assert(str_contains($form,'author-listings.css?v=20261104-2'),'Version du CSS de la fiche non actualisée');
$assert(str_contains($admin,'class="button-link" name="action" value="publish"'),'Publication admin sans style partagé');
$css=(string)file_get_contents($root.'/public/assets/css/author-listings.css');
$assert(str_contains($css,'var(--accent)') && str_contains($css,'@media(max-width:620px)'),'Charte et largeur mobile du bouton absentes');
$assert(!str_contains($form,'type="file"'),'Le formulaire reçoit encore des manuscrits');
$assert(str_contains($admin,'AdminAuth::enforce('),'Administration non protégée');
$assert(str_contains($admin,'value="publish"'),'Approbation manuelle absente');
$assert(str_contains($admin,'registered_company_name'),'Vérification SIRET absente de l’administration');
$assert(str_contains($legacy,'http_response_code(410)'),'Ancien dépôt non désactivé');
$assert(!str_contains($legacy,'$service->submit($_POST, $_FILES)'),'Ancienne réception de fichiers encore active');
$assert(str_contains($index,'/livres-auteurs.php'),'Catalogue non relié à l’accueil');
$assert(str_contains($listing,'$service->visible('),'Catalogue non limité aux publications');
$assert(str_contains($detail,'$service->visible('),'Fiche non publiée accessible');
echo "Fiches d’auteurs : dépôt sans fichiers, modération, confidentialité et accès public OK\n";
