<?php

declare(strict_types=1);

final class GallicaDiscoverySource implements DiscoverySource
{
    private const ENDPOINT = 'https://gallica.bnf.fr/services/engine/search/opds';

    public function __construct(private readonly HttpClient $http) {}
    public function key(): string { return 'gallica'; }
    public function name(): string { return 'Gallica EPUB'; }

    public function search(string $query, int $limit = 8): array
    {
        $query = trim($query);
        if ($query === '') return [];
        $limit = max(1, min(20, $limit));
        $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $query);
        $cql = 'gallica all "' . $escaped . '" and dc.formatspecific all "epub" and provenance all "bnf.fr"';
        $url = self::ENDPOINT . '?' . http_build_query([
            'operation'=>'searchRetrieve',
            'version'=>'1.2',
            'exactSearch'=>'false',
            'query'=>$cql,
            'startRecord'=>1,
            'maximumRecords'=>$limit,
        ], '', '&', PHP_QUERY_RFC3986);

        $entries = (new GallicaOpdsSource($this->http))->parse($this->http->get($url));
        $results = [];
        foreach ($entries as $entry) {
            $readUrl = (string) ($entry['read_url'] ?? '');
            $downloadUrl = (string) ($entry['epub_url'] ?? '');
            $results[] = [
                'source_key'=>$this->key(),
                'source_name'=>$this->name(),
                'external_id'=>(string) $entry['external_id'],
                'title'=>(string) $entry['title'],
                'authors'=>array_values($entry['authors'] ?? []),
                'year'=>null,
                'language'=>'fr',
                'source_url'=>$readUrl !== '' ? $readUrl : $downloadUrl,
                'access_url'=>$readUrl !== '' ? $readUrl : $downloadUrl,
                'download_url'=>$downloadUrl !== '' ? $downloadUrl : null,
                'format'=>'EPUB',
                'rights_note'=>'EPUB Gallica produit par la BnF à partir d’un ouvrage du domaine public.',
                'kind'=>'free_ebook',
            ];
        }
        return $results;
    }
}
