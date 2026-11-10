<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$read=static fn(string $path):string=>(string)file_get_contents($root.'/'.$path);
$page=$read('public/publier-et-lire-local.php');
$home=$read('public/index.php');
$project=$read('public/projet.php');
$outreach=$read('public/diffuser-mon-livre.php');
$navigation=$read('src/views/site-header.php');
$sitemap=$read('public/sitemap.php');
$css=$read('public/assets/css/publier-local.css');
$corep=json_decode($read('data/corep-metz-approved.json'),true,512,JSON_THROW_ON_ERROR);

$check=static function(bool $success,string $message):void{
  if(!$success){fwrite(STDERR,"[Local & droits] ".$message."\n");exit(1);}
};
foreach([
 'Votre livre reste le vôtre',
 'Amazon ne devient pas non plus propriétaire',
 'Un livre papier publié avec KDP peut être vendu également',
 'ISBN gratuit KDP est réservé à KDP',
 'distribution étendue',
 'KDP Select',
 'papier ou EPUB',
 '3 %',
 'convention',
 'une librairie',
 'dépôt-vente',
 'pas la promesse',
 'La mise en rayon ne se décrète pas',
 'sans garantir',
 'Aucun chiffre de CO₂ par livre',
 'Pas forcément meilleur',
 'Pas de nouvelle livraison',
 'pas un devis',
 '/conditions-generales.php',
 'https://www.legifrance.gouv.fr/',
 'https://kdp.amazon.com/fr_FR/help/topic/G201834340',
 'https://www.ademe.fr/',
] as $needle){
 $check(str_contains($page,$needle),'Mention de transparence manquante : '.$needle);
}
$check(str_contains($page,"date('Y-m-d') <= (string)") &&
 str_contains($page,"$"."corepCurrent") &&
 str_contains($page,'prix d\'impression locale en cours de réexamen')===false &&
 str_contains($page,'Prix d\'impression locale en cours de réexamen'),
 'Les prix Corep doivent disparaître après la date de réexamen.');
$check(($corep['tax_included']??false)===true
 && ($corep['amounts_cents']['120']['10']??null)===12082
 && ($corep['amounts_cents']['120']['50']??null)===34705
 && ($corep['amounts_cents']['120']['100']??null)===58484,
 'La comparaison doit reposer sur la grille Corep réellement autorisée.');
$kdpPrint=.75+120*.012;
$kdpRoyalty=.60*(15/1.055)-$kdpPrint;
$check(abs($kdpPrint-2.19)<.00001
 && abs($kdpRoyalty-6.3408)<.001
 && str_contains($page,'$kdpPrintCost = .75 + 120 * .012')
 && str_contains($page,'$kdpPaperbackRoyalty = (.60 * ($paperPriceTtc / 1.055)) - $kdpPrintCost'),
 'KDP standard France 120 pages : prix et redevance incorrects.');
$check(str_contains($page,'0,75 € fixe + 120 × 0,012 €')
 && str_contains($page,'vente directe')
 && str_contains($page,'l\'avance')
 && str_contains($page,'invendus'),
 'La comparaison économique doit distinguer tirage, avance de stock et commission.');
$check(str_contains($page,'70 %')
 && str_contains($page,'35 %')
 && str_contains($page,'avant frais de livraison numérique KDP')
 && str_contains($page,'montant restant à l\'auteur ou vendeur'),
 'EPUB : conditions et limites des deux taux KDP manquantes.');
$check(str_contains($outreach,'id="parcours-librairies"')
 && str_contains($outreach,'remise commerciale')
 && str_contains($outreach,'3 %')
 && str_contains($outreach,'id="fiche-libraire"')
 && str_contains($page,'/diffuser-mon-livre.php#fiche-libraire'),
 'Parcours concret des librairies ou distinction commissions manquant.');
$check(str_contains($home,'id="home-local-title"')
 && str_contains($home,'href="/publier-et-lire-local.php"')
 && str_contains($project,'/publier-et-lire-local.php')
 && str_contains($navigation,'Vos droits et le local')
 && str_contains($sitemap,"'/publier-et-lire-local.php'"),
 'L\'argumentaire doit être visible depuis l\'accueil, le projet, la navigation et le sitemap.');
$check(str_contains($css,'@media(max-width:640px)')
 && str_contains($css,'.local-table-scroll{max-width:100%;overflow-x:auto')
 && str_contains($css,'.home-local-options{display:grid')
 && !str_contains($page,'<script src="https://'),
 'Mise en page mobile, comparaison accessible ou sobriété manquante.');
$check(!str_contains($page,'amazon.com/dp/')
 && !str_contains($page,'affiliate')
 && !str_contains($page,'émissions évitées par livre')
 && !str_contains($page,'plus écologique qu\'Amazon'),
 'Ne pas publier de publicité Amazon ou de résultat carbone inventé.');
echo "Audit local : droits papier et EPUB, parcours librairies, marges KDP, devis Corep, prudence écologique OK\n";
