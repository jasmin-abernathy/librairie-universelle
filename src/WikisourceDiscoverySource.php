<?php

declare(strict_types=1);

final class WikisourceDiscoverySource implements DiscoverySource
{
    private const ENDPOINT = 'https://fr.wikisource.org/w/api.php';

    public function __construct(private readonly HttpClient $http) {}
    public function key(): string { return 'wikisource'; }
    public function name(): string { return 'Wikisource francophone'; }

    public function search(string $query, int $limit = 8): array
    {
        $query = trim($query);
        if ($query === '') return [];
        $limit = max(1, min(20, $limit));

        $url = self::ENDPOINT . '?' . http_build_query([
            'action'=>'query',
            'list'=>'search',
            'srsearch'=>$query . ' incategory:"Bon pour export"',
            'srnamespace'=>0,
            'srlimit'=>$limit,
            'format'=>'json',
            'formatversion'=>2,
        ], '', '&', PHP_QUERY_RFC3986);

        $decoded = json_decode($this->http->get($url, 'application/json'), true);
        if (!is_array($decoded)) throw new RuntimeException('Réponse Wikisource invalide.');

        $results = [];
        foreach (($decoded['query']['search'] ?? []) as $item) {
            $title = trim((string) ($item['title'] ?? ''));
            if ($title === '') continue;
            $pageUrl = 'https://fr.wikisource.org/wiki/' . rawurlencode(str_replace(' ', '_', $title));
            $results[] = [
                'source_key'=>$this->key(),
                'source_name'=>$this->name(),
                'external_id'=>'page:' . (string) ($item['pageid'] ?? $title),
                'title'=>$title,
                'authors'=>[],
                'year'=>null,
                'language'=>'fr',
                'source_url'=>$pageUrl,
                'access_url'=>$pageUrl,
                'download_url'=>null,
                'format'=>'HTML / export EPUB',
                'rights_note'=>'Wikisource « Bon pour export » : texte complet et relu, domaine public en France ou licence libre.',
                'kind'=>'free_ebook',
            ];
        }
        return $results;
    }
}
