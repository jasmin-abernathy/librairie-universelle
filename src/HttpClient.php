<?php

declare(strict_types=1);

final class HttpClient
{
    public function __construct(
        private readonly int $timeoutSeconds = 12,
        private readonly string $userAgent = 'LibrairieUniverselleMVP/0.1 (+https://github.com/jasmin-abernathy/librairie-universelle)',
        private readonly int $maxAttempts = 2
    ) {
    }

    public function get(string $url): string
    {
        $lastError = null;
        $attempts = max(1, min(3, $this->maxAttempts));

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                return function_exists('curl_init')
                    ? $this->getWithCurl($url)
                    : $this->getWithStreams($url);
            } catch (RuntimeException $error) {
                $lastError = $error;
                if ($attempt >= $attempts || !$this->isTransient($error->getMessage())) {
                    throw $error;
                }
                usleep(350_000 * $attempt);
            }
        }

        throw $lastError ?? new RuntimeException('Échec HTTP inconnu.');
    }

    private function getWithCurl(string $url): string
    {
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
            $message = sprintf('Erreur HTTP %d pour %s%s', $status, $url, $error !== '' ? ' — ' . $error : '');
            throw new RuntimeException($message);
        }

        return $body;
    }

    private function getWithStreams(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeoutSeconds,
                'ignore_errors' => true,
                'header' => "User-Agent: {$this->userAgent}\r\nAccept: application/xml,text/xml;q=0.9,*/*;q=0.1\r\n",
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        $status = $this->streamStatus($http_response_header ?? []);
        if ($body === false || $status < 200 || $status >= 300) {
            throw new RuntimeException(sprintf('Erreur HTTP %d pour %s', $status, $url));
        }

        return $body;
    }

    private function streamStatus(array $headers): int
    {
        if ($headers === []) {
            return 0;
        }
        if (preg_match('/\s(\d{3})\s/', (string) $headers[0], $matches)) {
            return (int) $matches[1];
        }
        return 0;
    }

    private function isTransient(string $message): bool
    {
        if (preg_match('/Erreur HTTP (429|500|502|503|504)\b/', $message)) {
            return true;
        }

        return preg_match('/Erreur HTTP 0\b/', $message) === 1
            || str_contains($message, 'timed out')
            || str_contains($message, 'Timeout');
    }
}
