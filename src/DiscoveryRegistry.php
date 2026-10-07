<?php

declare(strict_types=1);

final class DiscoveryRegistry
{
    public static function definitions(): array
    {
        return [
            'bnf'=>['name'=>'BnF', 'description'=>'Catalogue bibliographique français'],
            'gallica'=>['name'=>'Gallica', 'description'=>'EPUB patrimoniaux BnF'],
            'wikisource'=>['name'=>'Wikisource FR', 'description'=>'Textes relus et exportables'],
            'elg'=>['name'=>'Ebooks Libres et Gratuits', 'description'=>'Catalogue OPDS francophone'],
            'bnr'=>['name'=>'Bibliothèque numérique romande', 'description'=>'Classiques francophones en EPUB'],
            'gutenberg'=>['name'=>'Project Gutenberg', 'description'=>'Grand catalogue international du domaine public'],
            'standardebooks'=>['name'=>'Standard Ebooks', 'description'=>'Éditions EPUB soignées indexées via GitHub'],
            'openlibrary'=>['name'=>'Open Library', 'description'=>'Catalogue international et accès numériques'],
            'doab'=>['name'=>'DOAB', 'description'=>'Livres académiques en open access'],
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
}
