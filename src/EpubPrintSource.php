<?php

declare(strict_types=1);

final class EpubPrintSource
{
    private const MAX_ARCHIVE_ENTRIES = 5000;
    private const MAX_UNCOMPRESSED_BYTES = 209715200;
    private const MAX_EMBEDDED_IMAGE_BYTES = 8388608;
    private const MAX_TOTAL_IMAGE_BYTES = 33554432;

    public static function fromUpload(array $file, int $maxBytes): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Sélectionnez un EPUB avant de générer la version imprimée.');
        }

        $path = (string) ($file['tmp_name'] ?? '');
        $name = basename((string) ($file['name'] ?? 'livre.epub'));
        $size = (int) ($file['size'] ?? 0);

        if ($path === '' || !is_uploaded_file($path)) {
            throw new RuntimeException('Le fichier reçu n’est pas un upload HTTP valide.');
        }
        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'epub') {
            throw new RuntimeException('La composition papier attend un fichier EPUB.');
        }
        if ($size < 1 || $size > $maxBytes) {
            throw new RuntimeException('La taille de l’EPUB dépasse la limite autorisée.');
        }

        return self::fromPath($path, $maxBytes);
    }

    public static function fromPath(string $path, int $maxBytes): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('La composition papier nécessite l’extension PHP ZipArchive.');
        }
        if (!class_exists('DOMDocument')) {
            throw new RuntimeException('La composition papier nécessite l’extension PHP DOM.');
        }
        if (!is_file($path)) {
            throw new RuntimeException('EPUB introuvable.');
        }

        $size = filesize($path);
        if ($size === false || $size < 1 || $size > $maxBytes) {
            throw new RuntimeException('La taille de l’EPUB est invalide.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('L’EPUB n’est pas une archive ZIP lisible.');
        }

        try {
            if ($zip->numFiles < 3 || $zip->numFiles > self::MAX_ARCHIVE_ENTRIES) {
                throw new RuntimeException('Structure EPUB anormale.');
            }

            $uncompressed = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (is_array($stat)) {
                    $uncompressed += (int) ($stat['size'] ?? 0);
                    if ($uncompressed > self::MAX_UNCOMPRESSED_BYTES) {
                        throw new RuntimeException('EPUB trop volumineux une fois décompressé.');
                    }
                }
            }

            if (trim((string) $zip->getFromName('mimetype')) !== 'application/epub+zip') {
                throw new RuntimeException('Signature EPUB invalide.');
            }

            $containerRaw = $zip->getFromName('META-INF/container.xml');
            if ($containerRaw === false) {
                throw new RuntimeException('META-INF/container.xml est absent.');
            }
            $container = self::xml((string) $containerRaw, 'container.xml');
            $rootfiles = $container->xpath('//*[local-name()="rootfile"]');
            $opfPath = isset($rootfiles[0]) ? self::normalizeZipPath((string) $rootfiles[0]['full-path']) : null;
            if ($opfPath === null || $opfPath === '') {
                throw new RuntimeException('Chemin OPF introuvable dans l’EPUB.');
            }

            $opfRaw = $zip->getFromName($opfPath);
            if ($opfRaw === false) {
                throw new RuntimeException('Fichier OPF introuvable dans l’EPUB.');
            }
            $opf = self::xml((string) $opfRaw, 'package OPF');

            $titleNodes = $opf->xpath('//*[local-name()="metadata"]/*[local-name()="title"]');
            $bookTitle = isset($titleNodes[0]) ? self::cleanText((string) $titleNodes[0]) : 'Livre importé';

            $manifest = [];
            $manifestNodes = $opf->xpath('//*[local-name()="manifest"]/*[local-name()="item"]') ?: [];
            foreach ($manifestNodes as $item) {
                $id = trim((string) $item['id']);
                if ($id === '') continue;
                $manifest[$id] = [
                    'href' => (string) $item['href'],
                    'media_type' => strtolower((string) $item['media-type']),
                    'properties' => strtolower((string) $item['properties']),
                ];
            }

            $spineNodes = $opf->xpath('//*[local-name()="spine"]/*[local-name()="itemref"]') ?: [];
            $chapters = [];
            $warnings = [];
            $imageBytes = 0;
            $wordCount = 0;
            $opfDirectory = self::zipDirname($opfPath);

            foreach ($spineNodes as $itemref) {
                if (count($chapters) >= 500) {
                    throw new RuntimeException('L’EPUB contient trop de sections pour la prévisualisation.');
                }

                $idref = trim((string) $itemref['idref']);
                $item = $manifest[$idref] ?? null;
                if ($item === null || str_contains($item['properties'], 'nav')) continue;
                if (!in_array($item['media_type'], ['application/xhtml+xml', 'text/html'], true)) continue;

                $contentPath = self::resolveZipPath($opfDirectory, $item['href']);
                if ($contentPath === null) continue;
                $raw = $zip->getFromName($contentPath);
                if ($raw === false) continue;

                $chapter = self::sanitizeChapter($zip, (string) $raw, $contentPath, count($chapters) + 1, $imageBytes, $warnings);
                if ($chapter === null) continue;

                $chapters[] = $chapter;
                $plain = trim(strip_tags($chapter['html']));
                if ($plain !== '') {
                    $words = preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY);
                    $wordCount += is_array($words) ? count($words) : 0;
                }
            }

            if ($chapters === []) {
                throw new RuntimeException('Aucun chapitre XHTML exploitable n’a été trouvé dans l’EPUB.');
            }

            return [
                'title' => $bookTitle !== '' ? $bookTitle : 'Livre importé',
                'chapters' => $chapters,
                'warnings' => array_values(array_unique($warnings)),
                'word_count' => $wordCount,
            ];
        } finally {
            $zip->close();
        }
    }

    private static function xml(string $xml, string $label): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $parsed = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if (!$parsed instanceof SimpleXMLElement) {
            throw new RuntimeException('XML EPUB invalide : ' . $label . '.');
        }
        return $parsed;
    }

    private static function sanitizeChapter(ZipArchive $zip, string $html, string $contentPath, int $position, int &$imageBytes, array &$warnings): ?array
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $loaded = $dom->loadXML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            if (!$loaded) {
                $loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT);
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (!$loaded) {
            $warnings[] = 'Une section XHTML illisible a été ignorée.';
            return null;
        }

        $xpath = new DOMXPath($dom);
        $body = $xpath->query('//*[local-name()="body"]')->item(0);
        if (!$body instanceof DOMNode) return null;

        $titleNode = $xpath->query('(//*[local-name()="body"]//*[local-name()="h1" or local-name()="h2"])[1]')->item(0);
        $title = $titleNode instanceof DOMNode ? self::cleanText($titleNode->textContent) : '';
        if ($title === '') $title = 'Chapitre ' . $position;

        $parts = [];
        foreach ($body->childNodes as $child) {
            $parts[] = self::sanitizeNode($zip, $child, $contentPath, $imageBytes, $warnings);
        }
        $bodyHtml = trim(implode('', $parts));
        if ($bodyHtml === '') return null;

        if (!$titleNode instanceof DOMNode) {
            $bodyHtml = '<h1>' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h1>' . $bodyHtml;
        }

        return ['id' => 'chapter-' . $position, 'title' => $title, 'html' => $bodyHtml];
    }

    private static function sanitizeNode(ZipArchive $zip, DOMNode $node, string $contentPath, int &$imageBytes, array &$warnings): string
    {
        if ($node instanceof DOMText) {
            return htmlspecialchars($node->wholeText, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        if (!$node instanceof DOMElement) return '';

        $tag = strtolower($node->localName ?: $node->nodeName);
        if (in_array($tag, ['script', 'style', 'link', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'video', 'audio'], true)) {
            return '';
        }

        if ($tag === 'img') {
            $src = trim($node->getAttribute('src'));
            $alt = self::cleanText($node->getAttribute('alt'));
            $resolved = self::resolveZipPath(self::zipDirname($contentPath), $src);
            if ($resolved === null) {
                return $alt !== '' ? '<p class="image-placeholder">[Image : ' . htmlspecialchars($alt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ']</p>' : '';
            }

            $bytes = $zip->getFromName($resolved);
            if ($bytes === false || strlen($bytes) > self::MAX_EMBEDDED_IMAGE_BYTES || $imageBytes + strlen($bytes) > self::MAX_TOTAL_IMAGE_BYTES) {
                $warnings[] = 'Une image trop volumineuse n’a pas été intégrée à l’aperçu.';
                return $alt !== '' ? '<p class="image-placeholder">[Image : ' . htmlspecialchars($alt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ']</p>' : '';
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string) $finfo->buffer($bytes);
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
                $warnings[] = 'Un format d’image non pris en charge a été ignoré.';
                return $alt !== '' ? '<p class="image-placeholder">[Image : ' . htmlspecialchars($alt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ']</p>' : '';
            }

            $imageBytes += strlen($bytes);
            return '<img src="data:' . $mime . ';base64,' . base64_encode($bytes) . '" alt="' . htmlspecialchars($alt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
        }

        $allowed = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'ul', 'ol', 'li', 'em', 'strong', 'b', 'i', 'u', 's', 'br', 'hr', 'figure', 'figcaption', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'sup', 'sub', 'pre', 'code'];

        $children = '';
        foreach ($node->childNodes as $child) {
            $children .= self::sanitizeNode($zip, $child, $contentPath, $imageBytes, $warnings);
        }

        if (!in_array($tag, $allowed, true)) return $children;
        if (in_array($tag, ['br', 'hr'], true)) return '<' . $tag . '>';

        $attributes = '';
        if (in_array($tag, ['td', 'th'], true)) {
            foreach (['colspan', 'rowspan'] as $attribute) {
                $value = (int) $node->getAttribute($attribute);
                if ($value > 1 && $value <= 20) $attributes .= ' ' . $attribute . '="' . $value . '"';
            }
        }
        return '<' . $tag . $attributes . '>' . $children . '</' . $tag . '>';
    }

    private static function resolveZipPath(string $baseDirectory, string $relative): ?string
    {
        $relative = html_entity_decode(trim($relative), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($relative === '' || str_starts_with($relative, '#') || preg_match('#^[a-z][a-z0-9+.-]*:#i', $relative)) return null;

        $pathOnly = preg_split('/[?#]/', $relative, 2)[0] ?? '';
        $pathOnly = rawurldecode(str_replace('\\', '/', $pathOnly));
        $candidate = str_starts_with($pathOnly, '/') ? ltrim($pathOnly, '/') : trim($baseDirectory . '/' . $pathOnly, '/');
        return self::normalizeZipPath($candidate);
    }

    private static function normalizeZipPath(string $path): ?string
    {
        $stack = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment === '' || $segment === '.') continue;
            if ($segment === '..') {
                if ($stack === []) return null;
                array_pop($stack);
                continue;
            }
            if (str_contains($segment, "\0")) return null;
            $stack[] = $segment;
        }
        return implode('/', $stack);
    }

    private static function zipDirname(string $path): string
    {
        $directory = dirname($path);
        return $directory === '.' ? '' : str_replace('\\', '/', $directory);
    }

    private static function cleanText(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';
        return mb_substr($value, 0, 300);
    }
}
