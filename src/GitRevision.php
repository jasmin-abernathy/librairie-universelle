<?php
declare(strict_types=1);

/** Read the deployed local Git HEAD without executing a shell command. */
final class GitRevision
{
    public static function read(string $projectRoot): ?string
    {
        $git = rtrim($projectRoot, '/') . '/.git';
        if (!is_dir($git)) return null;
        $head = @file_get_contents($git . '/HEAD');
        if (!is_string($head) || strlen($head) > 4096) return null;
        $head = trim($head);
        if (self::validSha($head)) return strtolower($head);
        if (!preg_match('~^ref:\s+(refs/heads/[A-Za-z0-9._/-]+)$~D', $head, $match)) return null;
        $ref = $match[1];
        if (str_contains($ref, '..')) return null;
        $loose = @file_get_contents($git . '/' . $ref);
        if (is_string($loose) && self::validSha(trim($loose))) return strtolower(trim($loose));
        $packed = @file_get_contents($git . '/packed-refs');
        if (!is_string($packed) || strlen($packed) > 5242880) return null;
        if (preg_match('/^([a-f0-9]{40}) ' . preg_quote($ref, '/') . '$/mi', $packed, $found)) {
            return strtolower($found[1]);
        }
        return null;
    }

    private static function validSha(string $value): bool
    {
        return (bool) preg_match('/^[a-f0-9]{40}$/iD', $value);
    }
}
