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
- `zip` / `ZipArchive` pour valider plus profondément les EPUB

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

## Sauvegardes

Sauvegarder au minimum :

- la base SQLite ;
- le répertoire privé de soumissions ;
- les logs utiles à l'investigation.

Les manuscrits reçus ne doivent jamais être copiés dans Git.

## Passage ultérieur à MariaDB

SQLite reste adapté à l'alpha. La migration vers MariaDB devient pertinente lorsque les écritures concurrentes, les imports parallèles ou le volume réel le justifient. Ne pas migrer uniquement « parce que c'est de la production ».
