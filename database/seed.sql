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
(10, 'J. R. R. Tolkien', 1892, 1973, NULL),
(11, 'Josée Kamoun', NULL, NULL, NULL),
(12, 'Amélie Audiberti', NULL, NULL, NULL),
(13, 'Yves Gohin', NULL, NULL, NULL);

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
(2, 'Bibliothèque nationale de France', 'library', 'https://catalogue.bnf.fr', 'Licence ouverte de l’État — métadonnées BnF'),
(3, 'BnF - prêt numérique', 'library', 'https://pret.bnf.fr', 'Accès soumis aux conditions du service'),
(4, 'Lavoisier', 'bookseller', 'https://e.lavoisier.fr', 'Conditions commerciales du vendeur'),
(5, 'E-librairie Leclerc', 'bookseller', 'https://e-librairie.leclerc', 'Conditions commerciales du vendeur');

INSERT OR IGNORE INTO editions (id, work_id, isbn13, title, publisher, publication_date, language, medium, file_format, drm_type) VALUES
(1, 1, NULL, 'Les Misérables — édition Émile Testard', 'Émile Testard', '1890', 'fr', 'ebook', 'HTML / exports proposés par la source', 'none'),
(2, 2, NULL, 'Nineteen Eighty-Four — texte original', 'Secker & Warburg', '1949', 'en', 'ebook', 'texte', 'none'),
(3, 3, NULL, 'Animal Farm — texte original', 'Secker & Warburg', '1945', 'en', 'ebook', 'texte', 'none'),
(4, 2, '9782072878497', '1984 — Folio, traduction Josée Kamoun', 'Gallimard', '2020', 'fr', 'paper', NULL, NULL),
(5, 2, '9782072730030', '1984 — Du monde entier, traduction Josée Kamoun', 'Gallimard', '2018', 'fr', 'paper', NULL, NULL),
(6, 2, '9782070248100', '1984 — Du monde entier, traduction Amélie Audiberti', 'Gallimard', '2015', 'fr', 'paper', NULL, NULL),
(7, 2, '9782070463695', '1984 — Folioplus classiques, traduction Amélie Audiberti', 'Gallimard', '2015', 'fr', 'paper', NULL, NULL),
(8, 2, '9782070348626', '1984 — Folio, traduction Amélie Audiberti', 'Gallimard', '2007', 'fr', 'paper', NULL, NULL),
(9, 1, '9782070409228', 'Les Misérables — Folio Classique, tome I', 'Gallimard', '1999', 'fr', 'paper', NULL, NULL),
(10, 1, '9782070409235', 'Les Misérables — Folio Classique, tome II', 'Gallimard', '1999', 'fr', 'paper', NULL, NULL),
(11, 2, '9782072938245', '1984 — Folio SF, ebook, traduction Amélie Audiberti', 'Gallimard', '2021-05-13', 'fr', 'ebook', 'EPUB', NULL),
(12, 2, '9782072938221', '1984 — Folio SF, traduction Amélie Audiberti', 'Gallimard', '2021', 'fr', 'paper', NULL, NULL);

INSERT OR IGNORE INTO edition_contributors (edition_id, contributor_id, role) VALUES
(4, 11, 'translator'),
(5, 11, 'translator'),
(6, 12, 'translator'),
(7, 12, 'translator'),
(8, 12, 'translator'),
(9, 13, 'editor'),
(10, 13, 'editor'),
(11, 12, 'translator'),
(12, 12, 'translator');

