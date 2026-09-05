# Librairie universelle

> **Nom de travail** — le nom public définitif reste à choisir et à vérifier.

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
- schéma séparant œuvres, éditions, contributeurs d’œuvre, contributeurs d’édition, sources et offres
- connecteurs de catalogue derrière une interface commune ; première implémentation : BnF SRU

SQLite permet de démarrer très petit. Le modèle est conçu pour qu’un passage ultérieur à PostgreSQL ou MariaDB reste possible lorsque la volumétrie ou la concurrence d’écriture le justifieront.

## Lancer en local

Prérequis : PHP 8.2+ avec PDO SQLite, `mbstring` et SimpleXML. `curl` est recommandé ; un fallback HTTP natif est prévu.

```bash
php -m | grep -Ei 'sqlite|mbstring|SimpleXML|curl'
php -S 127.0.0.1:8080 -t public
```

Puis ouvrir `http://127.0.0.1:8080`.

La base `data/app.sqlite` reçoit automatiquement le schéma idempotent de `database/schema.sql`, puis le corpus MVP de `database/seed.sql` est chargé sans dupliquer les données.

## Importer des éditions depuis la BnF

Le premier connecteur automatisé utilise le service **SRU du Catalogue général de la BnF**. Il cherche des notices par auteur + titre, normalise les ISBN, conserve l’ARK BnF comme identifiant de provenance et rattache chaque notice à l’œuvre locale sans créer d’offre commerciale fictive.

Importer une seule œuvre :

```bash
php bin/import-bnf.php --work=2
```

Importer tout le petit corpus :

```bash
php bin/import-bnf.php --all --limit=20
```

L’import est idempotent grâce à `source_records`. Une même notice BnF n’est pas réimportée à chaque cron. Les tests du parseur utilisent une fixture locale : la CI ne dépend pas du réseau BnF.

Sur o2switch, cette commande pourra être placée dans un cron à faible fréquence une fois le déploiement installé. Voir `docs/CATALOG-SOURCES.md`.

## État du MVP

Le socle contient déjà :

- une page d’accueil/recherche accessible et orientée MVP ;
- une page publique `/projet.php` qui explique la promesse et les limites du prototype ;
- une page publique `/sans-ia.php` qui documente l’absence d’IA, de profilage et de classement opaque ;
- une recherche SQL simple et déterministe ;
- des fiches `/work.php?id=…` qui distinguent œuvre, éditions, offres, droits et provenance ;
- un corpus de démonstration de **21 œuvres** couvrant des cas de domaine public, des œuvres encore protégées et un cas volontairement à vérifier ;
- six œuvres de George Orwell pour tester la différence entre domaine public du texte original et droits propres aux traductions ;
- plusieurs éditions françaises réelles de *1984* avec ISBN, traductrice et notice BnF ;
- deux volumes Folio réels de *Les Misérables* ;
- une source Wikisource vérifiée pour *Les Misérables* et des liens de prêt BnF à vérifier côté source pour *1984* et *Animal Farm* ;
- deux offres commerciales observées sur le même ebook de *1984*, qui démontrent que prix et DRM doivent être portés par l’offre vendeur ;
- un connecteur BnF SRU et un importeur idempotent ;
- une route de santé `/health.php` ;
- les principes d’architecture et de non-IA ;
- une feuille de route progressive ;
- un kit de mise en avant avec pitchs, FAQ, appels à testeurs et séquence de lancement ;
- une CI qui vérifie syntaxe PHP, extensions, parseur BnF hors réseau, schéma SQLite, corpus, recherche et pages principales.

Il **n’y a encore aucun paiement, aucune gestion de compte, aucun panier multi-libraires et aucun agrégateur exhaustif de stocks**. Les prix commerciaux affichés sont datés et doivent être revérifiés chez leur source.

## Documentation

- `docs/ARCHITECTURE.md` — architecture et modèle de données
- `docs/CATALOG-SOURCES.md` — stratégie de sources, import BnF et règles commerciales
- `docs/PRINCIPLES.md` — principes produit
- `docs/ROADMAP.md` — feuille de route fonctionnelle
- `docs/CORPUS-NOTES.md` — règles de vérification bibliographique et juridique du corpus
- `docs/MVP-PROMOTION-KIT.md` — positionnement, pitchs, FAQ, messages de test, critères et séquence de lancement
- Issue `#1` — checklist opérationnelle avant première mise en avant publique
