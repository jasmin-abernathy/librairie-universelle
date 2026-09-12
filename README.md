# Librairie universelle

> **Nom de travail** — le nom public définitif reste à choisir et à vérifier.

MVP web centré sur l’**œuvre** : chercher un livre une fois, comparer ses éditions et voir les différentes façons légales d’y accéder. La première alpha met désormais l’accent sur les **ebooks payants et gratuits de sources identifiées** ainsi que sur une **autoédition validée humainement**.

Le projet est développé ouvertement sous licence AGPL-3.0-only. Il a vocation à rester sobre, accessible et interopérable ; son statut alpha et ses limites sont documentés explicitement.

## Principes non négociables

- **Aucune IA** dans la recherche, le classement, les recommandations ou la validation éditoriale.
- Aucun profilage comportemental ni publicité ciblée.
- Les résultats doivent être explicables : œuvre, édition, source, format, DRM, disponibilité, prix ou date de vérification.
- Les recommandations futures seront éditoriales/humaines ou basées sur des règles déterministes visibles et désactivables.
- L’unité principale du catalogue est l’**œuvre**, pas l’ISBN.
- Une œuvre peut regrouper plusieurs éditions et plusieurs modes d’accès.
- Le statut « domaine public » ne doit jamais être déduit à l’aveugle : les cas incertains restent à vérifier.
- Le gratuit n’est pas rétrogradé parce qu’il ne génère pas de vente.
- L’autoédition n’est jamais publiée automatiquement : contrôles techniques déterministes, décision éditoriale humaine.
- Les fichiers numériques doivent rester exportables/téléchargeables lorsque les droits le permettent ; pas d’enfermement dans un lecteur propriétaire.

## Socle actuel

- PHP 8.2+ sans framework ;
- HTML rendu côté serveur ;
- CSS/JS natifs, sans dépendance front ;
- SQLite pour l’alpha ;
- schéma séparant œuvres, éditions, contributeurs, sources et offres ;
- source bibliographique BnF SRU ;
- source gratuite Gallica OPDS pour les EPUB du domaine public, avec filtrage conservateur ;
- interface `OfferSource` séparée pour les futurs partenaires commerciaux ;
- stockage privé des manuscrits hors webroot ;
- journal des synchronisations et back-office alpha protégé.

SQLite permet de démarrer petit. Le passage à MariaDB reste prévu lorsque la concurrence d’écriture ou la volumétrie réelle le justifieront.

## Lancer en local

Prérequis : PHP 8.2+ avec PDO SQLite, `mbstring`, SimpleXML et `fileinfo`. `curl` et `zip` sont recommandés.

```bash
php bin/preflight.php
php -S 127.0.0.1:8080 -t public
```

Puis ouvrir `http://127.0.0.1:8080`.

La base `data/app.sqlite` reçoit automatiquement le schéma idempotent de `database/schema.sql`, puis le corpus MVP de `database/seed.sql` est chargé sans dupliquer les données.

## Pages principales

- `/` — recherche par œuvre ;
- `/ebooks.php` — storefront ebook payant + gratuit ;
- `/work.php?id=…` — fiche œuvre / éditions / offres ;
- `/autoedition.php` — dépôt auteur, ressources ISBN et impression ;
- `/feedback.php` — canal de retour alpha ;
- `/projet.php` — périmètre et limites ;
- `/sans-ia.php` — règles sans IA ;
- `/admin/` — suivi des imports, retours et validation humaine des soumissions.

Les écritures publiques restent **fermées par défaut** :

```text
SELF_PUBLISHING_ENABLED=false
FEEDBACK_ENABLED=false
```

Définir aussi un `ADMIN_TOKEN` long avant toute ouverture de l’alpha. Voir `.env.example` et `docs/O2SWITCH-DEPLOY.md`.

## Sources bibliographiques et domaine public

### BnF SRU

```bash
php bin/import-bnf.php --work=2
php bin/import-bnf.php --all --limit=20
```

L’import conserve les ARK BnF et les ISBN comme provenance et ne crée jamais de prix ou de stock à partir d’une simple notice bibliographique.

### Gallica OPDS

