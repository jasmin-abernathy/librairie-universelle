<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/src/DistributionLinks.php';

$check=static function(bool $ok,string $message):void {
    if(!$ok){fwrite(STDERR,"[DistributionLinks] ".$message."\n");exit(1);}
};
$pdo=new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$pdo->exec(file_get_contents(dirname(__DIR__).'/database/schema.sql'));
$pdo->exec("INSERT INTO works(id,title) VALUES(1,'Livre de test')");
$pdo->exec("INSERT INTO editions(id,work_id,isbn13,title,medium) VALUES(10,1,'9782070360024','Version papier','paper')");
$pdo->exec("INSERT INTO editions(id,work_id,isbn13,title,medium) VALUES(11,1,'9782070360031','Version ebook','ebook')");
$pdo->exec("INSERT INTO author_book_listings(id,status,title,author_name,description,book_format,price_cents,seller_type,private_email,public_email,rights_confirmed,public_contact_confirmed,seller_responsibility_confirmed) VALUES(5,'published','Titre auteur','Auteur','Texte test','paper',1500,'individual','private@example.org','public@example.org',1,1,1)");
$pdo->exec("INSERT INTO author_book_listings(id,status,title,author_name,description,book_format,price_cents,seller_type,private_email,public_email,rights_confirmed,public_contact_confirmed,seller_responsibility_confirmed) VALUES(6,'pending','Titre non publie','Auteur','Texte test','paper',1500,'individual','private@example.org','public@example.org',1,1,1)");
$service=new DistributionLinks($pdo);
$base=['scope'=>'edition','book_id'=>10,'channel'=>'independent_portal','medium'=>'paper','verified'=>'1'];
$service->add($base+['platform'=>'Les libraires','url'=>'https://example.org/produit/9782070360024']);
$service->add($base+['platform'=>'Autre réseau','url'=>'https://books.example.org/oeuvre/10']);
$rows=$service->forWork(1);
$check(count($rows)===2,'Plusieurs liens devraient apparaître pour une même édition.');
$check($rows[0]['platform']==='Autre réseau','Tri neutre inattendu.');
$service->add(['scope'=>'author_book','book_id'=>5,'platform'=>'Partenaire auteur','channel'=>'author','medium'=>'paper','url'=>'https://auteur.example.org/livre','verified'=>'1']);
$check(count($service->forAuthorBook(5))===1,'Fiche auteur non associée.');
$check(count($service->forAuthorBook(6))===0,'Fiche non publiée exposée.');
foreach([
 $base+['platform'=>'Doublon','url'=>'https://example.org/produit/9782070360024'],
 $base+['platform'=>'Local','url'=>'http://localhost/livre'],
 $base+['platform'=>'IP','url'=>'https://127.0.0.1/livre'],
 $base+['platform'=>'Partenariat imaginaire','url'=>'https://example.org/other','verified'=>'0'],
 ['scope'=>'author_book','book_id'=>6,'platform'=>'Un vendeur','channel'=>'author','medium'=>'paper','url'=>'https://auteur.example.org/secret','verified'=>'1']
] as $input){
 $caught=false;
 try{$service->add($input);}catch(InvalidArgumentException $e){$caught=true;}
 $check($caught,'Un lien invalide a été accepté.');
}
$id=(int)$rows[0]['id'];
$service->hide($id);
$check(count($service->forWork(1))===1,'Le lien masqué reste visible.');
$pdo->exec("DELETE FROM editions WHERE id=10");
$check(count($service->forWork(1))===0,'Les liens ne sont pas supprimés avec leur édition.');
$check((int)$pdo->query('SELECT COUNT(*) FROM distribution_links WHERE author_book_id=5')->fetchColumn()===1,'Un autre livre a été modifié.');
echo "Distribution multicanal : plusieurs liens, vérification, validation et suppression OK\n";