INSERT OR IGNORE INTO offers (id, source_id, work_id, edition_id, offer_type, price_cents, currency, availability, url, file_format, drm_type, checked_at) VALUES
(1, 1, 1, 1, 'read_online', 0, 'EUR', 'available', 'https://fr.wikisource.org/wiki/Les_Mis%C3%A9rables', 'HTML', 'none', '2026-09-05'),
(2, 1, 1, 1, 'free_download', 0, 'EUR', 'available_via_source_exports', 'https://fr.wikisource.org/wiki/Les_Mis%C3%A9rables', 'PDF / autres exports selon la source', 'none', '2026-09-05'),
(3, 3, 2, NULL, 'borrow', 0, 'EUR', 'check_on_source', 'https://pret.bnf.fr/resources?author_keyword=George+Orwell', 'EPUB', NULL, '2026-09-05'),
(4, 3, 3, NULL, 'borrow', 0, 'EUR', 'check_on_source', 'https://pret.bnf.fr/resources?author_keyword=George+Orwell', 'EPUB', NULL, '2026-09-05'),
(5, 4, 2, 11, 'ebook', 949, 'EUR', 'available_when_checked', 'https://e.lavoisier.fr/produit/634185/9782072938269/1984', 'EPUB', 'Adobe DRM', '2026-09-05'),
(6, 5, 2, 11, 'ebook', 949, 'EUR', 'available_when_checked', 'https://e-librairie.leclerc/product/9782072938245_9782072938245_10060/1984', 'EPUB', 'CARE', '2026-09-05');

INSERT OR IGNORE INTO source_records (source_id, external_id, entity_type, local_id, source_url) VALUES
(2, 'ark:/12148/cb11907966z', 'contributor', 1, 'https://data.bnf.fr/ark:/12148/cb11907966z'),
(2, 'ark:/12148/cb11918228x', 'contributor', 2, 'https://data.bnf.fr/ark:/12148/cb11918228x'),
(2, 'ark:/12148/cb465691674', 'edition', 4, 'https://catalogue.bnf.fr/ark:/12148/cb465691674'),
(2, 'ark:/12148/cb45505564v', 'edition', 5, 'https://catalogue.bnf.fr/ark:/12148/cb45505564v'),
(2, 'ark:/12148/cb44346567g', 'edition', 6, 'https://catalogue.bnf.fr/ark:/12148/cb44346567g'),
(2, 'ark:/12148/cb444267862', 'edition', 7, 'https://catalogue.bnf.fr/ark:/12148/cb444267862'),
(2, 'ark:/12148/cb41167710z', 'edition', 8, 'https://catalogue.bnf.fr/ark:/12148/cb41167710z'),
(2, 'ark:/12148/cb370398428', 'edition', 9, 'https://catalogue.bnf.fr/ark:/12148/cb370398428');