```bash
php bin/import-gallica.php --work=1
php bin/import-gallica.php --all --limit=10
```

Au stade actuel, l’importeur Gallica est volontairement conservateur : il ne cherche que les œuvres locales déjà marquées `public_domain_status=yes` et de langue originale française, puis vérifie titre + auteur avant d’ajouter un lien EPUB. Le fichier reste servi par Gallica ; le MVP ne le réhéberge pas.

Les parseurs BnF et Gallica sont testés sur fixtures locales afin que la CI ne dépende pas de leur disponibilité réseau.

## Storefront ebook

`/ebooks.php` réunit les offres gratuites et payantes dans le même rayon. Les offres commerciales affichent leur source, prix, format, DRM et fraîcheur. Une donnée dépassant `OFFER_MAX_AGE_DAYS` est signalée comme à revérifier.

Le code sait désormais importer des offres commerciales par ISBN via l’interface `OfferSource` et `OfferImporter`. Aucun partenaire n’est prétendu intégré tant qu’un vrai accès contractuel/API n’existe pas.

## Autoédition

Le parcours `/autoedition.php` permet de préparer :

- EPUB ;
- couverture ;
- PDF prêt à imprimer ;
- ISBN ebook et papier ;
- prix envisagés ;
- mode gratuit ou payant ;
- déclarations de droits et de qualité.

Le formulaire contient le lien vers la demande d’ISBN AFNIL pour particuliers autoédités et des ressources d’impression/POD (Bookelis, CoolLibri, BoD) présentées sans partenariat ni classement sponsorisé.

Les fichiers sont stockés hors `public/`. Le back-office permet : validation, demande de corrections, refus, puis publication. Un EPUB indépendant gratuit peut devenir téléchargeable après publication ; un EPUB indépendant payant reste seulement catalogué tant que le paiement n’est pas réellement intégré.

## État du MVP

Déjà codé et couvert par la CI :

- recherche déterministe et fiches œuvre ;
- corpus de 21 œuvres, cas domaine public/protégé/incertain ;
- six Orwell et plusieurs éditions françaises réelles de *1984* ;
- deux offres commerciales réelles du même EPUB avec DRM vendeur différents ;
- storefront ebook gratuit + payant ;
- BnF SRU + Gallica OPDS ;
- autoédition avec contrôle des uploads, CSRF, validation humaine et back-office ;
- feedback alpha ;
- fraîcheur des offres ;
- retries limités et journalisation des imports ;
- interface de futurs connecteurs commerciaux ;
- préflight et configuration Apache/o2switch ;
- responsive, focus clavier et réduction des animations ;
- tests du corpus, des parseurs, du storefront, de l’autoédition et des routes principales.

**Pas encore réalisé en conditions réelles :** déploiement sur la Lune o2switch, import réseau BnF/Gallica depuis cette Lune, test avec vrais fichiers envoyés via le formulaire, audit sur vrais appareils/lecteur d’écran et alpha auprès d’utilisateurs.

Il **n’y a encore aucun paiement intégré, aucun panier multi-libraires, aucun compte lecteur et aucun flux exhaustif de stock libraire**. Les vraies intégrations commerciales dépendront des conditions et accès fournis par les partenaires.

## Documentation

- `docs/ARCHITECTURE.md` — architecture et modèle de données
- `docs/CATALOG-SOURCES.md` — stratégie des sources bibliographiques et commerciales
- `docs/O2SWITCH-DEPLOY.md` — déploiement alpha sur une Lune
- `docs/SELF-PUBLISHING.md` — règles et workflow d’autoédition
- `docs/PRINCIPLES.md` — principes produit
- `docs/ROADMAP.md` — feuille de route
- `docs/CORPUS-NOTES.md` — vérification bibliographique et juridique du corpus
- `docs/MVP-PROMOTION-KIT.md` — positionnement et lancement
- Issue `#1` — checklist opérationnelle avant première mise en avant publique

## Licence et sécurité

Le code est distribué sous **AGPL-3.0-only**. Voir `LICENSE`.

Les signalements de sécurité doivent suivre `SECURITY.md` et ne pas être publiés d’abord dans une issue publique.
