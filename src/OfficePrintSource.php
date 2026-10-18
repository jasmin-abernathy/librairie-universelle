<?php
declare(strict_types=1);

/**
 * Import de manuscrit bureautique DOCX/ODT, destiné à une recomposition
 * en pages de livre (et non à reproduire le rendu Word au pixel près).
 * Aucun contenu distant n'est chargé, aucune macro n'est exécutée.
 */
final class OfficePrintSource
{
    private const MAX_ENTRIES = 3500;
    private const MAX_XML_BYTES = 16777216;
    private const MAX_TOTAL_BYTES = 120000000;
    private const MAX_IMAGE_BYTES = 8000000;
    private const MAX_IMAGE_TOTAL = 28000000;

    public static function fromUpload(array $file, int $maxBytes): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw new RuntimeException('Manuscrit trop volumineux pour le serveur. Réduisez sa taille ou compressez les illustrations.');
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Ajoutez un manuscrit DOCX/ODT valide. Si l’envoi a été interrompu, réessayez.');
        }
        $path = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($ext === 'doc') {
            throw new RuntimeException('Le format .doc ancien nécessite une conversion : ouvrez-le dans LibreOffice ou Word et enregistrez-le en .docx ou .odt.');
        }
        if (!in_array($ext, ['docx', 'odt'], true)) {
            throw new RuntimeException('Format non compatible. Fournissez un DOCX ou un ODT (pas de TXT).');
        }
        if ($path === '' || !is_uploaded_file($path) || $size < 1 || $size > $maxBytes) {
            throw new RuntimeException('Manuscrit manquant, trop volumineux ou transfert invalide.');
        }
        return self::fromPath($path, $ext, $maxBytes);
    }

    public static function fromPath(string $path, string $ext, int $maxBytes): array
    {
        if (!class_exists(ZipArchive::class) || !class_exists(DOMDocument::class)) {
            throw new RuntimeException('La conversion DOCX/ODT nécessite les extensions PHP ZIP et DOM.');
        }
        if (!in_array($ext, ['docx', 'odt'], true) || !is_file($path)
            || ($size = filesize($path)) === false || $size < 1 || $size > $maxBytes) {
            throw new RuntimeException('Fichier DOCX/ODT invalide.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('Le manuscrit ne peut pas être ouvert.');
        try {
            if ($zip->numFiles > self::MAX_ENTRIES) throw new RuntimeException('Archive de document anormalement volumineuse.');
            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $total += (int) ($stat['size'] ?? 0);
                if ($total > self::MAX_TOTAL_BYTES) throw new RuntimeException('Document décompressé trop volumineux.');
            }
            $file = $ext === 'docx' ? 'word/document.xml' : 'content.xml';
            if ($ext === 'odt' && trim((string) $zip->getFromName('mimetype')) !== 'application/vnd.oasis.opendocument.text') {
                throw new RuntimeException('La signature du fichier ODT n’est pas valide.');
            }
            $raw = $zip->getFromName($file);
            if (!is_string($raw) || $raw === '' || strlen($raw) > self::MAX_XML_BYTES) {
                throw new RuntimeException('Le contenu bureautique est introuvable ou trop volumineux.');
            }
            $doc = new DOMDocument();
            if (!@$doc->loadXML($raw, LIBXML_NONET | LIBXML_COMPACT)) {
                throw new RuntimeException('Le document contient un XML illisible.');
            }
            $xpath = new DOMXPath($doc);
            $root = $ext === 'docx'
                ? $xpath->query('//*[local-name()="document"]/*[local-name()="body"]')->item(0)
                : $xpath->query('//*[local-name()="body"]/*[local-name()="text"]')->item(0);
            if (!$root instanceof DOMNode) throw new RuntimeException('Structure du manuscrit non reconnue.');
            $rels = $ext === 'docx' ? self::relationships($zip) : [];
            $odtStyles = $ext === 'odt' ? self::odtTextStyles($doc, $zip) : [];
            $chapters = [];
            $parts = [];
            $warnings = ['Mise en page recomposée : tableaux, zones flottantes, notes et polices particulières doivent être relus dans le PDF.'];
            $wordCount = 0;
            $imageTotal = 0;
            $headingTitle = 'Manuscrit';
            $n = 0;
            $blocks = $ext === 'docx'
                ? $xpath->query('./*[local-name()="p" or local-name()="tbl"]', $root)
                : $xpath->query('.//*[local-name()="h" or local-name()="p"]', $root);
            foreach ($blocks as $node) {
                if ($n++ > 6500) throw new RuntimeException('Trop de blocs dans le manuscrit.');
                if ($node->localName === 'tbl') {
                    $warnings[] = 'Les tableaux du manuscrit doivent être vérifiés manuellement.';
                    continue;
                }
                $heading = $ext === 'docx' ? self::wordHeading($node, $xpath) : $node->localName === 'h';
                $html = self::paragraph($node, $ext, $xpath, $zip, $rels, $odtStyles, $warnings, $imageTotal);
                $plain = trim($node->textContent);
                if ($plain !== '') $wordCount += count(preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY) ?: []);
                if ($heading && trim(strip_tags($html)) !== '') {
                    if ($parts !== []) {
                        $chapters[] = ['id'=>'chapter-'.(count($chapters)+1),'title'=>$headingTitle,'html'=>implode("\n",$parts)];
                        $parts = [];
                    }
                    $headingTitle = mb_substr($plain, 0, 160);
                    $parts[] = '<h1>'.self::esc($headingTitle).'</h1>';
                } elseif ($html !== '') {
                    $parts[] = '<p>'.$html.'</p>';
                }
                if (count($chapters) > 350) throw new RuntimeException('Trop de chapitres détectés.');
            }
            if ($parts !== []) {
                $chapters[] = ['id'=>'chapter-'.(count($chapters)+1),'title'=>$headingTitle,'html'=>implode("\n",$parts)];
            }
            if ($chapters === [] || $wordCount < 1) throw new RuntimeException('Ce fichier ne contient pas de texte exploitable pour la composition papier.');
            $title = $chapters[0]['title'] ?: 'Manuscrit';
            return [
                'title'=>$title,'chapters'=>$chapters,'word_count'=>$wordCount,
                'warnings'=>array_values(array_unique($warnings)),
            ];
        } finally { $zip->close(); }
    }

    private static function relationships(ZipArchive $zip): array
    {
        $raw = $zip->getFromName('word/_rels/document.xml.rels');
        if (!is_string($raw) || strlen($raw) > 1048576) return [];
        $doc = new DOMDocument();
        if (!@$doc->loadXML($raw, LIBXML_NONET | LIBXML_COMPACT)) return [];
        $out = [];
        foreach ($doc->getElementsByTagName('Relationship') as $rel) {
            if ($rel->getAttribute('TargetMode') === 'External') continue;
            $target = str_replace('\\','/',$rel->getAttribute('Target'));
            // Ne suivre que les médias internes au package word/media/.
            $name = basename($target);
            if (str_starts_with($target, 'media/') && $name !== '' && $name !== '.' && $name !== '..') {
                $out[$rel->getAttribute('Id')] = 'word/media/'.$name;
            }
        }
        return $out;
    }

    private static function wordHeading(DOMNode $paragraph, DOMXPath $xpath): bool
    {
        $style = $xpath->query('./*[local-name()="pPr"]/*[local-name()="pStyle"]', $paragraph)->item(0);
        if (!$style instanceof DOMElement) return false;
        return (bool) preg_match('/^(heading\s*[1-2]|titre\s*[1-2]|title)$/i', $style->getAttributeNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'val') ?: $style->getAttribute('w:val'));
    }

    private static function paragraph(DOMNode $node, string $ext, DOMXPath $xpath, ZipArchive $zip, array $rels, array $odtStyles, array &$warnings, int &$imageTotal): string
    {
        $html = '';
        if ($ext === 'docx') {
            foreach ($xpath->query('.//*[local-name()="r"]', $node) as $run) {
                $chunk = '';
                foreach ($xpath->query('.//*[local-name()="t" or local-name()="tab" or local-name()="br" or local-name()="blip"]', $run) as $leaf) {
                    if ($leaf->localName === 't') $chunk .= self::esc($leaf->textContent);
                    if ($leaf->localName === 'tab') $chunk .= '　';
                    if ($leaf->localName === 'br') $chunk .= '<br>';
                    if ($leaf->localName === 'blip' && $leaf instanceof DOMElement) {
                        $rid = $leaf->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'embed');
                        $chunk .= self::image($zip, $rels[$rid] ?? '', $warnings, $imageTotal);
                    }
                }
                if ($chunk !== '') {
                    if (self::docxRunFlag($xpath, $run, 'b')) $chunk = '<strong>'.$chunk.'</strong>';
                    if (self::docxRunFlag($xpath, $run, 'i')) $chunk = '<em>'.$chunk.'</em>';
                }
                $html .= $chunk;
            }
        } else {
            foreach ($node->childNodes as $child) {
                $html .= self::odtInline($child, $zip, $odtStyles, $warnings, $imageTotal);
            }
        }
        return trim($html);
    }

    private static function odtInline(DOMNode $node, ZipArchive $zip, array $styles, array &$warnings, int &$imageTotal): string
    {
        if ($node instanceof DOMText) return self::esc($node->wholeText);
        if (!$node instanceof DOMElement) return '';
        if ($node->localName === 's') {
            $count = max(1,min(30,(int) $node->getAttribute('text:c')));
            return str_repeat(' ', $count);
        }
        if ($node->localName === 'tab') return '　';
        if ($node->localName === 'line-break') return '<br>';
        if ($node->localName === 'image') {
            $src = $node->getAttributeNS('http://www.w3.org/1999/xlink', 'href');
            if (!preg_match('#^Pictures/[a-zA-Z0-9_.-]+$#', $src)) return '';
            return self::image($zip, $src, $warnings, $imageTotal);
        }
        $result = '';
        foreach ($node->childNodes as $child) $result .= self::odtInline($child,$zip,$styles,$warnings,$imageTotal);
        if ($node->localName === 'span'
            && $node->namespaceURI === 'urn:oasis:names:tc:opendocument:xmlns:text:1.0') {
            $styleName = $node->getAttributeNS('urn:oasis:names:tc:opendocument:xmlns:text:1.0', 'style-name');
            $style = $styles[$styleName] ?? [];
            if (($style['bold'] ?? false) === true) $result = '<strong>'.$result.'</strong>';
            if (($style['italic'] ?? false) === true) $result = '<em>'.$result.'</em>';
        }
        return $result;
    }

    /** Limited text styles: no untrusted stylesheets, font names or CSS. */
    private static function odtTextStyles(DOMDocument $content, ZipArchive $zip): array
    {
        $docs = [$content];
        $raw = $zip->getFromName('styles.xml');
        if (is_string($raw) && strlen($raw) <= 8388608) {
            $external = new DOMDocument();
            if (@$external->loadXML($raw, LIBXML_NONET | LIBXML_COMPACT)) $docs[] = $external;
        }
        $styleNS = 'urn:oasis:names:tc:opendocument:xmlns:style:1.0';
        $foNS = 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0';
        $defs = [];
        foreach ($docs as $doc) {
            foreach ($doc->getElementsByTagNameNS($styleNS, 'style') as $node) {
                if (!$node instanceof DOMElement || $node->getAttributeNS($styleNS, 'family') !== 'text') continue;
                $name = $node->getAttributeNS($styleNS, 'name');
                if ($name === '' || strlen($name) > 120 || count($defs) > 3000) continue;
                $weight = null;
                $italic = null;
                foreach ($node->getElementsByTagNameNS($styleNS, 'text-properties') as $prop) {
                    if ($prop->hasAttributeNS($foNS, 'font-weight')) {
                        $value = strtolower($prop->getAttributeNS($foNS, 'font-weight'));
                        $weight = $value === 'bold' || (ctype_digit($value) && (int) $value >= 600);
                    }
                    if ($prop->hasAttributeNS($foNS, 'font-style')) {
                        $italic = in_array(strtolower($prop->getAttributeNS($foNS, 'font-style')), ['italic','oblique'], true);
                    }
                    break;
                }
                $defs[$name] = [
                    'bold'=>$weight, 'italic'=>$italic,
                    'parent'=>$node->getAttributeNS($styleNS, 'parent-style-name')
                ];
            }
        }
        $resolved = [];
        foreach ($defs as $name => $_) {
            $seen = [];
            $cur = $name;
            $values = ['bold'=>null, 'italic'=>null];
            for ($depth = 0; $depth < 12 && isset($defs[$cur]) && !isset($seen[$cur]); $depth++) {
                $seen[$cur] = true;
                foreach (['bold','italic'] as $prop) {
                    if ($values[$prop] === null && $defs[$cur][$prop] !== null) $values[$prop] = $defs[$cur][$prop];
                }
                $cur = $defs[$cur]['parent'];
            }
            $resolved[$name] = ['bold'=>$values['bold'] === true, 'italic'=>$values['italic'] === true];
        }
        return $resolved;
    }

    private static function docxRunFlag(DOMXPath $xpath, DOMNode $run, string $kind): bool
    {
        $flag = $xpath->query('./*[local-name()="rPr"]/*[local-name()="'.$kind.'"]', $run)->item(0);
        if (!$flag instanceof DOMElement) return false;
        $value = $flag->getAttributeNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'val');
        if ($value === '') $value = $flag->getAttribute('w:val');
        return !in_array(strtolower($value), ['0','false','off','no'], true);
    }

    private static function image(ZipArchive $zip, string $path, array &$warnings, int &$imageTotal): string
    {
        if ($path === '') {
            $warnings[] = 'Une illustration référencée dans le manuscrit est absente ou inaccessible.';
            return '';
        }
        if ($imageTotal >= self::MAX_IMAGE_TOTAL) {
            $warnings[] = 'Certaines illustrations ont été omises : limite cumulée de 28 Mo atteinte.';
            return '';
        }
        $stat = $zip->statName($path);
        if (!is_array($stat)) {
            $warnings[] = 'Une illustration est introuvable dans le document importé.';
            return '';
        }
        if ((int) $stat['size'] > self::MAX_IMAGE_BYTES) {
            $warnings[] = 'Une illustration de plus de 8 Mo n’a pas été intégrée à l’aperçu.';
            return '';
        }
        $bytes = $zip->getFromName($path);
        if (!is_string($bytes)) {
            $warnings[] = 'Une illustration du manuscrit n’a pas pu être lue.';
            return '';
        }
        if ($imageTotal + strlen($bytes) > self::MAX_IMAGE_TOTAL) {
            $warnings[] = 'Certaines illustrations ont été omises : limite cumulée de 28 Mo atteinte.';
            return '';
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        if (!in_array($mime, ['image/jpeg','image/png','image/webp'], true)) {
            $warnings[] = 'Une illustration au format non compatible (par exemple SVG, WMF ou EMF) a été omise : convertissez-la en PNG ou JPEG.';
            return '';
        }
        $imageTotal += strlen($bytes);
        return '<img alt="Illustration du manuscrit" src="data:'.$mime.';base64,'.base64_encode($bytes).'">';
    }

    private static function esc(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
