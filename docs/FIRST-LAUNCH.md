# Premier lancement — Librairie universelle

Ce guide vise une **alpha réellement utilisable** avant tout accord avec des libraires.

## 1. Garder les écritures fermées pendant l’installation

```text
APP_ENV=production
SELF_PUBLISHING_ENABLED=false
FEEDBACK_ENABLED=false
ADMIN_TOKEN=<secret long>
DATABASE_PATH=/chemin/prive/librairie-universelle/app.sqlite
STORAGE_PATH=/chemin/prive/librairie-universelle/storage
```

Le document root doit pointer uniquement vers `public/`.

## 2. Préflight et initialisation

```bash
php bin/preflight.php
php -r 'require "src/bootstrap.php"; echo "Base initialisée".PHP_EOL;'
```

Puis vérifier :

- `/health.php` ;
- `/` ;
- `/ebooks.php` ;
- `/work.php?id=2` ;
- `/autoedition.php` ;
- `/admin/` avec le token.

## 3. Constituer l’offre gratuite de départ

Commencer petit et vérifiable :

```bash
php bin/import-gallica.php --all --limit=10
```

Contrôler les offres importées dans l’admin avant d’augmenter la volumétrie.

Le MVP contient déjà Wikisource sur des œuvres ciblées. Voir `docs/FREE-EBOOK-SOURCES.md` pour la suite : OpenEdition puis OAPEN/DOAB, avec Standard Ebooks/ELG derrière un filtre juridique France.

## 4. Brancher Atelier EPUB

Déployer Atelier EPUB en mode local-first, puis définir ici :

```text
ATELIER_EPUB_URL=https://VOTRE-HOTE-ATELIER/
```

Dans le build Atelier, définir réciproquement :

```text
VITE_LIBRAIRIE_PUBLISH_URL=https://VOTRE-HOTE-LIBRAIRIE/autoedition.php?source=atelier-epub
```

Aucun manuscrit ne passe automatiquement d’un service à l’autre.

## 5. Tester un vrai dépôt avant ouverture

Avec un EPUB non sensible :

1. activer temporairement `SELF_PUBLISHING_ENABLED=true` ;
2. relancer `php bin/preflight.php` ;
3. déposer EPUB + couverture ;
4. vérifier le stockage hors webroot ;
5. vérifier l’admin ;
6. demander une correction puis valider ;
7. publier uniquement en mode gratuit pour ce test ;
8. vérifier le téléchargement public ;
9. sauvegarder SQLite + stockage.

## 6. Ouvrir l’alpha

Quand le parcours précédent fonctionne :

```text
SELF_PUBLISHING_ENABLED=true
FEEDBACK_ENABLED=true
```

Les ebooks indépendants payants restent **cataloguables mais non vendus** tant que paiement, livraison sécurisée et reversement auteur ne sont pas effectivement intégrés.

## 7. Mise à jour

Utiliser `scripts/server-update.sh` : il refuse un arbre Git modifié, teste le nouveau `origin/main` dans un worktree temporaire, puis fait uniquement un fast-forward.
