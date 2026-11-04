<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$check = static function (bool $ok, string $message): void {
    if (!$ok) { fwrite(STDERR, "[Navigation] " . $message . "\n"); exit(1); }
};
$render = static function (string $current) use ($root): string {
    $currentNav = $current;
    ob_start();
    require $root.'/src/views/site-header.php';
    return (string)ob_get_clean();
};
$html = $render('propose');
$expected = [
    'Chercher un livre', 'Librairies proches', 'Livres numériques', 'Livres d’auteurs',
    'Pour les auteurs', 'Préparer mon livre', 'Proposer mon livre au catalogue',
    'Contacter des librairies', 'À propos', 'Le projet', 'Pourquoi sans IA ?',
];
$last = -1;
foreach ($expected as $label) {
    $pos = strpos($html, $label);
    $check($pos !== false, "Entrée absente : ".$label);
    $check($pos > $last, "Ordre incorrect : ".$label);
    $last = $pos;
}
$check(str_contains($html, 'id="site-primary-navigation"'), 'ID de navigation absent');
$check(str_contains($html, 'aria-controls="site-primary-navigation"'), 'Bouton non relié au menu');
$check(str_contains($html, 'aria-expanded="false"'), 'État initial du menu non annoncé');
$check(str_contains($html, 'aria-label="Ouvrir le menu"'), 'Nom accessible du burger absent');
$check(str_contains($html, '<svg') && str_contains($html, 'aria-hidden="true"'), 'Icône de menu absente');
$check(str_contains($html, 'data-active-group="true"'), 'Groupe auteur actif non indiqué');
$check(str_contains($html, '/assets/js/site-nav.js?v=20261104-3'), 'JS de navigation absent');
$check(str_contains($html, 'Livres, librairies et auteurs'), 'Sous-titre non actualisé');
$check(!str_contains($html, 'nom de travail'), 'Ancien texte de marque conservé');

$pages = [
    'index.php'=>'search', 'ebooks.php'=>'ebooks', 'autoedition.php'=>'autoedition',
    'diffuser-mon-livre.php'=>'diffusion','livres-auteurs.php'=>'authorbooks',
    'livre-auteur.php'=>'authorbooks','proposer-livre.php'=>'propose',
    'projet.php'=>'projet','sans-ia.php'=>'sans-ia','work.php'=>'ebooks',
    'assistant-papier.php'=>'autoedition','dossier-impression.php'=>'autoedition',
    'prestations-auteurs.php'=>'autoedition','feedback.php'=>null,
    'export-pdfx.php'=>'autoedition',
];
foreach ($pages as $file=>$nav) {
    $s = (string)file_get_contents($root.'/public/'.$file);
    $check(str_contains($s, 'site-header.php'), 'Menu partagé absent de '.$file);
    if($nav!==null) {
        $check(str_contains($s, "'".$nav."'"), 'Repère de navigation absent de '.$file);
    }
    if($file!=='work.php') {
        $check(str_contains($s, '/assets/css/app.css?v=20261104-nav2'), 'Cache CSS non mis à jour dans '.$file);
    }
}
$work = (string)file_get_contents($root.'/public/assets/css/work.css');
$check(str_contains($work,'/assets/css/app.css?v=20261104-nav2'),'La fiche livre ne reprend pas le nouveau CSS');
$style = (string)file_get_contents($root.'/public/assets/css/app.css');
$check(str_contains($style,'@media (max-width: 1120px)'), 'Rupture responsive manquante');
$check(str_contains($style,'.site-nav-ready .site-header[data-site-navigation] .site-nav.is-open'), 'Ouverture mobile manquante');
$check(str_contains($style,'min-height: 44px'), 'Taille des cibles tactiles insuffisante');
$check(str_contains($style,'max-height: min(78dvh, 39rem)'), 'Défilement du menu mobile absent');
$check(str_contains($style,':focus-visible'), 'État de focus absent');
$check(str_contains($style,'prefers-reduced-motion'), 'Réduction des animations absente');
echo "Navigation : ordre, libellés, groupes, liens, assets et responsive OK\n";
