# Architecture du MVP

## Objectif

Le cœur du projet n’est pas un catalogue de produits mais un **graphe explicite autour de l’œuvre** : une œuvre peut avoir plusieurs éditions ; une édition peut être décrite par plusieurs sources et faire l’objet de plusieurs offres ; une offre peut être gratuite, commerciale ou liée à un prêt.

L’architecture doit permettre d’ajouter des sources une par une sans transformer leurs données en vérité globale ni introduire de classement opaque.

## Modèle

### `works`

L’œuvre intellectuelle : titre, titre original, année de première publication, langue originale et statut de domaine public du texte original.

Le statut juridique est conservé comme donnée explicite (`yes`, `no`, `review`, `unknown`) et n’est pas recalculé automatiquement à partir d’une date.

### `contributors` + `work_contributors`

Personnes rattachées à l’œuvre, principalement l’auteur ou l’autrice.

### `editions`

Manifestations précises de l’œuvre : ISBN/EAN, éditeur, date, langue, support et format.

Une traduction française de George Orwell est donc une édition distincte du texte original anglais.

### `edition_contributors`

Personnes propres à l’édition : traducteur ou traductrice, éditeur scientifique, illustrateur, etc.

Cette séparation est essentielle pour le droit d’auteur : le fait que l’œuvre originale soit dans le domaine public n’implique pas que le travail d’une traductrice récente le soit.

### `sources`

Origine identifiable d’une donnée ou d’une offre : BnF, Wikisource, bibliothèque, libraire, distributeur, etc.

### `source_records`

Lien entre l’identifiant d’une source et l’objet local. Pour la BnF, l’ARK de notice permet d’éviter de réimporter plusieurs fois la même édition et de retrouver sa provenance.

### `offers`

Façon réelle d’accéder à une œuvre ou une édition : lecture gratuite, téléchargement, prêt, achat neuf, occasion, ebook, audio.

Les données volatiles sont placées ici :

- prix ;
- disponibilité ;
- URL du vendeur ;
- date de vérification ;
- DRM annoncé par le vendeur.

Le cas de l’ebook français `1984` montre pourquoi : le même EAN peut être distribué avec des protections différentes selon la plateforme.

## Sources de catalogue

Les connecteurs bibliographiques implémentent `CatalogSource`.

Première implémentation : `BnfSruSource`.

```text
CatalogSource
    └── BnfSruSource
            └── HttpClient

CatalogImporter
    ├── œuvre locale
    ├── CatalogSource
    ├── editions
    ├── edition_contributors
    └── source_records
```

`CatalogImporter` ne crée pas d’offre commerciale : une notice BnF prouve l’existence d’une édition, pas son prix ou son stock aujourd’hui.

## Import BnF

Le script `bin/import-bnf.php` permet l’import ciblé :

```bash
php bin/import-bnf.php --work=2
php bin/import-bnf.php --all --limit=20
```

Le flux est :

1. charger l’œuvre locale et son auteur principal ;
2. interroger SRU sur le titre local puis, si différent, le titre original ;
3. parser la réponse Dublin Core ;
4. extraire ARK, ISBN, titre, éditeur, date, langue et contributeurs ;
5. réutiliser une édition déjà connue si son ISBN correspond à la même œuvre ;
6. sinon créer l’édition ;
7. conserver l’ARK dans `source_records`.

Un conflit ISBN entre deux œuvres n’est pas résolu silencieusement : il est comptabilisé et ignoré pour contrôle humain.

## Recherche

La recherche du MVP reste SQL et déterministe : titre, titre original, sous-titre et auteur.

Aucun historique utilisateur n’intervient dans le classement.

## Interface

`public/index.php` : recherche et résultats par œuvre.

`public/work.php` : fiche d’œuvre avec :

- statut juridique ;
- éditions ;
- ISBN ;
- contributeurs d’édition ;
- provenance BnF ;
- offres ;
- prix/DRM/date au niveau vendeur.

## Stockage

SQLite reste adapté au prototype et au petit nombre d’écritures simultanées. Le schéma SQL est rejoué de façon idempotente à chaque connexion afin d’appliquer les nouvelles tables `CREATE IF NOT EXISTS` à une base locale déjà existante.

Pour la mise en production sur o2switch, une migration vers MariaDB pourra être faite avant que les imports automatiques, comptes utilisateurs ou opérations commerciales créent une concurrence d’écriture significative. Le modèle logique doit rester indépendant du moteur.

## Tests

La CI vérifie :

- syntaxe PHP ;
- extensions PHP requises ;
- parsing BnF SRU avec une fixture XML locale ;
- création/migration du schéma SQLite ;
- présence des œuvres et éditions de référence ;
- distinction des DRM au niveau vendeur ;
- rendu HTTP des pages principales.

Le test du connecteur BnF ne dépend volontairement pas du réseau distant : une indisponibilité du catalogue ne doit pas rendre impossible la validation du code.

## Limites assumées du MVP

Pas encore de :

- résolution automatique de doublons bibliographiques complexes ;
- panier multi-libraires ;
- paiement ;
- comptes utilisateurs ;
- synchronisation exhaustive de stock ;
- rapprochement probabiliste ou IA ;
- import massif de la totalité de la BnF.

Ces limites sont intentionnelles : chaque source doit être ajoutée avec ses conditions, son niveau de fraîcheur et une façon visible d’expliquer les données affichées.
