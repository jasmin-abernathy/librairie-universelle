<?php
declare(strict_types=1);

$public = dirname(__DIR__) . '/public';
$icon = '<link rel="icon" href="/favicon.svg" type="image/svg+xml">';
$assert = static function(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, '[Favicon] ' . $message . PHP_EOL);
        exit(1);
    }
};

$assert(is_file($public . '/favicon.svg'), 'Le fichier public /favicon.svg est absent.');
$homepage = (string)file_get_contents($public . '/index.php');
$assert(str_contains($homepage, $icon), 'La favicon de référence ne correspond plus à celle de l’accueil.');

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($public, FilesystemIterator::SKIP_DOTS)
);
$pages = 0;
$heads = 0;
foreach ($files as $file) {
    if (!$file->isFile() || !in_array(strtolower($file->getExtension()), ['php', 'html', 'htm'], true)) {
        continue;
    }
    $source = (string)file_get_contents($file->getPathname());
    if (!preg_match('/<head\b/i', $source)) {
        continue; // Les endpoints JSON, fichiers binaires et réponses CSS n'ont pas de favicon.
    }
    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($public) + 1));
    preg_match_all('~<head\b[^>]*>(.*?)</head\s*>~is', $source, $matches);
    $expectedHeads = preg_match_all('/<head\b/i', $source);
    $assert(count($matches[1]) === $expectedHeads, "$relative : bloc <head> non fermé ou non analysable.");
    foreach ($matches[1] as $number => $head) {
        $count = substr_count($head, $icon);
        $assert($count === 1, "$relative : bloc head #" . ($number + 1) . " avec $count favicon(s) attendue(s) au lieu d’une.");
        preg_match_all('/<link\b[^>]*rel=["\'](?:shortcut\s+)?icon["\'][^>]*>/i', $head, $icons);
        $assert(count($icons[0]) === 1, "$relative : icône différente ou doublon déclaré.");
        $heads++;
    }
    $pages++;
}
$assert($pages >= 17, "Seulement $pages pages détectées : vérifier l’exhaustivité du parcours.");
$assert($heads > $pages, 'Les variantes HTML de prévisualisation et 404 ne sont pas couvertes.');
echo "Favicon : $pages pages PHP/HTML, $heads blocs head, icône d’accueil unique et cohérente : OK\n";
