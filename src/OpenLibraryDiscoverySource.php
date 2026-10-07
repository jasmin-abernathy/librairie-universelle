<?php

declare(strict_types=1);

final class OpenLibraryDiscoverySource implements DiscoverySource
{
    private const ENDPOINT = 'https://openlibrary.org/search.json';

    public function __construct(private readonly HttpClient $http) {}
    public function key(): string { return 'openlibrary'; }
    public function name(): string { return 'Open Library'; }

    public function search(string $query, int $limit = 8): array
    {
        $query = trim($query);
        if ($query === '') return [];
        $limit = max(1, min(20, $limit));
        $url = self::ENDPOINT . '?' . http_build_query([
            'q'=>$query,
            'limit'=>$limit,
            'fields'=>'key,title,author_name,first_publish_year,language,public_scan_b,ebook_access',
        ], '', '&', PHP_QUERY_RFC3986);

        $decoded = json_decode($this->http->get($url, 'application/json'), true);
        if (!is_array($decoded)) throw new RuntimeException('Réponse Open Library invalide.');

        $results = [];
        foreach (($decoded['docs'] ?? []) as $item) {
            $key = trim((string) ($item['key'] ?? ''));
            $title = trim((string) ($item['title'] ?? ''));
            if ($key === '' || $title === '') continue;
            $public = ($item['ebook_access'] ?? '') === 'public' || !empty($item['public_scan_b']);
            $accessUrl = 'https://openlibrary.org' . $key;
            $results[] = [
                'source_key'=>$this->key(),
                'source_name'=>$this->name(),
                'external_id'=>ltrim($key, '/'),
                'title'=>$title,
                'authors'=>array_values(array_filter(array_map('strval', $item['author_name'] ?? []))),
                'year'=>isset($item['first_publish_year']) ? (int) $item['first_publish_year'] : null,
                'language'=>isset($item['language'][0]) ? (string) $item['language'][0] : null,
                'source_url'=>$accessUrl,
                'access_url'=>$accessUrl,
                'download_url'=>null,
                'format'=>$public ? 'Lecture numérique signalée' : null,
                'rights_note'=>$public
                    ? 'Accès public signalé par Open Library ; vérifier la disponibilité et les droits dans votre territoire.'
                    : 'Notice bibliographique Open Library ; aucune liberté de téléchargement n’est déduite automatiquement.',
                'kind'=>$public ? 'digital_access' : 'catalog',
            ];
        }
        return $results;
    }
}
