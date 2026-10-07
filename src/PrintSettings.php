<?php

declare(strict_types=1);

final class PrintSettings
{
    public static function normalize(array $input): array
    {
        $trimSize = trim((string) ($input['print_trim_size'] ?? '140x210'));
        $binding = trim((string) ($input['print_binding'] ?? 'paperback'));
        $chapterStart = trim((string) ($input['print_chapter_start'] ?? 'right'));
        $pageNumberPosition = trim((string) ($input['print_page_number_position'] ?? 'outside'));
        $frontMatterNumbering = trim((string) ($input['print_front_matter_numbering'] ?? 'roman'));
        $bleedMm = (int) ($input['print_bleed_mm'] ?? 0);
        $gutterMode = trim((string) ($input['print_gutter_mode'] ?? 'auto'));

        if (!in_array($trimSize, ['a5', '140x210', '135x215', '152x229'], true)) {
            throw new InvalidArgumentException('Format papier invalide.');
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
        if (!in_array($bleedMm, [0, 3], true)) {
            throw new InvalidArgumentException('Fond perdu invalide.');
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
        return match ($trimSize) {
            'a5' => [148, 210],
            '135x215' => [135, 215],
            '152x229' => [152, 229],
            default => [140, 210],
        };
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
