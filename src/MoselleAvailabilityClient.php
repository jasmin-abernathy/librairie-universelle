<?php

declare(strict_types=1);

final class MoselleAvailabilityClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly int $timeoutSeconds = 2
    ) {
    }

    public function availabilityForIsbns(array $isbns): array
    {
        if (trim($this->baseUrl) === '') {
            return [];
        }

        $clean = [];
        foreach ($isbns as $value) {
            $isbn = preg_replace('/[^0-9]/', '', (string) $value);
            if (is_string($isbn) && preg_match('/^97[89][0-9]{10}$/', $isbn)) {
                $clean[$isbn] = true;
            }
        }
        $clean = array_slice(array_keys($clean), 0, 50);
        if ($clean === []) {
            return [];
        }

        $url = rtrim($this->baseUrl, '/') . '/api.php?action=availability-batch&isbns=' . rawurlencode(implode(',', $clean));

        try {
            $body = $this->get($url);
            $decoded = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
            return is_array($decoded['availability'] ?? null) ? $decoded['availability'] : [];
        } catch (Throwable) {
            // La couche locale ne doit jamais empêcher l'affichage de la fiche œuvre.
            return [];
        }
    }

    private function get(string $url): string
    {
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            if ($curl === false) {
                throw new RuntimeException('cURL indisponible.');
            }
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 2,
                CURLOPT_CONNECTTIMEOUT => 1,
                CURLOPT_TIMEOUT => $this->timeoutSeconds,
                CURLOPT_USERAGENT => 'LibrairieUniverselle/0.1 MoselleAvailability',
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
            ]);
            $body = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            curl_close($curl);
            if (!is_string($body) || $status < 200 || $status >= 300) {
                throw new RuntimeException('Service Moselle indisponible.');
            }
            return $body;
        }

        $context = stream_context_create(['http' => [
            'timeout' => $this->timeoutSeconds,
            'header' => "Accept: application/json\r\nUser-Agent: LibrairieUniverselle/0.1 MoselleAvailability\r\n",
        ]]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            throw new RuntimeException('Service Moselle indisponible.');
        }
        return $body;
    }
}
