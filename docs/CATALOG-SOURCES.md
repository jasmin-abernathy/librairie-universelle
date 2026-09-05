# Sources du catalogue et des offres

## Principe

Le projet sépare quatre choses qui ne doivent pas être confondues :

1. **l’œuvre** — par exemple *Nineteen Eighty-Four* ;
2. **l’édition** — traduction, éditeur, date, ISBN, support ;
3. **la source bibliographique** — d’où viennent les métadonnées permettant d’identifier l’édition ;
4. **l’offre** — un vendeur, une bibliothèque ou une source gratuite à une date donnée, avec son prix, son DRM et sa disponibilité propres.

Une notice bibliographique n’est donc jamais transformée automatiquement en « produit disponible ».

## BnF SRU — source bibliographique automatisée

Le connecteur `BnfSruSource` interroge le Catalogue général de la BnF via SRU 1.2.

Choix retenus pour le MVP :

- requête auteur + titre ;
- Dublin Core pour commencer simplement ;
- conservation de l’ARK BnF dans `source_records` ;
- normalisation ISBN-10 → ISBN-13 ;
- rattachement à une œuvre locale déjà identifiée ;
- aucune modification automatique du statut juridique de l’œuvre ;
- aucun prix ni stock créé depuis la notice BnF ;
- import idempotent ;
- timeout et retries limités uniquement sur les erreurs transitoires ;
- chaque exécution est journalisée dans `sync_runs`.

Commande :

```bash
php bin/import-bnf.php --work=2
php bin/import-bnf.php --all --limit=20
```

La CI vérifie le parseur avec `tests/fixtures/bnf-sru-1984.xml` sans appeler le service distant.

### Pourquoi ne pas importer aveuglément tout le Catalogue général ?

Le MVP doit d’abord valider le regroupement œuvre → éditions. Une ingestion massive créerait immédiatement des problèmes de rapprochement de titres, langues, traductions, coffrets et adaptations avant que l’interface ait prouvé son utilité.

Le cron de production fonctionne donc par lots et sur les œuvres déjà connues avant l’éventuelle construction d’un pipeline beaucoup plus large.

## Gallica OPDS — EPUB gratuits qualitatifs

`GallicaOpdsSource` utilise le catalogue OPDS d’ebooks Gallica. La BnF décrit cette API comme exposant des EPUB qu’elle a produits à partir d’ouvrages du domaine public.

Le filtre du MVP est volontairement prudent :

- uniquement les œuvres locales déjà marquées `public_domain_status=yes` ;
- uniquement les œuvres de langue originale française à ce stade ;
- requête par titre ;
- rapprochement déterministe titre + auteur avant ajout ;
- conservation de l’ARK dans `source_records` ;
- ajout d’une offre `free_download` pointant vers l’EPUB Gallica ;
- **pas de réhébergement** du fichier par défaut.

Commande :

```bash
php bin/import-gallica.php --work=1
php bin/import-gallica.php --all --limit=10
```

Le parseur est vérifié hors réseau avec `tests/fixtures/gallica-opds.xml`.

Cette approche permet d’étoffer le gratuit avec une source patrimoniale identifiable sans confondre « œuvre du domaine public » et « toutes les traductions/éditions sont libres ».

## Métadonnées BnF et documents numérisés

Les métadonnées BnF sont réutilisables sous Licence ouverte de l’État, avec citation de la source. Cela ne signifie pas que toutes les reproductions numériques de Gallica peuvent être réhébergées commercialement dans les mêmes conditions : métadonnées et fichiers numérisés ont des régimes de réutilisation distincts.

Le projet conserve donc explicitement la provenance et préfère, pour Gallica, utiliser le fichier depuis sa source tant que le droit de réhébergement n’a pas été vérifié pour l’usage envisagé.

## Couvertures

Le connecteur bibliographique peut préparer une URL de couverture lorsque l’ISBN est connu. Une couverture reste une donnée de présentation provenant d’une source externe et ne doit pas devenir une condition de fonctionnement de la fiche.

