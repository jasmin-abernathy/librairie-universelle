# Déploiement alpha sur une Lune o2switch

## Principe

Le dépôt peut être déployé sur une Lune dédiée sans Docker ni worker permanent.

Architecture cible :

```text
GitHub
  -> dépôt serveur hors webroot
  -> domaine dont le document root pointe vers /public
  -> PHP 8.2+
  -> SQLite pour l'alpha
  -> stockage privé hors /public
  -> cron BnF + Gallica à faible fréquence
```

## Variables à préparer

Copier `.env.example` vers une configuration réellement chargée par l'environnement ou définir les variables dans le contexte PHP/cron :

- `APP_ENV=production`
- `APP_NAME=...`
- `DATABASE_PATH=/chemin/hors/public/app.sqlite`
- `STORAGE_PATH=/chemin/hors/public/storage`
- `ADMIN_TOKEN=<secret long et aléatoire>`
- `SELF_PUBLISHING_ENABLED=false` au premier déploiement
- `FEEDBACK_ENABLED=false` au premier déploiement
- `OFFER_MAX_AGE_DAYS=7`

Ne jamais committer `ADMIN_TOKEN` ni les fichiers soumis.

## Document root

Le domaine ou sous-domaine doit pointer directement vers le dossier `public/` du projet. Les dossiers `src/`, `database/`, `storage/`, `data/` et `docs/` ne doivent pas être exposés par HTTP.

`public/.htaccess` ajoute des headers de sécurité sans imposer de directive PHP susceptible de provoquer une erreur 500 sur un hébergement mutualisé.

## Préflight

Depuis le dossier du dépôt :

```bash
php bin/preflight.php
```

Extensions obligatoires :

- `pdo_sqlite`
- `mbstring`
- `SimpleXML`
- `fileinfo`

Recommandées :

- `curl`
- `zip` / `ZipArchive` pour valider plus profondément les EPUB et utiliser la composition papier
- `dom` pour extraire et nettoyer les chapitres lors de la prévisualisation papier

Le script vérifie aussi les droits d'écriture et refuse de considérer l'environnement prêt si les fonctions alpha sont ouvertes sans `ADMIN_TOKEN`.

## Premier lancement

Ouvrir `/health.php` après le préflight. La base SQLite reçoit automatiquement le schéma et le petit corpus idempotent.

Tester ensuite :

- `/`
- `/ebooks.php`
- `/work.php?id=2`
- `/autoedition.php`
- `/feedback.php`
- `/admin/` avec l'authentification Basic et `ADMIN_TOKEN` comme mot de passe

## Crons de catalogue

Commencer à faible fréquence et décaler les tâches pour ne pas les lancer ensemble.

### BnF SRU — notices bibliographiques

Exemple quotidien :

```cron
17 4 * * * cd /CHEMIN/DU/DEPOT && /usr/local/bin/php bin/import-bnf.php --all --limit=20 >> logs/bnf-cron.log 2>&1
```

### Gallica OPDS — EPUB gratuits du domaine public

Exemple hebdomadaire :

```cron
43 4 * * 2 cd /CHEMIN/DU/DEPOT && /usr/local/bin/php bin/import-gallica.php --all --limit=10 >> logs/gallica-cron.log 2>&1
```

Adapter le chemin PHP à celui fourni par la Lune. Les deux scripts journalisent leur résultat dans `sync_runs`, visible depuis `/admin/`.

Ne pas augmenter la fréquence tant que la volumétrie, les limites des sources et la durée réelle des imports ne sont pas mesurées. Gallica reste volontairement limité aux œuvres françaises déjà validées comme domaine public dans notre base.

## Ouverture de l'alpha

Une fois les tests terminés :

```text
SELF_PUBLISHING_ENABLED=true
FEEDBACK_ENABLED=true
```

Puis relancer `php bin/preflight.php` et vérifier que l'administration est protégée.

Faire ensuite un vrai dépôt de test avec un EPUB non sensible avant d'accepter le moindre manuscrit extérieur.

## Composition papier depuis l’autoédition

La page `/autoedition.php` conserve Atelier EPUB comme outil dédié à l’EPUB. La version papier est préparée directement dans la Librairie : l’auteur sélectionne son EPUB, choisit format/reliure/sommaire/pagination, puis ouvre `/print-preview.php` via le bouton « Prévisualiser la version imprimée ».

Le serveur lit l’EPUB sans l’extraire dans le webroot, nettoie le XHTML et embarque uniquement les images locales acceptées. L’aperçu paginé utilise Paged.js 0.4.3 chargé depuis unpkg ; le manuscrit n’est pas envoyé à ce CDN. Le navigateur ne récupère que le script statique de pagination, avec une politique de référent sans URL source. Les scripts applicatifs et la feuille de mise en page dynamique restent servis par `librairie.lepotager.org`. Le bouton final utilise la boîte d’impression du navigateur pour imprimer ou enregistrer le résultat en PDF.

Avant ouverture publique, tester ce parcours avec un EPUB non sensible et vérifier recto/verso, pages blanches de début de chapitre, sommaire et export PDF dans le navigateur réellement utilisé.

## Sauvegardes

Sauvegarder au minimum :

- la base SQLite ;
- le répertoire privé de soumissions ;
- les logs utiles à l'investigation.

Les manuscrits reçus ne doivent jamais être copiés dans Git.

## Passage ultérieur à MariaDB

SQLite reste adapté à l'alpha. La migration vers MariaDB devient pertinente lorsque les écritures concurrentes, les imports parallèles ou le volume réel le justifient. Ne pas migrer uniquement « parce que c'est de la production ».
