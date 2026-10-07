<?php

declare(strict_types=1);

final class StandardEbooksGithubSource implements DiscoverySource
{
    private const ENDPOINT = 'https://api.github.com/search/repositories';

    public function __construct(private readonly HttpClient $http) {}
    public function key(): string { return 'standardebooks'; }
    public function name(): string { return 'Standard Ebooks'; }

    public function search(string $query, int $limit = 8): array
    {
        $query = trim($query);
        if ($query === '') return [];
        $limit = max(1, min(15, $limit));
        $url = self::ENDPOINT . '?' . http_build_query([
            'q'=>$query . ' org:standardebooks fork:false',
            'per_page'=>$limit,
        ], '', '&', PHP_QUERY_RFC3986);

        $decoded = json_decode($this->http->get($url, 'application/vnd.github+json,application/json'), true);
        if (!is_array($decoded)) throw new RuntimeException('Réponse GitHub Standard Ebooks invalide.');

        $results = [];
        foreach (($decoded['items'] ?? []) as $item) {
            $description = trim((string) ($item['description'] ?? ''));
            $repoName = trim((string) ($item['name'] ?? ''));
            if ($repoName === '' || stripos($description, 'Standard Ebooks edition of') === false) continue;

            $title = '';
            $authors = [];
            if (preg_match('/edition of (.+), by (.+?)(?:\. Translated by .*)?$/u', $description, $match) === 1) {
                $title = trim($match[1]);
                $authors = [trim($match[2])];
            }
            if ($title === '') {
                $parts = explode('_', $repoName);
                $title = isset($parts[1]) ? ucwords(str_replace('-', ' ', $parts[1])) : ucwords(str_replace('-', ' ', $repoName));
            }

            $bookUrl = 'https://standardebooks.org/ebooks/' . str_replace('_', '/', $repoName);
            $repoUrl = trim((string) ($item['html_url'] ?? 'https://github.com/standardebooks/' . $repoName));
            $results[] = [
                'source_key'=>$this->key(),
                'source_name'=>$this->name(),
                'external_id'=>'standardebooks/' . $repoName,
                'title'=>$title,
                'authors'=>$authors,
                'year'=>null,
                'language'=>'en',
                'source_url'=>$repoUrl,
                'access_url'=>$bookUrl,
                'download_url'=>null,
                'format'=>'EPUB',
                'rights_note'=>'Standard Ebooks s’appuie sur le domaine public américain : vérifier séparément le statut de l’œuvre et de la traduction en France.',
                'kind'=>'free_ebook_review',
            ];
        }
        return $results;
    }
}
