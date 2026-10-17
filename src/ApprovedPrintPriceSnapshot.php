<?php
declare(strict_types=1);

/**
 * Copie locale explicitement autorisée du devis Corep, utile lorsque le
 * pilote Moselle n'est pas connecté. Échéance de réexamen : pas une garantie.
 */
final class ApprovedPrintPriceSnapshot
{
    public static function grids(?string $today = null): array
    {
        $path = dirname(__DIR__) . '/data/corep-metz-approved.json';
        $data = json_decode((string) file_get_contents($path), true);
        $today ??= date('Y-m-d');
        if (!is_array($data) || ($data['review_on'] ?? '') < $today
            || ($data['currency'] ?? '') !== 'EUR' || ($data['tax_included'] ?? false) !== true) {
            return [];
        }
        $prices = $data['amounts_cents'] ?? null;
        if (!is_array($prices) || count($prices) !== 3
            || array_sum(array_map('count', $prices)) !== 12) return [];
        foreach ($prices as $quantities) {
            foreach ($quantities as $cents) {
                if (!is_int($cents) || $cents < 1) return [];
            }
        }
        return [[
            'printer' => 'Corep Metz',
            'format' => 'a5',
            'binding' => 'paperback',
            'valid_until' => $data['review_on'],
            'review_only' => true,
            'amounts_cents' => $prices,
        ]];
    }
}
