<?php

declare(strict_types=1);

final class HttpClient
{
    public function __construct(
        private readonly int $timeoutSeconds = 12,
        private readonly string $userAgent = 'LibrairieUniverselleMVP/0.1 (+https://github.com/jasmin-abernathy/librairie-universelle)'
    ) {
    }

    public function get(string $url): string
    {
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            if ($curl === false) {
                throw new RuntimeException('Impossible d\'initialiser cURL.');
            }

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_CONNECTTIMEOUT => $this->timeoutSeconds,
                CURLOPT_TIMEOUT => $this->timeoutSeconds,
                CURLOPT_USERAGENT => $this->userAgent,
                CURLOPT_HTTPHEADER => ['Accept: application/xml,text/xml;q=0.9,*/*;q=0.1'],
            ]);

            $body = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            if (!is_string($body) || $status < 200 || $status >= 300) {
                throw new RuntimeException(sprintf('Erreur HTTP %d pour %s%s', $status, $url, $error !== '' ? ' — ' . $error : ''));
            }

            return $body;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeoutSeconds,
                'header' => "User-Agent: {$this->userAgent}\r\nAccept: application/xml,text/xml;q=0.9,*/*;q=0.1\r\n",
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            throw new RuntimeException('Impossible de récupérer ' . $url . '.');
        }

        return $body;
    }
}
