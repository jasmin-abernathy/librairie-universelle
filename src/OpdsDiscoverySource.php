<?php

declare(strict_types=1);

final class OpdsDiscoverySource implements DiscoverySource
{
    public function __construct(
        private readonly string $sourceKey,
        private readonly string $sourceName,
        private readonly string $catalogUrl,
        private readonly string $rightsNote,
        private readonly HttpClient $http
    ) {}

    public function key(): string { return $this->sourceKey; }
    public function name(): string { return $this->sourceName; }

    public function search(string $query, int $limit = 8): array
    {
        $query = trim($query);
        if ($query === '') return [];
        $limit = max(1, min(20, $limit));
        $feedUrl = $this->resolveSearchUrl($query, $limit);
        return array_slice($this->parseFeed($this->http->get(
            $feedUrl,
            'application/atom+xml,application/xml,text/xml;q=0.9,*/*;q=0.1'
        )), 0, $limit);
    }

    public function parseFeed(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $feed = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
            if ($feed === false) throw new RuntimeException('Flux OPDS invalide pour ' . $this->sourceName . '.');
            $entries = $feed->xpath('//*[local-name()="entry"]') ?: [];
            $results = [];

            foreach ($entries as $entry) {
                $titleNodes = $entry->xpath('./*[local-name()="title"]');
                $idNodes = $entry->xpath('./*[local-name()="id"]');
                $title = trim((string) ($titleNodes[0] ?? ''));
                $externalId = trim((string) ($idNodes[0] ?? $title));
                if ($title === '') continue;

                $authors = [];
                foreach ($entry->xpath('./*[local-name()="author"]/*[local-name()="name"]') ?: [] as $author) {
                    $name = trim((string) $author);
                    if ($name !== '') $authors[] = $name;
                }

                $accessUrl = '';
                $downloadUrl = null;
                $sourceUrl = '';
                foreach ($entry->xpath('./*[local-name()="link"]') ?: [] as $link) {
                    $attributes = $link->attributes();
                    $href = trim((string) ($attributes['href'] ?? ''));
                    $rel = trim((string) ($attributes['rel'] ?? ''));
                    $type = trim((string) ($attributes['type'] ?? ''));
                    if ($href === '') continue;
                    $href = $this->absoluteUrl($href, $this->catalogUrl);
                    if ($type === 'application/epub+zip' && (str_contains($rel, 'acquisition') || $rel === '')) $downloadUrl = $href;
                    if ($rel === 'alternate' && ($type === 'text/html' || $type === '')) $accessUrl = $href;
                    if ($sourceUrl === '') $sourceUrl = $href;
                }

                if ($accessUrl === '') $accessUrl = $downloadUrl ?? $sourceUrl;
                if ($sourceUrl === '') $sourceUrl = $accessUrl;
                if ($accessUrl === '') continue;

                $language = null;
                $languageNodes = $entry->xpath('./*[local-name()="language"]');
                if ($languageNodes !== false && isset($languageNodes[0])) $language = trim((string) $languageNodes[0]) ?: null;

                $results[] = [
                    'source_key'=>$this->key(),
                    'source_name'=>$this->name(),
                    'external_id'=>$externalId,
                    'title'=>$title,
                    'authors'=>array_values(array_unique($authors)),
                    'year'=>null,
                    'language'=>$language,
                    'source_url'=>$sourceUrl,
                    'access_url'=>$accessUrl,
                    'download_url'=>$downloadUrl,
                    'format'=>$downloadUrl !== null ? 'EPUB' : null,
                    'rights_note'=>$this->rightsNote,
                    'kind'=>'free_ebook_review',
                ];
            }
            return $results;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function resolveSearchUrl(string $query, int $limit): string
    {
        $rootXml = $this->http->get($this->catalogUrl, 'application/atom+xml,application/xml,text/xml;q=0.9,*/*;q=0.1');
        $previous = libxml_use_internal_errors(true);
        try {
            $root = simplexml_load_string($rootXml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
            if ($root === false) throw new RuntimeException('Catalogue OPDS invalide pour ' . $this->sourceName . '.');

            $searchHref = '';
            foreach ($root->xpath('//*[local-name()="link"]') ?: [] as $link) {
                $attributes = $link->attributes();
                $rel = trim((string) ($attributes['rel'] ?? ''));
                if ($rel === 'search' || str_contains($rel, 'search')) {
                    $searchHref = trim((string) ($attributes['href'] ?? ''));
                    break;
                }
            }

            if ($searchHref === '') {
                if (str_contains($this->catalogUrl, 'search.opds')) {
                    return $this->catalogUrl . (str_contains($this->catalogUrl, '?') ? '&' : '?')
                        . http_build_query(['query'=>$query], '', '&', PHP_QUERY_RFC3986);
                }
                throw new RuntimeException('Recherche OPDS non annoncée par ' . $this->sourceName . '.');
            }

            $searchHref = $this->absoluteUrl($searchHref, $this->catalogUrl);
            if (str_contains($searchHref, '{searchTerms')) return $this->fillTemplate($searchHref, $query, $limit);

            $descriptionXml = $this->http->get($searchHref, 'application/opensearchdescription+xml,application/xml,text/xml;q=0.9,*/*;q=0.1');
            $description = simplexml_load_string($descriptionXml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
            if ($description === false) throw new RuntimeException('Description OpenSearch invalide pour ' . $this->sourceName . '.');

            foreach ($description->xpath('//*[local-name()="Url"]') ?: [] as $urlNode) {
                $attributes = $urlNode->attributes();
                $template = trim((string) ($attributes['template'] ?? ''));
                $type = trim((string) ($attributes['type'] ?? ''));
                if ($template !== '' && (str_contains($type, 'atom') || str_contains($type, 'opds') || str_contains($template, '{searchTerms'))) {
                    return $this->fillTemplate($this->absoluteUrl($template, $searchHref), $query, $limit);
                }
            }
            throw new RuntimeException('Aucun modèle de recherche OPDS utilisable pour ' . $this->sourceName . '.');
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function fillTemplate(string $template, string $query, int $limit): string
    {
        $template = str_replace(['{searchTerms}', '{searchTerms?}'], rawurlencode($query), $template);
        return preg_replace_callback('/\{([^}]+)\}/', static function (array $match) use ($limit): string {
            $name = rtrim($match[1], '?');
            return match ($name) {
                'count' => (string) $limit,
                'startIndex', 'startPage' => '1',
                'language' => 'fr',
                'inputEncoding', 'outputEncoding' => 'UTF-8',
                default => '',
            };
        }, $template) ?? $template;
    }

    private function absoluteUrl(string $url, string $base): string
    {
        if (preg_match('#^https?://#i', $url) === 1) return preg_replace('#^http://#i', 'https://', $url) ?? $url;
        $parts = parse_url($base);
        if (!is_array($parts) || empty($parts['host'])) return $url;
        $origin = ($parts['scheme'] ?? 'https') . '://' . $parts['host'];
        if (str_starts_with($url, '/')) return $origin . $url;
        $path = $parts['path'] ?? '/';
        $directory = rtrim(str_replace('\\', '/', dirname($path)), '/');
        return $origin . ($directory !== '' ? $directory : '') . '/' . ltrim($url, '/');
    }
}
