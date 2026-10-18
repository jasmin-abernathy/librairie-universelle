# CSP de l'aperçu papier

La page `print-preview.php` contient des manuscrits non publiés : elle ne doit pas exécuter de JavaScript distant ni ouvrir de connexion réseau.

La CSP globale Apache / LiteSpeed dans `public/.htaccess` peut supplanter celle envoyée par PHP avec `header()`. Le bloc local `<Files "print-preview.php">` applique donc la politique stricte et autorise seulement les styles inline nécessaires à la pagination Paged.js.

Le contrôle de production exige `connect-src 'none'` dans l'en-tête réellement servi. En cas d'échec, vérifier et corriger la configuration de l'hébergement avant de considérer l'aperçu comme sécurisé. La directive `unsafe-inline` ne doit pas être ajoutée à la CSP globale.
