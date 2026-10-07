<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/EpubPrintSource.php';

if (!class_exists('ZipArchive') || !class_exists('DOMDocument')) {
    fwrite(STDERR, "ZipArchive et DOM sont requis pour la composition papier.\n");
    exit(1);
}

$tmp = tempnam(sys_get_temp_dir(), 'librairie-print-');
if ($tmp === false) throw new RuntimeException('Impossible de créer le fichier EPUB de test.');
$epub = $tmp . '.epub';
rename($tmp, $epub);

$zip = new ZipArchive();
if ($zip->open($epub, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Impossible de créer l’EPUB de test.');
$zip->addFromString('mimetype', 'application/epub+zip');
$zip->addFromString('META-INF/container.xml', '<?xml version="1.0"?><container xmlns="urn:oasis:names:tc:opendocument:xmlns:container"><rootfiles><rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml"/></rootfiles></container>');
$zip->addFromString('OEBPS/content.opf', '<?xml version="1.0" encoding="UTF-8"?><package xmlns="http://www.idpf.org/2007/opf" version="3.0"><metadata xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:title>Livre de test</dc:title></metadata><manifest><item id="c1" href="c1.xhtml" media-type="application/xhtml+xml"/><item id="c2" href="c2.xhtml" media-type="application/xhtml+xml"/></manifest><spine><itemref idref="c1"/><itemref idref="c2"/></spine></package>');
$zip->addFromString('OEBPS/c1.xhtml', '<?xml version="1.0" encoding="UTF-8"?><html xmlns="http://www.w3.org/1999/xhtml"><body><h1>Premier chapitre</h1><p>Un premier paragraphe.</p><script>alert(1)</script></body></html>');
$zip->addFromString('OEBPS/c2.xhtml', '<?xml version="1.0" encoding="UTF-8"?><html xmlns="http://www.w3.org/1999/xhtml"><body><h1>Deuxième chapitre</h1><p>Un second paragraphe.</p></body></html>');
$zip->close();

try {
    $book = EpubPrintSource::fromPath($epub, 5 * 1024 * 1024);
    if ($book['title'] !== 'Livre de test') throw new RuntimeException('Titre EPUB non lu.');
    if (count($book['chapters']) !== 2) throw new RuntimeException('Nombre de chapitres incorrect.');
    if ($book['chapters'][0]['title'] !== 'Premier chapitre') throw new RuntimeException('Titre de chapitre non détecté.');
    if (str_contains($book['chapters'][0]['html'], '<script')) throw new RuntimeException('Le contenu dangereux n’a pas été filtré.');
    if ((int) $book['word_count'] < 6) throw new RuntimeException('Comptage de longueur incohérent.');
} finally {
    @unlink($epub);
}
fwrite(STDOUT, "EPUB -> composition papier: OK\n");
