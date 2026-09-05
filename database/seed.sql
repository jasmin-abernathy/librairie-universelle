-- Corpus réel minimal pour le MVP.
-- Le fichier est idempotent afin de pouvoir être rejoué sans dupliquer les données.

INSERT OR IGNORE INTO contributors (id, name, birth_year, death_year, authority_uri) VALUES
(1, 'Victor Hugo', 1802, 1885, 'https://data.bnf.fr/ark:/12148/cb11907966z'),
(2, 'George Orwell', 1903, 1950, 'https://data.bnf.fr/ark:/12148/cb11918228x');

INSERT OR IGNORE INTO works (id, title, original_title, first_publication_year, language, public_domain_status, public_domain_note) VALUES
(1, 'Les Misérables', 'Les Misérables', 1862, 'fr', 'yes', 'Œuvre originale en français dans le domaine public.'),
(2, '1984', 'Nineteen Eighty-Four', 1949, 'en', 'yes', 'Œuvre originale anglaise dans le domaine public en France. Les traductions françaises doivent être vérifiées séparément.'),
(3, 'La Ferme des animaux', 'Animal Farm', 1945, 'en', 'yes', 'Œuvre originale anglaise dans le domaine public en France. Les traductions françaises doivent être vérifiées séparément.'),
(4, 'Hommage à la Catalogne', 'Homage to Catalonia', 1938, 'en', 'yes', 'Œuvre originale anglaise dans le domaine public en France. Les traductions françaises doivent être vérifiées séparément.'),
(5, 'Le Quai de Wigan', 'The Road to Wigan Pier', 1937, 'en', 'yes', 'Œuvre originale anglaise dans le domaine public en France. Les traductions françaises doivent être vérifiées séparément.'),
(6, 'Dans la dèche à Paris et à Londres', 'Down and Out in Paris and London', 1933, 'en', 'yes', 'Œuvre originale anglaise dans le domaine public en France. Les traductions françaises doivent être vérifiées séparément.'),
(7, 'Une histoire birmane', 'Burmese Days', 1934, 'en', 'yes', 'Œuvre originale anglaise dans le domaine public en France. Les traductions françaises doivent être vérifiées séparément.');

INSERT OR IGNORE INTO work_contributors (work_id, contributor_id, role) VALUES
(1, 1, 'author'),
(2, 2, 'author'),
(3, 2, 'author'),
(4, 2, 'author'),
(5, 2, 'author'),
(6, 2, 'author'),
(7, 2, 'author');

INSERT OR IGNORE INTO sources (id, name, source_type, base_url, data_license) VALUES
(1, 'Wikisource', 'public_domain', 'https://fr.wikisource.org', 'Voir les conditions de réutilisation de Wikisource'),
(2, 'Bibliothèque nationale de France', 'library', 'https://catalogue.bnf.fr', 'Données bibliographiques BnF'),
(3, 'BnF - prêt numérique', 'library', 'https://pret.bnf.fr', 'Accès soumis aux conditions du service');

INSERT OR IGNORE INTO editions (id, work_id, title, publisher, publication_date, language, medium, file_format, drm_type) VALUES
(1, 1, 'Les Misérables — édition Émile Testard', 'Émile Testard', '1890', 'fr', 'ebook', 'HTML / exports proposés par la source', 'none'),
(2, 2, 'Nineteen Eighty-Four — texte original', 'Secker & Warburg', '1949', 'en', 'ebook', 'texte', 'none'),
(3, 3, 'Animal Farm — texte original', 'Secker & Warburg', '1945', 'en', 'ebook', 'texte', 'none');

INSERT OR IGNORE INTO offers (id, source_id, work_id, edition_id, offer_type, price_cents, currency, availability, url, file_format, drm_type, checked_at) VALUES
(1, 1, 1, 1, 'read_online', 0, 'EUR', 'available', 'https://fr.wikisource.org/wiki/Les_Mis%C3%A9rables', 'HTML', 'none', '2026-09-05'),
(2, 1, 1, 1, 'free_download', 0, 'EUR', 'available_via_source_exports', 'https://fr.wikisource.org/wiki/Les_Mis%C3%A9rables', 'PDF / autres exports selon la source', 'none', '2026-09-05'),
(3, 3, 2, NULL, 'borrow', 0, 'EUR', 'check_on_source', 'https://pret.bnf.fr/resources?author_keyword=George+Orwell', 'EPUB', NULL, '2026-09-05'),
(4, 3, 3, NULL, 'borrow', 0, 'EUR', 'check_on_source', 'https://pret.bnf.fr/resources?author_keyword=George+Orwell', 'EPUB', NULL, '2026-09-05');

INSERT OR IGNORE INTO source_records (source_id, external_id, entity_type, local_id, source_url) VALUES
(2, 'ark:/12148/cb11907966z', 'contributor', 1, 'https://data.bnf.fr/ark:/12148/cb11907966z'),
(2, 'ark:/12148/cb11918228x', 'contributor', 2, 'https://data.bnf.fr/ark:/12148/cb11918228x');
