# Taille des manuscrits et fichiers de publication

Le serveur o2switch était initialement configuré en CLI sur `upload_max_filesize=2M` et `post_max_size=8M` : les livres DOCX/ODT de plus de 2 Mo échouaient alors que l'interface annonçait 30 Mo.

`public/.user.ini` demande maintenant un plafond HTTP de 112 Mo par fichier et 192 Mo par requête, afin de permettre la composition de manuscrits jusqu'à 30 Mio et les PDF finaux actuellement configurés jusqu'à 100 Mio. Les plafonds web **effectifs** peuvent dépendre du PHP-FPM et des restrictions de l'hébergeur ; ils doivent être vérifiés dans une requête HTTP réelle, après le délai de cache `user_ini.cache_ttl` (souvent 300 secondes).

`UploadLimits.php` lit les plafonds PHP web réellement actifs, les combine avec le plafond métier et l'interface affiche cette valeur au lieu de promettre 30 Mo systématiquement. Si le corps HTTP excède `post_max_size`, la page explique l'erreur de taille au lieu d'indiquer une fausse expiration de session.

Les extensions ZIP et DOM deviennent des prérequis explicites du générateur DOCX/ODT. Leur disponibilité côté o2switch a été vérifiée le 18 octobre 2026. Le préflight en **ligne de commande** peut afficher une configuration différente de PHP web et ne démontre donc pas seul l'effectivité de `.user.ini`.

Tout upload non authentifié reste à contrôler en matière de charge CPU et quotas lors de l'ouverture de l'alpha ; ne pas augmenter les limites de PHP au-delà des besoins et de la politique d'hébergement.
