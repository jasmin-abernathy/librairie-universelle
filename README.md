# Librairie universelle

MVP web pour chercher une **œuvre** une seule fois et voir, à terme, les différentes façons légales d’y accéder : livre neuf, occasion, librairie locale, ebook, audio, bibliothèque et téléchargement gratuit lorsqu’une version est réellement dans le domaine public.

Le dépôt est volontairement privé pendant la phase de conception. Le projet a vocation à rester sobre, accessible, interopérable et open source.

## Principes non négociables

- **Aucune IA** dans la recherche, le classement ou les recommandations.
- Aucun profilage comportemental ni publicité ciblée.
- Les résultats doivent être explicables : titre, auteur, édition, format, disponibilité, prix, distance ou source.
- Les recommandations futures seront éditoriales/humaines ou basées sur des règles déterministes visibles et désactivables.
- L’unité principale du catalogue est l’**œuvre**, pas l’ISBN.
- Une œuvre peut regrouper plusieurs éditions et plusieurs modes d’accès.
- Le statut « domaine public » ne doit jamais être déduit à l’aveugle : les cas incertains restent à vérifier.
- Les fichiers numériques doivent rester exportables/téléchargeables lorsque les droits le permettent ; pas d’enfermement dans un lecteur propriétaire.

## Socle actuel

- PHP 8.2+ sans framework
- HTML rendu côté serveur
- CSS/JS natifs, sans dépendance front
- SQLite pour le prototype local
- schéma prévu pour séparer œuvres, éditions, contributeurs, sources et offres

SQLite permet de démarrer très petit. Le modèle est conçu pour qu’un passage ultérieur à PostgreSQL soit simple lorsque la volumétrie ou la concurrence d’écriture le justifieront.

## Lancer en local

Prérequis : PHP 8.2+ avec PDO SQLite et `mbstring`.

```bash
php -m | grep -Ei 'sqlite|mbstring'
php -S 127.0.0.1:8080 -t public
```

Puis ouvrir `http://127.0.0.1:8080`.

La base `data/app.sqlite` est créée automatiquement au premier lancement à partir de `database/schema.sql`.

## État du MVP

Le socle contient déjà :

- une page d’accueil/recherche accessible ;
- une recherche SQL simple et déterministe ;
- le modèle de données initial ;
- une route de santé `/health.php` ;
- les principes d’architecture et de non-IA ;
- une feuille de route progressive ;
- un contrôle CI minimal de la syntaxe PHP, des extensions requises, du schéma SQLite et de la route de santé.

Il **n’y a encore aucun import de catalogue externe, aucun paiement, aucune gestion de compte et aucun agrégateur de stock**. Ces briques seront ajoutées une par une après vérification des API, licences et contraintes juridiques.

Voir `docs/ARCHITECTURE.md`, `docs/PRINCIPLES.md` et `docs/ROADMAP.md`.
