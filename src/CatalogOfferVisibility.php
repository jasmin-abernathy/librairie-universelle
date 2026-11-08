<?php
declare(strict_types=1);

/** Compatibilité des anciens catalogues : ne jamais montrer les données de démonstration. */
final class CatalogOfferVisibility
{
    private const OLD_SEEDED_URLS = [
        'https://fr.wikisource.org/wiki/Les_Mis%C3%A9rables',
        'https://pret.bnf.fr/resources?author_keyword=George+Orwell',
        'https://e.lavoisier.fr/produit/634185/9782072938269/1984',
        'https://e-librairie.leclerc/product/9782072938245_9782072938245_10060/1984',
    ];

    public static function filter(array $offers): array
    {
        return array_values(array_filter($offers, static function (array $offer): bool {
            if (($offer['work_title'] ?? '') === 'Livre indépendant de test') {
                return false;
            }
            if (($offer['source_name'] ?? '') === 'Partenaire test') {
                return false;
            }
            // Pas de publicité gratuite vers les grandes plateformes commerciales
            // depuis les pages d'accès numériques, y compris les anciennes offres indexées.
            if (($offer['offer_type'] ?? '') === 'ebook'
                && !empty($offer['price_cents'])
                && ($offer['source_name'] ?? '') !== 'Auteurs indépendants validés') {
                return false;
            }

            // Les six anciennes offres préremplies portaient cette date exacte.
            // Une vraie offre réactualisée ultérieurement n'est pas masquée.
            return !(
                ($offer['checked_at'] ?? '') === '2026-09-05'
                && in_array((string)($offer['url'] ?? ''), self::OLD_SEEDED_URLS, true)
            );
        }));
    }
}
