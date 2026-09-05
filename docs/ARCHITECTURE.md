# Architecture initiale

## Objectif

Rester extrêmement simple tant que le produit n’a pas validé ses sources de données et ses parcours principaux.

```text
Navigateur
   ↓
PHP / HTML rendu serveur
   ↓
Recherche déterministe
   ↓
SQLite (MVP)
   ↓
œuvres ─ éditions ─ offres
   │        │         │
   └ contributeurs    └ sources externes
```

## Pourquoi PHP sans framework au départ

- déploiement facile sur un VPS ou hébergement PHP ;
- surface de dépendances minimale ;
- rendu serveur adapté au référencement et à l’accessibilité ;
- consommation mémoire faible pour un MVP ;
- pas de build front obligatoire.

Un framework pourra être introduit plus tard si les besoins réels le justifient. Il ne doit pas être choisi avant d’avoir identifié une contrainte que le socle actuel ne sait plus gérer proprement.

## Pourquoi SQLite au départ

Le prototype a surtout des lectures et peu d’écritures concurrentes. SQLite évite d’administrer un service supplémentaire.

Passage recommandé à PostgreSQL lorsque l’un de ces besoins devient réel :

- imports parallèles fréquents ;
- grand nombre d’écritures simultanées ;
- recherche plein texte plus poussée ;
- réplication/haute disponibilité ;
- séparation de plusieurs services.

## Adaptateurs de données futurs

Chaque intégration externe devra être isolée derrière un adaptateur et documenter :

1. origine des données ;
2. licence/conditions d’utilisation ;
3. identifiant externe stable ;
4. fréquence de mise à jour autorisée ;
5. données réellement nécessaires ;
6. stratégie de suppression/correction.

Aucune API externe n’est encore câblée dans le dépôt.

## Recherche

La version initiale fait uniquement du `LIKE` sur titre/sous-titre/titre original/contributeur, avec un tri explicite : égalité de titre d’abord, puis ordre alphabétique.

Ce choix est volontaire : pas d’IA, pas de score opaque. Une future recherche plein texte restera déterministe et documentée.

## Numérique

Le modèle `editions` possède déjà `medium`, `file_format`, `drm_type` et `accessibility_note` pour pouvoir afficher clairement les contraintes d’un ebook ou livre audio.

Le modèle `offers` distingue notamment `ebook`, `audio`, `free_download`, `borrow` et `read_online`.

## Déploiement

Pour un premier serveur :

- Nginx ou Apache ;
- PHP-FPM 8.2+ ;
- racine web = dossier `public/` ;
- `data/` hors exposition HTTP ;
- HTTPS ;
- sauvegarde quotidienne de la base ;
- supervision de `/health.php`.

Les fichiers EPUB/PDF lourds ne devraient pas être stockés sur le disque système du VPS si le catalogue devient important. Prévoir du stockage objet séparé si le projet héberge réellement des fichiers autorisés.
