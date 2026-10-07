# Recherche fédérée

La recherche locale reste instantanée. Les catalogues externes sont interrogés progressivement, quatre sources au maximum en parallèle, avec un cache privé afin de limiter la charge sur les services tiers.

Sources intégrées :

- Catalogue général de la BnF (SRU) ;
- Gallica EPUB (OPDS/SRU) ;
- Wikisource francophone, limité aux textes « Bon pour export » ;
- Ebooks Libres et Gratuits (OPDS) ;
- Bibliothèque numérique romande (OPDS) ;
- Project Gutenberg (OPDS officiel) ;
- Standard Ebooks via les dépôts publics GitHub ;
- Open Library (Search API) ;
- DOAB (REST).

NosLivres.net sert de référence pour identifier l’écosystème francophone, mais n’est pas scrapé : ses principales sources structurées sont interrogées directement.

La présence d’un résultat externe ne vaut jamais validation juridique automatique. Les sources fondées sur le domaine public américain ou suisse sont signalées comme devant être vérifiées pour la France.
