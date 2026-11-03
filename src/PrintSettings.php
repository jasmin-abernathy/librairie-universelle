<?php

declare(strict_types=1);

final class PrintSettings
{
    // Recommandations communiquées par Corep Metz pour l'A5 recto-verso, papier 80 g.
    public const COREP_MAX_PAGES = 680; // 340 feuilles imprimées des deux côtés.
    public const COREP_RECOMMENDED_FONT_PT = 10.0;
    public const COREP_MIN_FONT_PT = 9.0;

    // Dimensions finies publiées par Bookelis (en mm). Corep garde A5 uniquement.
    private const BOOKELIS_SIZES = [
        'a5' => [148, 210],
        '130x180' => [130, 180], '156x234' => [156, 234],
        '170x244' => [170, 244], '170x170' => [170, 170],
        '190x150' => [190, 150], 'a4' => [210, 297],
    ];

    public static function normalize(array $input): array
    {
        $provider = trim((string) ($input['print_provider'] ?? 'corep_metz'));
        if (!in_array($provider, ['corep_metz', 'bookelis'], true)) {
            throw new InvalidArgumentException('Imprimeur inconnu.');
        }
        $trimSize = trim((string) ($input['print_trim_size'] ?? 'a5'));
        $binding = trim((string) ($input['print_binding'] ?? 'paperback'));
        $chapterStart = trim((string) ($input['print_chapter_start'] ?? 'right'));
        $pageNumberPosition = trim((string) ($input['print_page_number_position'] ?? 'outside'));
        $frontMatterNumbering = trim((string) ($input['print_front_matter_numbering'] ?? 'roman'));
        $bleedMm = (int) ($input['print_bleed_mm'] ?? 0);
        $gutterMode = trim((string) ($input['print_gutter_mode'] ?? 'auto'));

        $sizes = $provider === 'corep_metz' ? ['a5'] : array_keys(self::BOOKELIS_SIZES);
        if (!in_array($trimSize, $sizes, true)) {
            throw new InvalidArgumentException('Format indisponible pour cet imprimeur.');
        }
        if (!in_array($binding, ['paperback', 'hardcover'], true)) {
            throw new InvalidArgumentException('Type de reliure invalide.');
        }
        if (!in_array($chapterStart, ['right', 'next'], true)) {
            throw new InvalidArgumentException('Règle de début de chapitre invalide.');
        }
        if (!in_array($pageNumberPosition, ['outside', 'center', 'none'], true)) {
            throw new InvalidArgumentException('Position des numéros de page invalide.');
        }
        if (!in_array($frontMatterNumbering, ['roman', 'hidden', 'arabic'], true)) {
            throw new InvalidArgumentException('Numérotation des pages liminaires invalide.');
        }
        if (!in_array($bleedMm, [0, 3], true) || ($provider === 'bookelis' && $bleedMm !== 0)) {
            throw new InvalidArgumentException('Pour Bookelis, les éléments à fond perdu doivent être préparés avec le gabarit officiel. Ce générateur ne valide pas encore les fonds perdus Bookelis.');
        }
        if (!in_array($gutterMode, ['auto', 'custom'], true)) {
            throw new InvalidArgumentException('Réglage de marge intérieure invalide.');
        }

        $gutterMm = null;
        if ($gutterMode === 'custom') {
            $rawGutter = str_replace(',', '.', trim((string) ($input['print_gutter_mm'] ?? '')));
            if ($rawGutter === '' || !is_numeric($rawGutter)) {
                throw new InvalidArgumentException('Indiquez une marge intérieure en millimètres.');
            }
            $gutterMm = (float) $rawGutter;
            if ($gutterMm < 5 || $gutterMm > 40) {
                throw new InvalidArgumentException('La marge intérieure personnalisée doit être comprise entre 5 et 40 mm.');
            }
        }

        return [
            'provider' => $provider,
            'trim_size' => $trimSize,
            'binding' => $binding,
            'toc_enabled' => !empty($input['print_toc_enabled']) ? 1 : 0,
            'chapter_start' => $chapterStart,
            'page_number_position' => $pageNumberPosition,
            'hide_chapter_openers' => !empty($input['print_hide_chapter_openers']) ? 1 : 0,
            'front_matter_numbering' => $frontMatterNumbering,
            'bleed_mm' => $bleedMm,
            'gutter_mode' => $gutterMode,
            'gutter_mm' => $gutterMm,
        ];
    }

    public static function trimDimensions(string $trimSize): array
    {
        if ($trimSize === 'a5') return [148, 210];
        if (!isset(self::BOOKELIS_SIZES[$trimSize])) {
            throw new InvalidArgumentException('Format de livre inconnu.');
        }
        return self::BOOKELIS_SIZES[$trimSize];
    }

    public static function automaticGutter(int $pageCountEstimate, string $binding): float
    {
        $gutter = match (true) {
            $pageCountEstimate <= 150 => 16.0,
            $pageCountEstimate <= 300 => 19.0,
            $pageCountEstimate <= 500 => 22.0,
            default => 25.0,
        };

        return $binding === 'hardcover' ? $gutter + 2.0 : $gutter;
    }
}
