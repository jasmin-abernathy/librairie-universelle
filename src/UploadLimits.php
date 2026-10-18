<?php
declare(strict_types=1);

/**
 * Actual PHP web upload and POST ceilings. Do not advertise an upload that
 * PHP discards before the controller can validate it.
 */
final class UploadLimits
{
    public static function iniBytes(string $value): int
    {
        $value = trim($value);
        if (!preg_match('/^([0-9]+(?:\.[0-9]+)?)\s*([KMG]?)B?$/i', $value, $matches)) {
            return 0;
        }
        $value = (float) $matches[1];
        $unit = strtoupper($matches[2]);
        $multiplier = match ($unit) {
            'K' => 1024,
            'M' => 1048576,
            'G' => 1073741824,
            default => 1,
        };
        return (int) min(PHP_INT_MAX, $value * $multiplier);
    }

    public static function effective(int $configured, int $phpFile, int $phpPost): int
    {
        $file = $phpFile > 0 ? $phpFile : PHP_INT_MAX;
        $post = $phpPost > 0 ? max(1, $phpPost - 1048576) : PHP_INT_MAX;
        return max(1, min($configured, $file, $post));
    }

    public static function office(array $config): int
    {
        return self::effective(
            min(31457280, (int) $config['max_epub_bytes']),
            self::iniBytes((string) ini_get('upload_max_filesize')),
            self::iniBytes((string) ini_get('post_max_size'))
        );
    }

    public static function postExceeded(int $contentLength): bool
    {
        $postMax = self::iniBytes((string) ini_get('post_max_size'));
        return $postMax > 0 && $contentLength > $postMax;
    }

    public static function officeLabel(array $config): string
    {
        $bytes = self::office($config);
        return number_format($bytes / 1048576, $bytes < 10485760 ? 1 : 0, ',', ' ') . ' Mo';
    }
}
