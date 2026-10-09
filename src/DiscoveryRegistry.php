<?php

declare(strict_types=1);

final class DiscoveryRegistry
{
    public static function definitions(): array
    {
        return [
            'bnf'=>['name'=>'BnF', 'description'=>'Catalogue bibliographique français', 'languages'=>['fr','en'], 'default_language'=>null, 'ebook_surface'=>false, 'modes'=>['all']],
            'gallica'=>['name'=>'Gallica', 'description'=>'EPUB patrimoniaux BnF', 'languages'=>['fr'], 'default_language'=>'fr', 'ebook_surface'=>true, 'modes'=>['all','free']],
            'wikisource'=>['name'=>'Wikisource FR', 'description'=>'Textes relus et exportables', 'languages'=>['fr'], 'default_language'=>'fr', 'ebook_surface'=>true, 'modes'=>['all','free']],
            'elg'=>['name'=>'Ebooks Libres et Gratuits', 'description'=>'Catalogue OPDS francophone', 'languages'=>['fr'], 'default_language'=>'fr', 'ebook_surface'=>true, 'modes'=>['all','free']],
            'bnr'=>['name'=>'Bibliothèque numérique romande', 'description'=>'Classiques francophones en EPUB', 'languages'=>['fr'], 'default_language'=>'fr', 'ebook_surface'=>true, 'modes'=>['all','free']],
            'gutenberg'=>['name'=>'Project Gutenberg', 'description'=>'Grand catalogue international du domaine public', 'languages'=>['fr','en'], 'default_language'=>null, 'ebook_surface'=>true, 'modes'=>['all','free']],
            'standardebooks'=>['name'=>'Standard Ebooks', 'description'=>'Éditions EPUB soignées indexées via GitHub', 'languages'=>['en'], 'default_language'=>'en', 'ebook_surface'=>true, 'modes'=>['all','free']],
            'openlibrary'=>['name'=>'Open Library', 'description'=>'Catalogue international et accès numériques', 'languages'=>['fr','en'], 'default_language'=>null, 'ebook_surface'=>true, 'modes'=>['all']],
            'doab'=>['name'=>'DOAB', 'description'=>'Livres académiques en open access', 'languages'=>['fr','en'], 'default_language'=>null, 'ebook_surface'=>true, 'modes'=>['all','free']],
        ];
    }

    public static function create(string $key, HttpClient $http): DiscoverySource
    {
        return match ($key) {
            'bnf' => new BnfDiscoverySource($http),
            'gallica' => new GallicaDiscoverySource($http),
            'wikisource' => new WikisourceDiscoverySource($http),
            'elg' => new OpdsDiscoverySource(
                'elg',
                'Ebooks Libres et Gratuits',
                'https://www.ebooksgratuits.com/opds/',
                'ELG autorise librement ses ebooks pour un usage non commercial ; certains auteurs contemporains restent soumis à autorisation.',
                $http
            ),
            'bnr' => new OpdsDiscoverySource(
                'bnr',
                'Bibliothèque numérique romande',
                'https://ebooks-bnr.com/opds/',
                'Catalogue gratuit de la BNR ; le statut juridique doit être vérifié pour la France avant toute réutilisation.',
                $http
            ),
            'gutenberg' => new OpdsDiscoverySource(
                'gutenberg',
                'Project Gutenberg',
                'https://www.gutenberg.org/ebooks/search.opds/',
                'Project Gutenberg raisonne principalement selon le droit américain ; vérifier le statut en France avant réutilisation.',
                $http
            ),
            'standardebooks' => new StandardEbooksGithubSource($http),
            'openlibrary' => new OpenLibraryDiscoverySource($http),
            'doab' => new DoabDiscoverySource($http),
            default => throw new InvalidArgumentException('Source de découverte inconnue.'),
        };
    }

    public static function keys(): array { return array_keys(self::definitions()); }

    public static function supportsLanguages(string $key, array $languages): bool
    {
        $definition = self::definitions()[$key] ?? null;
        if (!is_array($definition)) {
            return false;
        }
        return array_intersect($definition['languages'] ?? [], $languages) !== [];
    }

    public static function supportsEbookSurface(string $key): bool
    {
        $definition = self::definitions()[$key] ?? null;
        return is_array($definition) && !empty($definition['ebook_surface']);
    }

    public static function supportsMode(string $key, string $mode): bool
    {
        if ($mode === 'all') {
            return true;
        }

        $definition = self::definitions()[$key] ?? null;
        if (!is_array($definition)) {
            return false;
        }

        return in_array($mode, $definition['modes'] ?? ['all'], true);
    }

    public static function normalizeLanguage(?string $language): ?string
    {
        $value = mb_strtolower(trim((string) $language), 'UTF-8');
        if ($value === '') {
            return null;
        }

        if (in_array($value, ['fr', 'fra', 'fre', 'fr-fr', 'fr_ca', 'fr-ca'], true) || str_starts_with($value, 'fr-')) {
            return 'fr';
        }
        if (in_array($value, ['en', 'eng', 'en-us', 'en-gb', 'en_us', 'en_gb'], true) || str_starts_with($value, 'en-')) {
            return 'en';
        }

        return null;
    }

    public static function filterResults(string $key, array $results, array $languages): array
    {
        $definition = self::definitions()[$key] ?? [];
        $defaultLanguage = $definition['default_language'] ?? null;
        $bothSelected = count(array_intersect(['fr', 'en'], $languages)) === 2;

        return array_values(array_filter($results, static function (array $result) use ($languages, $defaultLanguage, $bothSelected): bool {
            $language = self::normalizeLanguage(isset($result['language']) ? (string) $result['language'] : null)
                ?? self::normalizeLanguage(is_string($defaultLanguage) ? $defaultLanguage : null);

            if ($language === null) {
                return $bothSelected;
            }

            return in_array($language, $languages, true);
        }));
    }
}
