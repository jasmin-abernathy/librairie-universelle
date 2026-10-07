<?php

declare(strict_types=1);

final class DoabDiscoverySource implements DiscoverySource
{
    private const ENDPOINT = 'https://directory.doabooks.org/rest/search';

    public function __construct(private readonly HttpClient $http) {}
    public function key(): string { return 'doab'; }
    public function name(): string { return 'Directory of Open Access Books'; }

    public function search(string $query, int $limit = 8): array
    {
        $query = trim($query);
        if ($query === '') return [];
        $limit = max(1, min(20, $limit));
        $url = self::ENDPOINT . '?' . http_build_query([
            'query'=>$query,
            'expand'=>'metadata',
            'limit'=>$limit,
        ], '', '&', PHP_QUERY_RFC3986);

        $decoded = json_decode($this->http->get($url, 'application/json'), true);
        if (!is_array($decoded)) throw new RuntimeException('Réponse DOAB invalide.');
        $items = array_is_list($decoded) ? $decoded : ($decoded['items'] ?? $decoded['results'] ?? []);
        $results = [];

        foreach ($items as $item) {
            if (!is_array($item)) continue;
            $metadata = [];
            foreach (($item['metadata'] ?? []) as $entry) {
                if (!is_array($entry)) continue;
                $key = trim((string) ($entry['key'] ?? ''));
                $value = trim((string) ($entry['value'] ?? ''));
                if ($key !== '' && $value !== '') $metadata[$key][] = $value;
            }
            $first = static fn (string $key): ?string => isset($metadata[$key][0]) ? (string) $metadata[$key][0] : null;
            $title = trim((string) ($item['name'] ?? $first('dc.title') ?? ''));
            $handle = trim((string) ($item['handle'] ?? ''));
            if ($title === '' || $handle === '') continue;

            $year = null;
            $date = $first('dc.date.issued');
            if ($date !== null && preg_match('/\b(1[0-9]{3}|20[0-9]{2})\b/', $date, $match) === 1) $year = (int) $match[1];
            $rights = $first('dc.rights.uri') ?? $first('dc.rights') ?? 'Licence open access indiquée dans la notice DOAB.';
            $recordUrl = 'https://directory.doabooks.org/handle/' . rawurlencode($handle);

            $results[] = [
                'source_key'=>$this->key(),
                'source_name'=>$this->name(),
                'external_id'=>$handle,
                'title'=>$title,
                'authors'=>array_values(array_unique(array_merge($metadata['dc.contributor.author'] ?? [], $metadata['dc.creator'] ?? []))),
                'year'=>$year,
                'language'=>$first('dc.language.iso') ?? $first('dc.language'),
                'source_url'=>$recordUrl,
                'access_url'=>$recordUrl,
                'download_url'=>null,
                'format'=>'Open access',
                'rights_note'=>'DOAB : ' . trim((string) $rights),
                'kind'=>'open_access',
            ];
        }
        return $results;
    }
}
