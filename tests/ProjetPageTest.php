<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$page = (string) file_get_contents($root . '/public/projet.php');
$css = (string) file_get_contents($root . '/public/assets/css/projet.css');
$check = static function (bool $ok, string $message): void {
    if (!$ok) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
};
foreach ([
    'Librairie universelle',
    'la proximité le permet',
    'Metz et en Moselle',
    '/autoedition.php',
    '/proposer-livre.php',
    '/dossier-impression.php',
    '/diffuser-mon-livre.php',
    '/prestations-auteurs.php',
    '/ebooks.php',
    '/sans-ia.php',
    '79 € HT',
    '179 € HT',
    'stocks en direct',
    'sans prétendre connaître leurs stocks en direct',
    'Les manuscrits ne sont jamais envoyés',
    'la publication exige une convention signée',
    'vente intégrée',
    'DOCX, un ODT ou un EPUB sans DRM',
    'Corep Metz',
    'Bookelis',
    'sept formats',
    'gabarit officiel',
    'convention signée',
    '3 %',
    '/conditions-generales.php',
] as $needle) {
    // Case and accents matter: keep outward-facing promises regression-tested.
    $check(str_contains($page, $needle), 'Mention attendue absente : ' . $needle);
}
$check(str_contains($page, 'n\'annonçons ni stock en temps réel, ni vente intégrée, ni partenariat contractuel'), 'Pas de limites explicites des promesses commerciales.');
$check(mb_stripos($page, "la Librairie n'encaisse pas le prix du livre") !== false
    && mb_stripos($page, 'Le vendeur gère la commande, le paiement et la livraison') !== false,
    'Une fiche ne doit pas promettre de paiement intégré.');
$check(str_contains($page, "nom de travail"), 'Nom provisoire masqué.');
$check(!str_contains($page, 'Amazon') && !str_contains($page, 'Fnac'),
    'La présentation du projet ne doit pas faire la promotion des grandes enseignes.');
$check(mb_stripos($page, 'aucun frais n\'est dû pour un simple contact') !== false
    && mb_stripos($page, "la Librairie n'encaisse pas le prix du livre") !== false,
    'La commission doit être conditionnée à une vente réelle, sans simuler un paiement intégré.');
$check(str_contains($page, 'convention signée de commission à 3 %')
    && str_contains($page, 'papier ou EPUB'),
    'La signature préalable doit aussi couvrir le référencement des EPUB indépendants.');
$check(str_contains($page, 'couverture complète avec tranche')
    && str_contains($page, 'deux faces de couverture séparées'),
    'Ne pas confondre les sorties Corep et Bookelis.');
$check(str_contains($css, '@media(max-width:640px)'), 'Mise en page mobile absente.');
$check(!str_contains($page, '<script src="https://'), 'Ressource externe indésirable.');
echo "Page Le projet : vision locale, parcours auteurs, limites réelles et responsive OK\n";
