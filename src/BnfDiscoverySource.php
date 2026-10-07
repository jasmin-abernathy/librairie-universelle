<?php

declare(strict_types=1);

final class BnfDiscoverySource implements DiscoverySource
{
    private const ENDPOINT = 'https://catalogue.bnf.fr/api/SRU';
    private const DC_NS = 'http://purl.org/dc/elements/1.1/';

    public function __construct(private readonly HttpClient $http) {}

    public function key(): string { return 'bnf'; }
    public function name(): string { return 'Catalogue général de la BnF'; }

    public function search(string $query, int $limit = 8): array
    {
        $query = trim($query);
        if ($query === '') return [];
        $limit = max(1, min(20, $limit));

        $cql = sprintf(
            '(bib.anywhere all "%s") and (bib.recordtype any "mon") and (bib.doctype any "a")',
            str_replace(['\\', '"'], ['\\\\', '\\"'], $query)
        );
        $url = self::ENDPOINT . '?' . http_build_query([
            'version' => '1.2',
            'operation' => 'searchRetrieve',
            'query' => $cql,
            'recordSchema' => 'dublincore',
            'maximumRecords' => $limit,
        ], '', '&', PHP_QUERY_RFC3986);

        return $this->parse($this->http->get($url));
    }

    public function parse(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $document = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
            if ($document === false) throw new RuntimeException('Réponse XML BnF invalide.');
            $recordNodes = $document->xpath('//*[local-name()="recordData"]/*') ?: [];
            $results = [];

            foreach ($recordNodes as $node) {
                $values = ['title'=>[], 'creator'=>[], 'contributor'=>[], 'date'=>[], 'language'=>[], 'identifier'=>[]];
                foreach ($node->children(self::DC_NS) as $name => $value) {
                    if (array_key_exists($name, $values)) {
                        $text = trim((string) $value);
                        if ($text !== '') $values[$name][] = $text;
                    }
                }

                $title = trim((string) ($values['title'][0] ?? ''));
                $ark = null;
                foreach ($values['identifier'] as $identifier) {
                    if (preg_match('~ark:/12148/cb[0-9a-z]+~i', $identifier, $match) === 1) {
                        $ark = $match[0];
                        break;
                    }
                }
                if ($title === '' || $ark === null) continue;

                $year = null;
                if (isset($values['date'][0]) && preg_match('/\b(1[0-9]{3}|20[0-9]{2})\b/', $values['date'][0], $match) === 1) {
                    $year = (int) $match[1];
                }
                $lang = mb_strtolower(trim((string) ($values['language'][0] ?? '')), 'UTF-8');
                $lang = match ($lang) { 'fre', 'fra', 'fr' => 'fr', 'eng', 'en' => 'en', '' => null, default => $lang };

                $results[] = [
                    'source_key'=>$this->key(),
                    'source_name'=>$this->name(),
                    'external_id'=>$ark,
                    'title'=>preg_replace('/\s*\[(?:Texte imprimé|Ressource électronique)\]\s*/iu', ' ', $title) ?: $title,
                    'authors'=>array_values(array_unique(array_filter(array_merge($values['creator'], $values['contributor'])))),
                    'year'=>$year,
                    'language'=>$lang,
                    'source_url'=>'https://catalogue.bnf.fr/' . $ark,
                    'access_url'=>'https://catalogue.bnf.fr/' . $ark,
                    'download_url'=>null,
                    'format'=>null,
                    'rights_note'=>'Notice bibliographique BnF : disponibilité et droits à vérifier selon l’édition.',
                    'kind'=>'catalog',
                ];
            }
            return $results;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
