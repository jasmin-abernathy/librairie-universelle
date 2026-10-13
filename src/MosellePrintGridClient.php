<?php
declare(strict_types=1);

/**
 * Ne lit que les grilles publiables, consenties et datées par le pilote Moselle.
 * Ne divulgue aucune grille interne, ne fabrique aucun prix manquant.
 */
final class MosellePrintGridClient
{
    public function __construct(private readonly string $baseUrl, private readonly int $timeoutSeconds = 2) {}

    public function publishedGrids(): array
    {
        if ($this->baseUrl === '') return [];
        $url = rtrim($this->baseUrl, '/') . '/api.php?action=print-price-grids';
        try {
            if (function_exists('curl_init')) {
                $curl = curl_init($url);
                if ($curl === false) return [];
                curl_setopt_array($curl, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_CONNECTTIMEOUT => 1,
                    CURLOPT_TIMEOUT => $this->timeoutSeconds,
                    CURLOPT_HTTPHEADER => ['Accept: application/json'],
                    CURLOPT_USERAGENT => 'LibrairieUniverselle/0.1 PrintGrids',
                ]);
                $body = curl_exec($curl);
                $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
                curl_close($curl);
            } else {
                $context = stream_context_create(['http'=>[
                    'timeout'=>$this->timeoutSeconds, 'ignore_errors'=>true,
                    'header'=>"Accept: application/json\r\nUser-Agent: LibrairieUniverselle/0.1 PrintGrids\r\n"
                ]]);
                $body = @file_get_contents($url, false, $context);
                $status = (isset($http_response_header[0]) && preg_match('/HTTP\/\S+\s+(\d{3})/', $http_response_header[0], $m)) ? (int) $m[1] : 0;
            }
            if (!is_string($body) || $status !== 200 || strlen($body) > 64000) return [];
            return self::normalizeResponse(json_decode($body, true, 32, JSON_THROW_ON_ERROR));
        } catch (Throwable) {
            // Une panne du pilote Moselle n'empêche jamais le dépôt d'un manuscrit.
            return [];
        }
    }

    public static function normalizeResponse(mixed $payload): array
    {
        if (!is_array($payload) || !is_array($payload['grids'] ?? null)) return [];
        $out = [];
        foreach (array_slice($payload['grids'], 0, 10) as $grid) {
            if (!is_array($grid) || !is_array($grid['amounts_cents'] ?? null)) continue;
            if (($grid['currency'] ?? '') !== 'EUR' || ($grid['tax_included'] ?? false) !== true) continue;
            $printer = trim((string) ($grid['printer'] ?? ''));
            $until = (string) ($grid['valid_until'] ?? '');
            if ($printer === '' || strlen($printer) > 160 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $until)
                || $until < date('Y-m-d')) continue;
            $prices = [];
            foreach ($grid['amounts_cents'] as $pages => $quantities) {
                if (!ctype_digit((string) $pages) || !is_array($quantities)) continue;
                foreach ($quantities as $quantity => $cents) {
                    if (!ctype_digit((string) $quantity) || !is_int($cents) || $cents <= 0) continue;
                    $prices[(string) $pages][(string) $quantity] = $cents;
                }
            }
            if ($prices !== []) $out[] = [
                'printer' => $printer,
                'valid_until' => $until,
                'amounts_cents' => $prices,
                // La grille A5/broché 80 g est la seule spécification publiée par ce pilote.
                'format' => 'a5', 'binding' => 'paperback'
            ];
        }
        return $out;
    }
}
