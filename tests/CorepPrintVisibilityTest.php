<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/ApprovedPrintPriceSnapshot.php';
require dirname(__DIR__).'/src/MosellePrintGridClient.php';
$assert=static function(bool $ok,string $msg):void{
 if(!$ok){fwrite(STDERR,"Corep: ".$msg."\n");exit(1);}
};
$html=(string)file_get_contents(dirname(__DIR__).'/public/autoedition.php');
$assert(str_contains($html,'Corep Metz — référence locale'),'La carte Corep Metz manque du parcours papier.');
$assert(str_contains($html,'https://www.corep.fr/fr/agence/corep-metz/'),'Adresse Corep manquante.');
$assert(str_contains($html,'autorisation de présenter une grille TTC indicative'),
  'L’accord limité de publication de la grille n’est pas expliqué.');
$assert(str_contains($html,'pas un partenariat commercial'),'Partenariat Corep sur-vendu.');
$assert(str_contains($html,'si ($publicPrintGrids === []'), 'Le repli conditionnel de la grille manque.');
$assert(str_contains($html,'($config[\'moselle_base_url\'] ?? \'\') === \'\''),
  'La grille locale ne doit pas être imposée si un pilote Moselle est configuré.');
$oct=ApprovedPrintPriceSnapshot::grids('2026-10-19');
$assert(count($oct)===1 && ($oct[0]['printer']??'')==='Corep Metz','Grille Corep autorisée absente au 19 octobre.');
$prices=$oct[0]['amounts_cents']??[];
$assert(count($prices)===3 && array_sum(array_map('count',$prices))===12,'12 montants TTC absents.');
$assert(ApprovedPrintPriceSnapshot::grids('2026-11-18')===[],
  'Ancienne grille doit être masquée après sa date de réexamen.');
$assert(MosellePrintGridClient::normalizeResponse(['grids'=>[]])===[],
  'Une grille retirée par l’API ne doit pas être réinjectée.');
echo "Corep : présence dans le parcours, 12 montants datés, absence de faux partenariat — OK\n";
