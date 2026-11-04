<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$check = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, '[Liens externes] '.$message."\n");
        exit(1);
    }
};
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
    $root.'/public', FilesystemIterator::SKIP_DOTS
));
$examined = 0;
foreach ($files as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') continue;
    $source = (string)file_get_contents($file->getPathname());
    preg_match_all('~<a\b[\s\S]*?</a\s*>~i', $source, $anchors);
    foreach ($anchors[0] as $anchor) {
        if (!preg_match('~\bhref\s*=\s*["\']https?://~i', $anchor)) continue;
        $check((bool)preg_match('~\btarget\s*=\s*["\']_blank["\']~i', $anchor),
            $file->getFilename().' : lien externe sans target="_blank"');
        $check((bool)preg_match('~\brel\s*=\s*["\'][^"\']*\bnoopener\b[^"\']*\bnoreferrer\b~i', $anchor),
            $file->getFilename().' : lien externe sans rel="noopener noreferrer"');
        $examined++;
    }
}
$mustBeExternal = [
    'public/ebooks.php' => ['$offer[\'url\']'],
    'public/work.php' => ['$offer[\'url\']', '$sourceUrl', '$config[\'moselle_base_url\']'],
    'public/autoedition.php' => ['$config[\'atelier_epub_url\']'],
    'public/livre-auteur.php' => ['$book[\'seller_url\']'],
    'public/admin/livres-auteurs.php' => ['$item[\'seller_url\']'],
];
foreach ($mustBeExternal as $path => $tokens) {
    $s=(string)file_get_contents($root.'/'.$path);
    preg_match_all('~<a\b[\s\S]*?</a\s*>~i', $s, $anchors);
    foreach ($tokens as $token) {
        $matching = array_values(array_filter($anchors[0],
            static fn(string $anchor): bool => str_contains($anchor, 'href=') && str_contains($anchor,$token)));
        $check($matching !== [], $path.' : lien externe dynamique non trouvé ('.$token.')');
        foreach ($matching as $a) {
            $check(str_contains($a,'target="_blank"'), $path.' : '.$token.' doit ouvrir un nouvel onglet');
            $check(str_contains($a,'noopener noreferrer'), $path.' : '.$token.' doit être protégé');
        }
    }
}
// Inspecter chaque ancre JS qui reçoit une URL externe, et non la présence
// de simples attributs target/rel ailleurs dans le fichier.
$javascriptExternalAnchors = [
    'public/assets/js/bookstores.js' => ['link'],
    'public/assets/js/discovery.js' => ['link', 'download'],
];
foreach ($javascriptExternalAnchors as $path => $names) {
    $s = (string)file_get_contents($root.'/'.$path);
    foreach ($names as $name) {
        $pattern = '~\\bconst\\s+'.preg_quote($name, '~').'\\s*=\\s*document\\.createElement\\([\\'"]a[\\'"]\\)\\s*;(.*?)(?:\\b(?:row|container)\\.appendChild\\('.preg_quote($name, '~').'\\)|\\b'.preg_quote($name, '~').'\\.textContent\\s*=)~s';
        $check((bool)preg_match($pattern, $s, $matches), $path.' : ancre externe '. $name .' introuvable');
        $block = $matches[1];
        $check((bool)preg_match('~\\b'.preg_quote($name, '~').'\\.href\\s*=~', $block),
            $path.' : URL externe absente pour '.$name);
        $check((bool)preg_match('~\\b'.preg_quote($name, '~').'\\.target\\s*=\\s*[\\'"]_blank[\\'"]~', $block),
            $path.' : '.$name.' doit ouvrir un nouvel onglet');
        $check((bool)preg_match('~\\b'.preg_quote($name, '~').'\\.rel\\s*=\\s*[\\'"]noopener noreferrer[\\'"]~', $block),
            $path.' : '.$name.' doit protéger le nouvel onglet');
    }
}
$ebook=(string)file_get_contents($root.'/public/ebooks.php');
$check(str_contains($ebook,"$"."offers = $"."query !== '' ? EbookStorefront::browse("),
    'La page ebooks doit attendre une vraie recherche avant de présenter des offres.');
$check(str_contains($ebook,"<?php if ($"."query !== ''): ?>"),
    'La section de résultats ne doit pas s’ouvrir sans recherche.');
$seed=(string)file_get_contents($root.'/database/seed.sql');
$check(!str_contains($seed,'INSERT OR IGNORE INTO offers'),
    'Le catalogue injecte encore des offres historiques par défaut.');
$mvp=(string)file_get_contents($root.'/tests/MvpWorkflowTest.php');
$check(!str_contains($mvp,'FakeOfferSource') && !str_contains($mvp,'Partenaire test'),
    'Le faux partenaire est toujours construit par le test CI.');
echo "Liens externes : $examined URLs statiques + dynamiques, nouvel onglet et catalogue sans démo : OK\n";
