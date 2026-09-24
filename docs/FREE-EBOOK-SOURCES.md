# Sources d’ebooks gratuits — politique de lancement

L’objectif n’est pas d’accumuler le plus grand nombre de fichiers gratuits. Le catalogue doit privilégier des **livres identifiables, éditorialisés et juridiquement publiables en France**.

## Niveaux de confiance

| Niveau | Usage |
| --- | --- |
| `institutional` | institution patrimoniale ou bibliothèque publique ; intégration automatisable si les droits du fichier sont clairs |
| `publisher_open_access` | éditeur ou plateforme éditoriale en accès ouvert ; vérifier la licence au niveau du livre |
| `curated_public_domain` | édition publique relue/curatée ; vérifier le droit français de l’œuvre, de la traduction et des illustrations |
| `validated_independent` | autoédition acceptée après validation humaine dans Librairie universelle |
| `external_unverified` | ne pas publier automatiquement |

## Vague 1 — à utiliser pour le premier lancement

### Gallica / BnF — `institutional`

**Statut : déjà intégré par OPDS.**

- priorité n°1 pour les classiques français ;
- l’importeur actuel ne cible que les œuvres locales déjà marquées `public_domain_status=yes` et de langue originale française ;
- titre et auteur doivent correspondre avant création d’une offre ;
- l’offre renvoie vers le fichier/source Gallica au lieu de prétendre à un réhébergement automatique.

Commande de lancement prudente :

```bash
php bin/import-gallica.php --all --limit=10
```

Augmenter seulement après observation des résultats et des faux positifs.

### Wikisource francophone — `curated_public_domain`

**Statut : déjà présent dans le MVP pour des œuvres ciblées.**

Pour une automatisation future, ne retenir que des éditions avec fac-similé et niveau de relecture suffisamment élevé. Ne pas aspirer les brouillons, OCR bruts, fragments ou textes sans provenance claire.

## Vague 2 — contemporains et académique

### OpenEdition Books — `publisher_open_access`

Candidat prioritaire pour apporter des livres contemporains réels : presses universitaires, institutions et maisons d’édition académiques.

Règle : la gratuité de lecture ne suffit pas. Conserver la licence et le lien du livre, et ne proposer un fichier que lorsque le téléchargement/licence du titre l’autorise explicitement.

### OAPEN / DOAB — `publisher_open_access`

Candidat prioritaire pour les monographies académiques Open Access.

- les métadonnées peuvent être moissonnées via les interfaces officielles ;
- la licence du **contenu** reste à contrôler livre par livre ;
- privilégier les titres disposant d’un texte intégral officiel et d’un éditeur identifié.

## Sources qualitatives avec filtre juridique France obligatoire

### Standard Ebooks — `curated_public_domain`

Très bonne qualité EPUB/typographique, mais le statut de domaine public annoncé par le projet est fondé sur le droit américain. Aucun import automatique en France sans contrôle de l’auteur, de la traduction, des illustrations et de l’édition.

### Ebooks libres et gratuits / Bibliothèque électronique du Québec — `curated_public_domain`

Catalogue intéressant de classiques francophones. Même règle : ne pas prendre le statut juridique canadien comme preuve suffisante pour la France.

## Pas d’aspiration automatique au lancement

### Project Gutenberg — `external_unverified`

Très grand catalogue, mais le statut juridique est principalement évalué selon le droit américain. À utiliser seulement titre par titre si une œuvre apporte une vraie valeur et après validation France.

## Règle pour l’autoédition

Un fichier envoyé par un utilisateur ne devient **jamais** une offre gratuite automatiquement.

Le chemin est :

```text
dépôt → contrôles techniques → validation humaine → corrections/refus/acceptation → publication
```

La sélection peut refuser les dépôts industriels ou automatisés, les variantes répétitives sans valeur, les contenus trompeurs ou illégaux, les ouvrages manifestement bâclés et ce qui ne présente pas de véritable travail éditorial.

L’objectif est un catalogue utile, pas un entrepôt de fichiers.
