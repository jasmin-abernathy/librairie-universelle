-- Corpus réel minimal pour le MVP.
-- Le fichier est idempotent afin de pouvoir être rejoué sans dupliquer les données.

INSERT OR IGNORE INTO contributors (id, name, birth_year, death_year, authority_uri) VALUES
(1, 'Victor Hugo', 1802, 1885, 'https://data.bnf.fr/ark:/12148/cb11907966z'),
(2, 'George Orwell', 1903, 1950, 'https://data.bnf.fr/ark:/12148/cb11918228x'),
(3, 'Alexandre Dumas', 1802, 1870, NULL),
(4, 'Jules Verne', 1828, 1905, NULL),
(5, 'Jane Austen', 1775, 1817, NULL),
(6, 'Albert Camus', 1913, 1960, NULL),
(7, 'Frank Herbert', 1920, 1986, NULL),
(8, 'Ursula K. Le Guin', 1929, 2018, NULL),
(9, 'Antoine de Saint-Exupéry', 1900, 1944, NULL),
(10, 'J. R. R. Tolkien', 1892, 1973, NULL);

INSERT OR IGNORE INTO works (id, title, original_title, first_publication_year, language, public_domain_status, public_domain_note) VALUES
(1, 'Les Misérables', 'Les Misérables', 1862, 'fr', 'yes', 'Œuvre originale en français dans le domaine public.'),
(2, '1984', 'Nineteen Eighty-Four', 1949, 'en', 'yes', 'Œuvre originale anglaise dans le domaine public en France. Les traductions françaises doivent être vérifiées séparément.'),
(3, 'La Ferme des animaux', 'Animal Farm', 1945, 'en', 'yes', 'Œuvre originale anglaise dans le domaine public en France. Les traductions françaises doivent être vérifiées séparément.'),
(4, 'Hommage à la Catalogne', 'Homage to Catalonia', 1938, 'en', 'yes', 'Œuvre originale anglaise dans le domaine public en France. Les traductions françaises doivent être vérifiées séparément.'),
(5, 'Le Quai de Wigan', 'The Road to Wigan Pier', 1937, 'en', 'yes', 'Œuvre originale anglaise dans le domaine public en France. Les traductions françaises doivent être vérifiées séparément.'),
(6, 'Dans la dèche à Paris et à Londres', 'Down and Out in Paris and London', 1933, 'en', 'yes', 'Œuvre originale anglaise dans le domaine public en France. Les traductions françaises doivent être vérifiées séparément.'),
(7, 'Une histoire birmane', 'Burmese Days', 1934, 'en', 'yes', 'Œuvre originale anglaise dans le domaine public en France. Les traductions françaises doivent être vérifiées séparément.'),
(8, 'Notre-Dame de Paris', 'Notre-Dame de Paris', 1831, 'fr', 'yes', 'Œuvre originale en français dans le domaine public.'),
(9, 'Le Dernier Jour d’un condamné', 'Le Dernier Jour d’un condamné', 1829, 'fr', 'yes', 'Œuvre originale en français dans le domaine public.'),
(10, 'Le Comte de Monte-Cristo', 'Le Comte de Monte-Cristo', 1844, 'fr', 'yes', 'Œuvre originale en français dans le domaine public.'),
(11, 'Les Trois Mousquetaires', 'Les Trois Mousquetaires', 1844, 'fr', 'yes', 'Œuvre originale en français dans le domaine public.'),
(12, 'Vingt mille lieues sous les mers', 'Vingt mille lieues sous les mers', 1869, 'fr', 'yes', 'Œuvre originale en français dans le domaine public.'),
(13, 'Le Tour du monde en quatre-vingts jours', 'Le Tour du monde en quatre-vingts jours', 1872, 'fr', 'yes', 'Œuvre originale en français dans le domaine public.'),
(14, 'Orgueil et Préjugés', 'Pride and Prejudice', 1813, 'en', 'yes', 'Œuvre originale anglaise dans le domaine public. Les traductions françaises doivent être vérifiées séparément.'),
(15, 'Emma', 'Emma', 1815, 'en', 'yes', 'Œuvre originale anglaise dans le domaine public. Les traductions françaises doivent être vérifiées séparément.'),
(16, 'L’Étranger', 'L’Étranger', 1942, 'fr', 'no', 'Albert Camus est mort en 1960 : l’œuvre reste protégée en France selon la règle générale en 2026.'),
(17, 'La Peste', 'La Peste', 1947, 'fr', 'no', 'Albert Camus est mort en 1960 : l’œuvre reste protégée en France selon la règle générale en 2026.'),
(18, 'Dune', 'Dune', 1965, 'en', 'no', 'Frank Herbert est mort en 1986 : l’œuvre originale reste protégée en France.'),
(19, 'Les Dépossédés', 'The Dispossessed', 1974, 'en', 'no', 'Ursula K. Le Guin est morte en 2018 : l’œuvre originale reste protégée en France.'),
(20, 'Le Petit Prince', 'Le Petit Prince', 1943, 'fr', 'review', 'Cas juridique particulier en France lié notamment au statut de Mort pour la France de Saint-Exupéry : ne pas automatiser le domaine public.'),
(21, 'Le Hobbit', 'The Hobbit', 1937, 'en', 'no', 'J. R. R. Tolkien est mort en 1973 : l’œuvre originale reste protégée en France.');

INSERT OR IGNORE INTO work_contributors (work_id, contributor_id, role) VALUES
(1, 1, 'author'),
(2, 2, 'author'),
(3, 2, 'author'),
(4, 2, 'author'),
(5, 2, 'author'),
(6, 2, 'author'),
(7, 2, 'author'),
(8, 1, 'author'),
(9, 1, 'author'),
(10, 3, 'author'),
(11, 3, 'author'),
(12, 4, 'author'),
(13, 4, 'author'),
(14, 5, 'author'),
(15, 5, 'author'),
(16, 6, 'author'),
(17, 6, 'author'),
(18, 7, 'author'),
(19, 8, 'author'),
(20, 9, 'author'),
(21, 10, 'author');

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
