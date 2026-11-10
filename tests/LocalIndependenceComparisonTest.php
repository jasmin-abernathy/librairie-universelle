<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$read=static fn(string $path):string=>(string)file_get_contents($root.'/'.$path);
$page=$read('public/comparer-diffusion-livre.php');
$overview=$read('public/publier-et-lire-local.php');
$combined=$page."\n".$overview;
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
 $check(str_contains($combined,$needle),'Mention de transparence manquante : '.$needle);
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
 && str_contains($page,'Montant restant à l\'auteur ou vendeur'),
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
$calculator=$read('public/assets/js/amazon-local.js');
$calculatorCss=$read('public/assets/css/amazon-local.css');
$check(str_contains($page,'id="simulateur-marges"') && str_contains($page,'id="amazon-local-calculator"')
    && str_contains($page,'id="amazon-comparison-status"') && str_contains($page,'id="amazon-comparison-results"'),
    'Simulateur de comparaison des circuits absent ou inaccessible.');
$check(str_contains($page,'/assets/css/amazon-local.css?v=20261110-2')
    && str_contains($page,'/assets/js/amazon-local.js?v=20261110-2')
    && str_contains($calculator,'priceExVat=publicPrice/1.055')
    && str_contains($calculator,'commission=publicPrice*0.03'),
    'Assets du simulateur ou formules TVA / commission absentes.');
$check(str_contains($calculatorCss,'@media(max-width:620px)')
    && str_contains($calculatorCss,':focus-visible')
    && str_contains($page,'sans compte ni transmission de données'),
    'Responsive, focus clavier ou confidentialité du simulateur manquants.');
$check(str_contains($page,'https://kdp.amazon.com/fr_FR/help/topic/G7BBN68RYX5UMDZF'),
    'Lien officiel sur les exemplaires auteur et les épreuves absent.');

$guideCss=$read('public/assets/css/circuits-guide.css');
$check(str_contains($overview,'id="circuits"') && str_contains($overview,'id="librairies"')
 && str_contains($overview,'id="chiffres"') && str_contains($overview,'href="/comparer-diffusion-livre.php#droits-kdp"'),
 'Le parcours court doit conserver ses entrées et son lien KDP.');
$check(!str_contains($overview,'id="amazon-local-calculator"') && !str_contains($overview,'amazon-local.js')
 && !str_contains($overview,'id="ecologie"') && !str_contains($overview,'id="enjeux-sociaux"'),
 'Le parcours de découverte ne doit pas contenir les outils détaillés.');
$check(str_contains($page,'id="droits-kdp"') && str_contains($page,'id="chiffres"')
 && str_contains($page,'id="ecologie"') && str_contains($page,'id="enjeux-sociaux"')
 && str_contains($page,'id="sources"') && str_contains($page,'class="local-guide-jump"'),
 'Guide : sommaire et rubriques essentielles manquants.');
$check(str_contains($page,'id="exemple-papier"') && str_contains($page,'id="exemple-epub"')
 && substr_count($page,'<summary>')===2 && str_contains($guideCss,'@media(max-width:640px)')
 && str_contains($guideCss,':focus-visible'),
 'Guide : chiffres détaillés non repliables ou styles accessibles absents.');
$check(str_contains($page,'83 ouvertures et 85 fermetures')
 && str_contains($page,'3 200 librairies et 14 000 emplois')
 && str_contains($page,"Amazon France Logistique de 32 millions d'euros")
 && str_contains($page,'13 mai 2026')
 && str_contains($page,'aucune obligation morale')===false
 && str_contains($page,"n'est donc pas une obligation morale"),
 'Rubrique sociale : faits documentés et nuances manquants.');
$check(str_contains($page,'31 % des achats de livres imprimés en 1993')
 && str_contains($page,'2 % des achats en 2002 à 20 % en 2025')
 && str_contains($page,'435,1 millions en 2019 à 419,6 millions en 2025')
 && str_contains($page,'10 % du marché français du livre en valeur')
 && str_contains($page,'Les chiffres détaillés d\'Amazon ne sont pas publics.')
 && str_contains($page,'https://www.sne.fr/economie/chiffres-cles/')
 && str_contains($page,'https://www.senat.fr/rap/a20-651/a20-6510.html'),
 'Manque la perspective historique ou la précaution sur la part Amazon.');
$workStart=strpos($page,'Cadences élevées, recours à l\'intérim');
$surveillanceStart=strpos($page,'À cela s\'ajoute la surveillance des performances.');
$check($workStart!==false && $surveillanceStart!==false && $workStart<$surveillanceStart,
 'Présenter les conditions de travail avant la surveillance / sanction CNIL.');
foreach (['centrenationaldulivre.fr','questions.assemblee-nationale.fr','www.cnil.fr','www.conseil-etat.fr','www.culture.gouv.fr'] as $domain) {
 $check(str_contains($page,'href="https://'.$domain),'Source sociale absente : '.$domain);
}
$check(str_contains($overview,'href="/comparer-diffusion-livre.php#enjeux-sociaux"')
 && str_contains($read('public/index.php'),'href="/comparer-diffusion-livre.php#chiffres"')
 && str_contains($read('public/autoedition.php'),'/comparer-diffusion-livre.php#droits-kdp')
 && str_contains($sitemap,"'/comparer-diffusion-livre.php'"),
 'Maillage du guide détaillé insuffisant.');
echo "Audit local : guide séparé, chiffres, impact social, sources et parcours : OK\n";
