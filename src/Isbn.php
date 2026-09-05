<?php

declare(strict_types=1);

final class Isbn
{
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(preg_replace('/[^0-9X]/i', '', trim($value)) ?? '');
        return $normalized === '' ? null : $normalized;
    }

    public static function isValid(?string $value): bool
    {
        $isbn = self::normalize($value);
        if ($isbn === null) {
            return true;
        }

        return strlen($isbn) === 13 ? self::isValid13($isbn) : (strlen($isbn) === 10 && self::isValid10($isbn));
    }

    public static function isValid13(string $isbn): bool
    {
        if (!preg_match('/^\d{13}$/', $isbn)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += ((int) $isbn[$i]) * ($i % 2 === 0 ? 1 : 3);
        }

        $check = (10 - ($sum % 10)) % 10;
        return $check === (int) $isbn[12];
    }

    public static function isValid10(string $isbn): bool
    {
        if (!preg_match('/^\d{9}[\dX]$/', $isbn)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $digit = $isbn[$i] === 'X' ? 10 : (int) $isbn[$i];
            $sum += $digit * (10 - $i);
        }

        return $sum % 11 === 0;
    }
}
