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
    '/dossier-impression.php',
    '/diffuser-mon-livre.php',
    '/prestations-auteurs.php',
    '/ebooks.php',
    '/sans-ia.php',
    '79 € HT',
    '179 € HT',
    'stocks en direct',
    'sans prétendre connaître leurs stocks en direct',
    'Les dépôts publics sont encore fermés',
    'une fiche est créée',
    'vente intégrée',
] as $needle) {
    // Case and accents matter: keep outward-facing promises regression-tested.
    $check(str_contains($page, $needle), 'Mention attendue absente : ' . $needle);
}
$check(str_contains($page, 'n\'annonçons ni stock en temps réel, ni vente intégrée, ni partenariat contractuel'), 'Pas de limites explicites des promesses commerciales.');
$check(str_contains($page, 'ne signifie pas encore pouvoir vendre directement'), 'Une fiche ne doit pas promettre la vente.');
$check(str_contains($page, "nom de travail"), 'Nom provisoire masqué.');
$check(str_contains($css, '@media(max-width:640px)'), 'Mise en page mobile absente.');
$check(!str_contains($page, '<script src="https://'), 'Ressource externe indésirable.');
echo "Page Le projet : vision locale, parcours auteurs, limites réelles et responsive OK\n";