Le MVP doit rester lisible sans image.

## Offres commerciales

Une offre commerciale doit contenir au minimum :

- une source identifiable ;
- une URL réelle ;
- l’édition concernée lorsque possible ;
- le format ;
- le prix s’il a été effectivement observé ;
- la date de vérification ;
- le DRM au niveau de l’offre lorsque le vendeur le documente.

### Exemple utile : ebook français de `1984`

L’édition Gallimard Folio SF du 13 mai 2021 a l’EAN EPUB `9782072938245`.

Le MVP contient deux offres observées le 5 septembre 2026 :

- Lavoisier : 9,49 €, EPUB, Adobe DRM ;
- E-librairie Leclerc : 9,49 €, EPUB, CARE.

Cela démontre qu’un même EAN/ISBN ne suffit pas à déduire le DRM reçu par l’acheteur. Le DRM appartient donc à `offers`, pas à la définition universelle de `editions`.

Les prix et disponibilités sont marqués comme observés à une date précise et l’interface signale les données dépassant `OFFER_MAX_AGE_DAYS`.

## Interface de futurs partenaires commerciaux

Les intégrations commerciales passent désormais par un contrat de code séparé :

```text
OfferSource
  -> offersForIsbn(ISBN-13)
  -> OfferImporter
  -> offers + source_records
```

`OfferSource` impose à l’adaptateur de renvoyer des offres structurées avec identifiant externe, type, URL, prix, devise, disponibilité, format et DRM lorsque connus.

`OfferImporter` :

- refuse une édition sans ISBN-13 valide ;
- valide les URLs et devises ;
- n’accepte que les types d’offre prévus ;
- conserve l’identifiant externe ;
- met à jour une offre déjà connue au lieu de la dupliquer ;
- remplace `checked_at` lors d’une vraie nouvelle observation.

La CI utilise un faux partenaire pour vérifier que deux synchronisations successives donnent une insertion puis une mise à jour, sans doublon.

Cette abstraction est prête pour ePagine, Place des Libraires/leslibraires.fr ou un autre partenaire, mais **aucun de ces services n’est présenté comme techniquement intégré tant qu’un accès actuel et autorisé n’a pas été obtenu**.

## Libraires indépendants et panier

L’objectif reste de privilégier une intégration qui permet de choisir ou soutenir un libraire indépendant.

Des documentations historiques de leslibraires.fr décrivent notamment un panier adressable par EAN13 et identifiant affilié. Elles sont utiles pour préparer l’architecture mais ne suffisent pas à conclure qu’une intégration commerciale est aujourd’hui ouverte sans accord.

ePagine constitue également une piste cohérente pour le livre numérique, notamment parce que son parcours commercial est articulé avec le choix d’une librairie. Là encore, le branchement transactionnel doit être contractualisé proprement plutôt que simulé.

## Règle de fraîcheur

Une donnée bibliographique peut être relativement durable. Un prix ou un stock ne l’est pas.

Pour le MVP :

- une offre porte `checked_at` ;
- `OFFER_MAX_AGE_DAYS` vaut 7 jours par défaut ;
- au-delà, le storefront affiche « à revérifier » ;
- un stock futur devra avoir une fenêtre beaucoup plus courte ;
- un prêt bibliothèque reste « à vérifier sur la source » si aucun accès temps réel fiable n’est disponible.

Le site ne transforme jamais une ancienne observation en promesse actuelle.

## Ordre recommandé pour la suite

1. déployer et mesurer BnF + Gallica sur la Lune ;
2. obtenir les conditions d’un premier partenaire libraire/ebook ;
3. écrire uniquement son adaptateur `OfferSource` ;
4. vérifier les règles de bénéficiaire/librairie choisie ;
5. ajouter les stocks locaux uniquement lorsqu’une donnée fiable existe ;
6. construire un panier seulement après validation des contrats et du parcours offre.