-- Annuaire initial Metz / Moselle, recensé et revérifié le 2026-10-07.
-- Les lignes "review" restent en base mais ne sont jamais exposées par l'API publique.
INSERT INTO bookstores (
    directory_key, name, group_name, address, postal_code, city, category,
    independence_status, specialization, phone, website_url, website_status,
    ordering_status, ordering_notes, directory_status, curation_note,
    source_label, source_url, last_checked
) VALUES
('la-petite-librairie-ars-sur-moselle', 'La Petite Librairie', NULL, '23 Rue du Maréchal Foch', '57130', 'Ars-sur-Moselle', 'librairie', 'indépendante', 'généraliste', '+33 9 86 79 07 19', NULL, 'pas de site propre confirmé', 'à vérifier', 'Entreprise active, commerce de détail de livres confirmé par l''Annuaire des entreprises.', 'listed', NULL, 'Annuaire des entreprises', 'https://annuaire-entreprises.data.gouv.fr/entreprise/la-petite-librairie-la-petite-librairie-898449210', '2026-10-07'),
('ma-p-tite-librairie-clouange', 'Ma p''tite Librairie', NULL, '1 Rue Maréchal Joffre', '57185', 'Clouange', 'librairie', 'indépendante', 'généraliste / événements', '+33 6 99 89 50 31', NULL, 'Facebook identifié, pas de site propre confirmé', 'à vérifier', 'Librairie active et événements locaux confirmés ; commande en ligne à rechercher.', 'listed', NULL, 'Mairie de Clouange', 'https://clouange.fr/commerces.php', '2026-10-07'),
('librairie-patisserie-autonome-forbach', 'Librairie Pâtisserie Autonome', NULL, '69 Rue Nationale', '57600', 'Forbach', 'librairie / café', 'indépendante', 'généraliste, lieu hybride', '+33 6 46 15 20 15', NULL, 'domaine librairieautonome.fr actuellement à vendre', 'commande locale probable, web non opérationnel', 'La librairie est active ; le domaine donné par certains annuaires est actuellement une page de vente de domaine, donc ne pas l''utiliser comme lien public.', 'listed', NULL, 'Office de tourisme Forbach + vérification du domaine', 'https://paysdeforbach.com/fiche-sit/F1306006875_librairie-patisserie-autonome-forbach/', '2026-10-07'),
('antik-bd-metz', 'Antik BD', NULL, '27 Quai Félix Maréchal', '57000', 'Metz', 'librairie spécialisée', 'indépendante probable', 'livres rares / BD', '+33 3 87 18 87 50', NULL, 'aucun site propre confirmé', 'à vérifier', 'À qualifier : neuf/occasion et possibilité de commande.', 'listed', NULL, 'Fiche établissement', NULL, '2026-10-07'),
('atoutlire-bookshop-metz', 'Atoutlire Bookshop', NULL, '2 Rue de la Basse Seille', '57000', 'Metz', 'librairie', 'indépendante historique / à revalider', 'livres', '+33 3 87 65 54 43', 'https://librairesdelest.fr/', 'annuaire/réseau signalé', 'à vérifier', 'Présence 2026 signalée par un annuaire local ; statut et canal de commande doivent être revalidés avant publication.', 'review', 'Présence actuelle à revalider avant affichage public.', 'Bottin.fr', 'https://www.bottin.fr/annuaire/librairie-a-metz-57463.htm', '2026-10-07'),
('au-carre-des-bulles-metz', 'Au Carré des Bulles', NULL, '19 Rue de la Fontaine', '57000', 'Metz', 'librairie spécialisée', 'indépendante', 'BD, graphisme, art, photo', '+33 3 87 35 72 14', NULL, 'la Ville de Metz signale un lien de site, URL à récupérer', 'à vérifier', 'Librairie spécialisée confirmée ; canal de commande en ligne à rechercher.', 'listed', NULL, 'Ville de Metz', 'https://metz.fr/lieux/lieu-233.php', '2026-10-07'),
('au-dela-des-mondes-metz-metz', 'Au-delà des Mondes Metz', 'Au-delà des Mondes', '30 Rue de Verdun', '57000', 'Metz', 'librairie spécialisée', 'petit réseau indépendant', 'imaginaire / culture spécialisée', '+33 3 87 37 75 41', 'https://audeladesmondes.fr/', 'site propre confirmé', 'à vérifier', 'Site et boutique physique confirmés ; commande de livres en ligne à vérifier.', 'listed', NULL, 'Au-delà des Mondes', 'https://audeladesmondes.fr/contact', '2026-10-07'),
('autour-du-monde-metz', 'Autour du Monde', NULL, '44 Rue de la Chèvre', '57000', 'Metz', 'librairie', 'indépendante', 'littérature, voyage, sciences humaines, art, cuisine', '+33 3 87 32 12 03', NULL, 'pas de site propre trouvé ; Facebook identifié', 'non vérifié', 'Click & collect signalé par un annuaire récent ; pas de plateforme de commande générale confirmée.', 'listed', NULL, 'ADELC / Ann Rose', 'https://adelc.fr/les-librairies-aidees/', '2026-10-07'),
('hisler-metz', 'Hisler', 'Groupe Hisler', '1 Rue Ambroise Thomas', '57000', 'Metz', 'librairie', 'groupe régional indépendant', 'généraliste, papeterie', '+33 3 87 75 07 11', 'https://www.hisler.fr/', 'site propre confirmé', 'à vérifier', 'Site propre confirmé ; la page Dalbe/Hisler mentionne livraison et paiement en ligne, mais il faut vérifier que la commande de livres grand public est bien couverte.', 'listed', NULL, 'Ville de Metz + Dalbe Metz', 'https://metz.fr/lieux/lieu-259.php', '2026-10-07'),
('hisler-bd-metz', 'Hisler BD', 'Groupe Hisler', '1 Rue Ambroise Thomas', '57000', 'Metz', 'librairie spécialisée', 'groupe régional indépendant', 'BD, mangas, comics', '+33 3 54 17 05 75', 'https://www.hisler-even.com/', 'site propre confirmé', 'à vérifier', 'Site propre confirmé ; adresse mail historique en canalbd.net, adhésion/commande CanalBD à revalider.', 'listed', NULL, 'Office de tourisme Metz', 'https://www.tourisme-metz.com/fr/details/838165076-librairie-hisler-bd', '2026-10-07'),
('la-cour-des-grands-metz', 'La Cour des Grands', NULL, '12 Rue Taison', '57000', 'Metz', 'librairie', 'indépendante', 'généraliste, littérature, jeunesse', '+33 3 87 65 05 21', 'https://www.librairies-lepreau-lacour.fr/', 'site propre confirmé', 'oui / à consolider', 'Présente dans le localisateur Dargaud avec « Commander en ligne ». Témoignage historique d''utilisation via leslibraires.fr ; présence actuelle sur cette plateforme à revalider.', 'listed', NULL, 'Ville de Metz + Dargaud', 'https://metz.fr/lieux/lieu-243.php', '2026-10-07'),
('la-pensee-sauvage-metz', 'La Pensée Sauvage', NULL, '23 Avenue de Nancy', '57000', 'Metz', 'librairie', 'indépendante', 'littérature, BD, mangas, arts, jeunesse, sciences humaines, nature', '+33 9 73 20 37 25', 'https://librairielapenseesauvage.com/', 'site propre confirmé', 'à vérifier', 'Site propre confirmé ; commande en ligne non vérifiée pendant ce recensement.', 'listed', NULL, 'PagesJaunes', 'https://www.pagesjaunes.fr/pros/62395771', '2026-10-07'),
('la-taniere-de-pixie-metz', 'La Tanière de Pixie', NULL, '28 Rue du Sablon', '57000', 'Metz', 'librairie spécialisée', 'indépendante probable', 'pop culture', '+33 6 18 95 82 01', NULL, 'aucun site propre confirmé', 'à vérifier', 'Commerce récent ; présence web/commande à approfondir.', 'listed', NULL, 'Fiche établissement', NULL, '2026-10-07'),
('le-preau-metz', 'Le Préau', NULL, '11 Rue Taison', '57000', 'Metz', 'librairie', 'indépendante', 'jeunesse / généraliste', '+33 3 87 75 07 16', 'https://www.librairies-lepreau-lacour.fr/', 'site propre confirmé', 'oui / à consolider', 'Site partagé avec La Cour des Grands. Référencé par les Librairies Sorcières. Témoignage historique leslibraires.fr.', 'listed', NULL, 'ADELC + Librairies Sorcières', 'https://adelc.fr/les-librairies-aidees/', '2026-10-07'),
('librairie-momie-metz-metz', 'Librairie Momie Metz', 'Momie', 'Galerie République, 1 Avenue Ney', '57000', 'Metz', 'librairie spécialisée', 'réseau spécialisé', 'BD, comics, mangas, jeunesse, imaginaire', '+33 3 87 74 98 77', 'https://www.momie.fr/', 'site marchand confirmé', 'oui', 'Le réseau Momie indique que tous les produits de ses librairies sont disponibles sur momie.fr.', 'listed', NULL, 'Momie', 'https://quoide9.momie.fr/nos-librairies/momie-metz/', '2026-10-07'),
('librairie-univers-metz', 'Librairie Univers', NULL, '51 Rue des Tanneurs', '57000', 'Metz', 'librairie spécialisée', 'indépendante', 'ésotérisme', '+33 3 87 74 36 11', 'https://librairie-univers.fr/', 'site propre confirmé', 'à vérifier', 'Site propre confirmé ; fonction de commande en ligne non vérifiée.', 'listed', NULL, 'iLibrairie', 'https://ilibrairie.fr/57/metz/librairie-univers-libraire-esoterique-328', '2026-10-07'),
('l-arbre-a-papillons-phalsbourg', 'L''Arbre à Papillons', NULL, '12 Place d''Armes', '57370', 'Phalsbourg', 'librairie-papeterie', 'indépendante probable', 'livres, papeterie, carterie', '+33 7 82 97 65 00', NULL, 'aucun site propre confirmé', 'à vérifier', 'Librairie physique active ; canal de commande à approfondir.', 'listed', NULL, 'Fiche établissement / Rue des livres', 'https://www.rue-des-livres.com/librairies/FR-GE-57/moselle.html', '2026-10-07'),
('cafe-litteraire-de-saint-avold-saint-avold', 'Café littéraire de Saint-Avold', NULL, '17 Place de la Victoire', '57500', 'Saint-Avold', 'café littéraire', 'candidat à confirmer', 'lecture, café, éventuellement vente', '+33 6 72 13 56 80', NULL, 'aucun site marchand confirmé', 'à confirmer', 'À ne pas afficher comme librairie de commande avant vérification de la vente régulière de livres.', 'review', 'Vente régulière de livres non confirmée ; ne pas afficher comme librairie avant vérification.', 'MOSL', 'https://www.mosl.fr/fr/f892146223_cafe-litteraire-de-saint-avold-saint-avold', '2026-10-07'),
('le-ventre-de-la-baleine-sarrebourg', 'Le Ventre de la Baleine', NULL, '3 Rue de la Marne', '57400', 'Sarrebourg', 'librairie', 'indépendante', 'généraliste, jeux', '+33 3 87 23 49 82', NULL, 'site web confirmé, URL à résoudre', 'oui', 'Annuaire vérifié fin septembre 2026 : click & collect confirmé. Adresse e-mail sur domaine leventredelabaleine.com.', 'listed', NULL, 'Ann Rose', 'https://ann-rose.com/librairies?departement=57', '2026-10-07'),
('librairie-confluence-sarreguemines', 'Librairie Confluence', NULL, '5 Rue Sainte-Croix', '57200', 'Sarreguemines', 'librairie', 'indépendante', 'généraliste', '+33 3 87 98 91 80', 'http://www.librairie-confluence.fr/', 'site propre confirmé', 'oui', 'La Ville de Sarreguemines indique livraison, à emporter, click & collect et présence en ligne.', 'listed', NULL, 'Ville de Sarreguemines', 'https://www.sarreguemines.fr/commerces/55-culturel-livres-jeux-video/28-librairie-confluence', '2026-10-07'),
('hisler-bd-bis-thionville', 'Hisler BD Bis', 'Groupe Hisler', '4 Rue du Maillet', '57100', 'Thionville', 'librairie spécialisée', 'groupe régional indépendant', 'BD, comics, mangas, romans jeunesse', '+33 3 82 57 18 42', 'http://www.hislerbdbis-lalibrairie.com/', 'site propre confirmé', 'oui', 'PagesJaunes indique « commande en ligne » ; le localisateur Dargaud propose également « Commander en ligne ».', 'listed', NULL, 'PagesJaunes + Dargaud', 'https://www.pagesjaunes.fr/pros/52212877', '2026-10-07'),
('hisler-tome-5-thionville', 'Hisler Tome 5', 'Groupe Hisler', '46 Rue de Paris', '57100', 'Thionville', 'librairie', 'groupe régional indépendant', 'généraliste, jeunesse, BD, jeux, papeterie', NULL, NULL, 'site non identifié dans ce passage', 'à vérifier', 'Établissement actif confirmé en 2026 ; appartient au groupe Hisler.', 'listed', NULL, 'Pappers / Ville de Thionville', 'https://www.pappers.fr/entreprise/hisler-thionville-900217324', '2026-10-07'),
('l-antre-temps-thionville', 'L''Antre Temps', NULL, '5 Rue de Strasbourg', '57100', 'Thionville', 'librairie / jeux', 'indépendante probable', 'jeux, imaginaire, BD/livres spécialisés', '+33 3 82 90 65 27', 'https://www.antretemps.com/', 'site marchand confirmé', 'oui', 'Site avec moteur de recherche, panier, compte client et livraison.', 'listed', NULL, 'L''Antre Temps', 'https://www.antretemps.com/contact-m60294.html', '2026-10-07')
ON CONFLICT(directory_key) DO UPDATE SET
    name = excluded.name,
    group_name = excluded.group_name,
    address = excluded.address,
    postal_code = excluded.postal_code,
    city = excluded.city,
    category = excluded.category,
    independence_status = excluded.independence_status,
    specialization = excluded.specialization,
    phone = excluded.phone,
    website_url = excluded.website_url,
    website_status = excluded.website_status,
    ordering_status = excluded.ordering_status,
    ordering_notes = excluded.ordering_notes,
    directory_status = excluded.directory_status,
    curation_note = excluded.curation_note,
    source_label = excluded.source_label,
    source_url = excluded.source_url,
    last_checked = excluded.last_checked;

