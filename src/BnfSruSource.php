<?php

declare(strict_types=1);

final class BnfSruSource implements CatalogSource
{
    private const ENDPOINT = 'https://catalogue.bnf.fr/api/SRU';
    private const DC_NS = 'http://purl.org/dc/elements/1.1/';

    /** @var Closure(string):string */
    private Closure $fetcher;

    public function __construct(?callable $fetcher = null)
    {
        $client = new HttpClient();
        $this->fetcher = Closure::fromCallable($fetcher ?? [$client, 'get']);
    }

    public function key(): string
    {
        return 'bnf-sru';
    }

    public function searchEditions(string $title, string $author, int $limit = 20): array
    {
        $limit = max(1, min($limit, 100));
        $query = sprintf(
            '(bib.author all "%s") and (bib.title all "%s") and (bib.recordtype any "mon") and (bib.doctype any "a")',
            self::escapeCql($author),
            self::escapeCql($title)
        );

        $url = self::ENDPOINT . '?' . http_build_query([
            'version' => '1.2',
            'operation' => 'searchRetrieve',
            'query' => $query,
            'recordSchema' => 'dublincore',
            'maximumRecords' => $limit,
        ], '', '&', PHP_QUERY_RFC3986);

        $xml = ($this->fetcher)($url);
        return $this->parseResponse($xml);
    }

    /** @return array<int, array<string, mixed>> */
    public function parseResponse(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        $document = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($document === false) {
            throw new RuntimeException('Réponse XML BnF invalide.');
        }

        $recordNodes = $document->xpath('//*[local-name()="recordData"]/*');
        if ($recordNodes === false) {
            return [];
        }

        $result = [];
        foreach ($recordNodes as $node) {
            $values = [
                'title' => [],
                'creator' => [],
                'contributor' => [],
                'publisher' => [],
                'date' => [],
                'language' => [],
                'type' => [],
                'format' => [],
                'identifier' => [],
            ];

            foreach ($node->children(self::DC_NS) as $name => $value) {
                if (array_key_exists($name, $values)) {
                    $text = trim((string) $value);
                    if ($text !== '') {
                        $values[$name][] = $text;
                    }
                }
            }

            $ark = self::extractArk($values['identifier']);
            if ($ark === null) {
                continue;
            }

            $isbn13 = self::extractIsbn13($values['identifier']);
            $title = $values['title'][0] ?? '';
            if ($title === '') {
                continue;
            }

            $result[] = [
                'external_id' => $ark,
                'title' => self::cleanTitle($title),
                'publisher' => $values['publisher'][0] ?? null,
                'publication_date' => self::normalizeDate($values['date'][0] ?? null),
                'language' => self::normalizeLanguage($values['language'][0] ?? null),
                'medium' => self::detectMedium($values['type'], $values['format']),
                'file_format' => self::detectFileFormat($values['format']),
                'isbn13' => $isbn13,
                'source_url' => 'https://catalogue.bnf.fr/' . $ark,
                'cover_url' => $isbn13 !== null
                    ? 'https://openapi.bnf.fr/couverture/image/image/recupererImage?ISBN=' . rawurlencode($isbn13) . '&couverture=1'
                    : null,
                'contributors' => array_map(
                    static fn(string $name): array => ['name' => self::cleanContributorName($name), 'role' => 'contributor'],
                    array_values(array_unique($values['contributor']))
                ),
            ];
        }

        return $result;
    }

    private static function escapeCql(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], trim($value));
    }

    /** @param string[] $identifiers */
    private static function extractArk(array $identifiers): ?string
    {
        foreach ($identifiers as $identifier) {
            if (preg_match('~ark:/12148/cb[0-9a-z]+~i', $identifier, $match) === 1) {
                return $match[0];
            }
        }

        return null;
    }

    /** @param string[] $identifiers */
    private static function extractIsbn13(array $identifiers): ?string
    {
        foreach ($identifiers as $identifier) {
            if (stripos($identifier, 'ISBN') === false && preg_match('/97[89][0-9\-\s]{10,}/', $identifier) !== 1) {
                continue;
            }

            if (preg_match('/(?:ISBN(?:-1[03])?\s*[:=]?\s*)?((?:97[89][\s-]*)?[0-9Xx](?:[\s-]*[0-9Xx]){9,12})/u', $identifier, $match) !== 1) {
                continue;
            }

            $isbn = self::normalizeIsbn($match[1]);
            if ($isbn !== null) {
                return $isbn;
            }
        }

        return null;
    }

    private static function normalizeIsbn(string $isbn): ?string
    {
        $compact = strtoupper((string) preg_replace('/[^0-9X]/i', '', $isbn));

        if (strlen($compact) === 13 && preg_match('/^97[89][0-9]{10}$/', $compact) === 1) {
            return self::validIsbn13($compact) ? $compact : null;
        }

        if (strlen($compact) === 10 && preg_match('/^[0-9]{9}[0-9X]$/', $compact) === 1) {
            $core = '978' . substr($compact, 0, 9);
            $sum = 0;
            for ($i = 0; $i < 12; $i++) {
                $sum += (int) $core[$i] * ($i % 2 === 0 ? 1 : 3);
            }
            return $core . ((10 - ($sum % 10)) % 10);
        }

        return null;
    }

    private static function validIsbn13(string $isbn): bool
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $isbn[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        return ((10 - ($sum % 10)) % 10) === (int) $isbn[12];
    }

    private static function cleanTitle(string $title): string
    {
        return trim((string) preg_replace('/\s*\[(?:Texte imprimé|Ressource électronique)\]\s*/iu', ' ', $title));
    }

    private static function cleanContributorName(string $name): string
    {
        return trim((string) preg_replace('/\s*\([^)]*\)\s*/u', ' ', $name));
    }

    private static function normalizeDate(?string $date): ?string
    {
        if ($date === null) {
            return null;
        }
        if (preg_match('/\b(1[0-9]{3}|20[0-9]{2})\b/', $date, $match) === 1) {
            return $match[1];
        }
        return trim($date) !== '' ? trim($date) : null;
    }

    private static function normalizeLanguage(?string $language): ?string
    {
        if ($language === null) {
            return null;
        }

        $language = strtolower(trim($language));
        return match ($language) {
            'fre', 'fra', 'fr' => 'fr',
            'eng', 'en' => 'en',
            default => $language !== '' ? $language : null,
        };
    }

    /** @param string[] $types @param string[] $formats */
    private static function detectMedium(array $types, array $formats): string
    {
        $haystack = mb_strtolower(implode(' ', array_merge($types, $formats)), 'UTF-8');
        if (str_contains($haystack, 'audio') || str_contains($haystack, 'sonore')) {
            return 'audio';
        }
        if (str_contains($haystack, 'electronic') || str_contains($haystack, 'électronique') || str_contains($haystack, 'numérique') || str_contains($haystack, 'epub') || str_contains($haystack, 'pdf')) {
            return 'ebook';
        }
        return 'paper';
    }

    /** @param string[] $formats */
    private static function detectFileFormat(array $formats): ?string
    {
        $haystack = mb_strtolower(implode(' ', $formats), 'UTF-8');
        if (str_contains($haystack, 'epub')) {
            return 'EPUB';
        }
        if (str_contains($haystack, 'pdf')) {
            return 'PDF';
        }
        return null;
    }
}
