<?php

declare(strict_types=1);

final class DiscoveryCache
{
    private string $directory;

    public function __construct(string $storagePath, private readonly int $ttlSeconds = 1800)
    {
        $this->directory = rtrim($storagePath, '/') . '/search-cache';
    }

    public function remember(string $sourceKey, string $query, callable $loader): array
    {
        $normalized = mb_strtolower(trim($query), 'UTF-8');
        $safeSource = preg_replace('/[^a-z0-9_-]+/i', '-', $sourceKey) ?: 'source';
        $path = $this->directory . '/' . $safeSource . '-' . hash('sha256', $normalized) . '.json';

        if (is_file($path) && (time() - (int) filemtime($path)) <= max(60, $this->ttlSeconds)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded)) {
                return ['results' => $decoded, 'cached' => true];
            }
        }

        $results = $loader();
        if (!is_array($results)) {
            throw new RuntimeException('Résultat de recherche invalide.');
        }

        if (!is_dir($this->directory) && !mkdir($this->directory, 0770, true) && !is_dir($this->directory)) {
            return ['results' => $results, 'cached' => false];
        }

        $encoded = json_encode($results, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (is_string($encoded)) {
            $temp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
            if (file_put_contents($temp, $encoded, LOCK_EX) !== false) {
                @chmod($temp, 0660);
                @rename($temp, $path);
            }
            @unlink($temp);
        }

        return ['results' => $results, 'cached' => false];
    }
}
