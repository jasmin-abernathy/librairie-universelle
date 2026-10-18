<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/OfficePrintSource.php';
if (!class_exists(ZipArchive::class) || !class_exists(DOMDocument::class)) {
    fwrite(STDERR,"Extensions ZipArchive et DOM requises.\n"); exit(1);
}
$assert = static function(bool $ok, string $message): void {
    if (!$ok) { fwrite(STDERR,$message."\n"); exit(1); }
};
$make = static function(array $entries): string {
    $base = tempnam(sys_get_temp_dir(),'office-paper-');
    if ($base === false) throw new RuntimeException('Impossible de créer une fixture.');
    $zip = new ZipArchive();
    if ($zip->open($base,ZipArchive::OVERWRITE) !== true) throw new RuntimeException('ZipArchive KO');
    foreach ($entries as $path => $content) $zip->addFromString($path,$content);
    $zip->close();
    return $base;
};
$docx = $make([
    '[Content_Types].xml'=>'<?xml version="1.0"?><Types/>',
    'word/document.xml'=><<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
<w:body>
 <w:p><w:pPr><w:pStyle w:val="Heading1"/></w:pPr><w:r><w:t>Chapitre 1</w:t></w:r></w:p>
 <w:p><w:r><w:t>Bonjour &amp; bienvenus.</w:t></w:r><w:r><w:rPr><w:b/></w:rPr><w:t> Important.</w:t></w:r></w:p>
 <w:p><w:pPr><w:pStyle w:val="Heading1"/></w:pPr><w:r><w:t>Chapitre 2</w:t></w:r></w:p>
 <w:p><w:r><w:t>La suite &lt;script&gt;alert(1)&lt;/script&gt;.</w:t></w:r></w:p>
 <w:p><w:r><w:rPr><w:b w:val="0"/></w:rPr><w:t>Non gras volontaire.</w:t></w:r></w:p>
 <w:tbl><w:tr><w:tc><w:p><w:r><w:t>NE_PAS_IMPORTER_TABLEAU</w:t></w:r></w:p></w:tc></w:tr></w:tbl>
 <w:p><w:r><w:drawing><a:blip xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
  xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" r:embed="rId1"/></w:drawing></w:r></w:p>
</w:body></w:document>
XML,
    'word/_rels/document.xml.rels'=>'<?xml version="1.0"?><Relationships><Relationship Id="rId1" Target="media/unsupported.svg"/></Relationships>',
    'word/media/unsupported.svg'=>'<svg xmlns="http://www.w3.org/2000/svg"></svg>'
]);
$odt = $make([
    'mimetype'=>'application/vnd.oasis.opendocument.text',
    'content.xml'=><<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
 xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"
 xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
 xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"
 xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"
 xmlns:xlink="http://www.w3.org/1999/xlink">
 <office:automatic-styles>
   <style:style style:name="BoldItalic" style:family="text">
     <style:text-properties fo:font-weight="bold" fo:font-style="italic"/>
   </style:style>
 </office:automatic-styles>
 <office:body><office:text>
   <text:h text:outline-level="1">Premier titre</text:h>
   <text:p>Un paragraphe de test avec du texte.</text:p>
   <text:h text:outline-level="1">Autre chapitre</text:h>
   <text:p>Une autre phrase. <text:span text:style-name="BoldItalic">Mot &amp; nuance</text:span></text:p>
   <text:p><text:span text:style-name="Inherited">Accent hérité</text:span></text:p>
   <text:p><draw:frame><draw:image xlink:href="Pictures/absente.png"/></draw:frame></text:p>
 </office:text></office:body>
</office:document-content>
XML,
    'styles.xml'=><<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<office:document-styles xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"
 xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"
 xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0">
 <office:styles>
   <style:style style:name="Emphasis" style:family="text">
     <style:text-properties fo:font-style="italic"/>
   </style:style>
   <style:style style:name="Inherited" style:family="text" style:parent-style-name="Emphasis"/>
 </office:styles>
</office:document-styles>
XML,
]);
try {
    $book = OfficePrintSource::fromPath($docx,'docx',31457280);
    $assert(count($book['chapters']) === 2,'Découpage DOCX en chapitres incorrect.');
    $assert($book['chapters'][0]['title'] === 'Chapitre 1','Titre DOCX incorrect.');
    $assert(str_contains($book['chapters'][0]['html'],'<strong> Important.</strong>'),'Mise en évidence DOCX non conservée.');
    $assert(!str_contains($book['chapters'][1]['html'],'<script>'),'HTML issu du document non échappé.');
    $assert(str_contains($book['chapters'][1]['html'],'&lt;script&gt;'),'Texte d’une balise non échappé.');
    $assert(!str_contains($book['chapters'][1]['html'],'NE_PAS_IMPORTER_TABLEAU'),'Paragraphe imbriqué dans un tableau importé par erreur.');
    $assert(!str_contains($book['chapters'][1]['html'],'<strong>Non gras volontaire.</strong>'),'Le faux gras explicite DOCX w:val=0 doit rester normal.');
    $assert(str_contains(implode(' ', $book['warnings']),'format non compatible'),'Illustration DOCX non compatible omise silencieusement.');
    $odtBook = OfficePrintSource::fromPath($odt,'odt',31457280);
    $assert(count($odtBook['chapters']) === 2,'Découpage ODT incorrect.');
    $assert($odtBook['chapters'][1]['title'] === 'Autre chapitre','Titre ODT incorrect.');
    $assert($odtBook['word_count'] >= 10,'Nombre de mots ODT incorrect.');
    $odtHTML = $odtBook['chapters'][1]['html'];
    $assert(str_contains($odtHTML, '<em><strong>Mot &amp; nuance</strong></em>'),'Le gras et italique ODT ne sont pas conservés.');
    $assert(str_contains($odtHTML,'<em>Accent hérité</em>'),'Le style hérité dans styles.xml est ignoré.');
    $assert(str_contains(implode(' ', $odtBook['warnings']),'introuvable'),'Illustration ODT absente omise silencieusement.');
    $thrown = false;
    try { OfficePrintSource::fromPath($docx,'txt',31457280); } catch (RuntimeException) { $thrown=true; }
    $assert($thrown,'TXT doit être refusé.');
    $thrown = false;
    try { OfficePrintSource::fromPath($docx,'docx',1); } catch (RuntimeException) { $thrown=true; }
    $assert($thrown,'La limite de taille doit être appliquée.');
} finally {
    @unlink($docx);
    @unlink($odt);
}
fwrite(STDOUT,"Manuscrit DOCX/ODT -> composition papier : OK\n");
