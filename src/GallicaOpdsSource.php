<?php

declare(strict_types=1);

final class GallicaOpdsSource
{
    private const ENDPOINT = 'https://gallica.bnf.fr/services/engine/search/opds';

    public function __construct(private readonly ?HttpClient $http = null)
    {
    }

    public function search(string $title, int $limit = 10): array
    {
        $title = trim($title);
        if ($title === '') {
            return [];
        }

        $limit = max(1, min(50, $limit));
        $query = sprintf(
            'dc.title all "%s" and dc.formatspecific all "epub" and provenance all "bnf.fr"',
            self::escapeCql($title)
        );
        $url = self::ENDPOINT . '?' . http_build_query([
            'operation' => 'searchRetrieve',
            'version' => '1.2',
            'exactSearch' => 'false',
            'query' => $query,
            'startRecord' => 1,
            'maximumRecords' => $limit,
        ], '', '&', PHP_QUERY_RFC3986);

        $client = $this->http ?? new HttpClient();
        return $this->parse($client->get($url));
    }

    public function parse(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $feed = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
            if ($feed === false) {
                throw new RuntimeException('Flux OPDS Gallica invalide.');
            }

            $atom = $feed->children('http://www.w3.org/2005/Atom');
            $entries = [];
            foreach ($atom->entry as $entry) {
                $title = trim((string) $entry->title);
                $id = trim((string) $entry->id);
                $authors = [];
                foreach ($entry->author as $author) {
                    $name = trim((string) $author->name);
                    if ($name !== '') {
                        $authors[] = $name;
                    }
                }

                $epubUrl = null;
                $readUrl = null;
                foreach ($entry->link as $link) {
                    $attributes = $link->attributes();
                    $href = trim((string) ($attributes['href'] ?? ''));
                    $rel = trim((string) ($attributes['rel'] ?? ''));
                    $type = trim((string) ($attributes['type'] ?? ''));
                    if ($href === '') {
                        continue;
                    }
                    $href = preg_replace('#^http://#i', 'https://', $href) ?? $href;
                    if ($type === 'application/epub+zip' && str_contains($rel, 'acquisition')) {
                        $epubUrl = $href;
                    } elseif ($type === 'text/html' && $rel === 'alternate') {
                        $readUrl = $href;
                    }
                }

                if ($title === '' || $id === '' || $epubUrl === null) {
                    continue;
                }

                preg_match('#ark:/12148/[^\s/]+#', $id, $matches);
                $externalId = $matches[0] ?? trim($id);
                $entries[] = [
                    'external_id' => $externalId,
                    'title' => $title,
                    'authors' => array_values(array_unique($authors)),
                    'epub_url' => $epubUrl,
                    'read_url' => $readUrl,
                ];
            }

            return $entries;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    public static function matchesWork(array $entry, string $workTitle, string $contributors): bool
    {
        $workNorm = self::normalize($workTitle);
        $entryNorm = self::normalize((string) ($entry['title'] ?? ''));
        if ($workNorm === '' || $entryNorm === '' || (!str_contains($entryNorm, $workNorm) && !str_contains($workNorm, $entryNorm))) {
            return false;
        }

        $contributorTokens = self::significantTokens($contributors);
        if ($contributorTokens === []) {
            return true;
        }

        $authorText = self::normalize(implode(' ', $entry['authors'] ?? []));
        if ($authorText === '') {
            return false;
        }

        foreach ($contributorTokens as $token) {
            if (!str_contains($authorText, $token)) {
                return false;
            }
        }

        return true;
    }

    private static function escapeCql(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }

    private static function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        if (class_exists('Transliterator')) {
            $transliterator = Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');
            if ($transliterator !== null) {
                $value = $transliterator->transliterate($value);
            }
        }
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value;
        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }

    private static function significantTokens(string $contributors): array
    {
        $tokens = preg_split('/\s+/u', self::normalize($contributors), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $stop = ['de', 'du', 'des', 'la', 'le', 'les', 'et'];
        return array_values(array_filter(array_unique($tokens), static fn (string $token): bool => mb_strlen($token) >= 3 && !in_array($token, $stop, true)));
    }
}
