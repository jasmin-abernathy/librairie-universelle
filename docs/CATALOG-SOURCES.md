# Sources du catalogue et des offres

## Principe

Le projet sépare quatre choses qui ne doivent pas être confondues :

1. **l’œuvre** — par exemple *Nineteen Eighty-Four* ;
2. **l’édition** — traduction, éditeur, date, ISBN, support ;
3. **la source bibliographique** — d’où viennent les métadonnées permettant d’identifier l’édition ;
4. **l’offre** — un vendeur, une bibliothèque ou une source gratuite à une date donnée, avec son prix, son DRM et sa disponibilité propres.

Une notice bibliographique n’est donc jamais transformée automatiquement en « produit disponible ».

## BnF SRU — première source automatisée

Le connecteur `BnfSruSource` interroge le Catalogue général de la BnF via SRU 1.2.

Choix retenus pour le MVP :

- requête auteur + titre ;
- Dublin Core pour commencer simplement ;
- conservation de l’ARK BnF dans `source_records` ;
- normalisation ISBN-10 → ISBN-13 ;
- rattachement à une œuvre locale déjà identifiée ;
- aucune modification automatique du statut juridique de l’œuvre ;
- aucun prix ni stock créé depuis la notice BnF ;
- import idempotent.

Commande :

```bash
php bin/import-bnf.php --work=2
php bin/import-bnf.php --all --limit=20
```

La CI vérifie le parseur avec `tests/fixtures/bnf-sru-1984.xml` sans appeler le service distant.

### Pourquoi ne pas importer aveuglément tout le Catalogue général ?

Le MVP doit d’abord valider le regroupement œuvre → éditions. Une ingestion massive créerait immédiatement des problèmes de rapprochement de titres, langues, traductions, coffrets et adaptations avant que l’interface ait prouvé son utilité.

Le cron de production devra donc fonctionner par lots et seulement sur les œuvres déjà connues, avant l’éventuelle construction d’un pipeline beaucoup plus large.

## Métadonnées BnF et documents numérisés

Les métadonnées BnF sont réutilisables sous Licence ouverte de l’État, avec citation de la source. Cela ne signifie pas que toutes les reproductions numériques de Gallica peuvent être réhébergées commercialement dans les mêmes conditions : les métadonnées et les fichiers numérisés ont des régimes de réutilisation distincts.

Le projet conserve donc explicitement la provenance et, pour le domaine public, préfère dans le MVP renvoyer vers la source tant que le droit de réhébergement du fichier n’a pas été vérifié.

## Couvertures

Le connecteur prépare une URL de couverture BnF lorsque l’ISBN est connu. La couverture reste une donnée de présentation provenant d’une source externe ; elle ne doit pas devenir une condition de fonctionnement de la fiche.

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

C’est volontaire : cela démontre qu’un même EAN/ISBN ne suffit pas à déduire le DRM reçu par l’acheteur. Le DRM appartient donc à `offers`, pas à la définition universelle de `editions`.

Les prix et disponibilités sont marqués comme observés à une date précise et l’interface demande de les revérifier chez le vendeur.

## Libraires indépendants et panier

L’objectif reste de privilégier une intégration qui permet de choisir ou soutenir un libraire indépendant.

Des documentations historiques de leslibraires.fr décrivent notamment un panier adressable par EAN13 et identifiant affilié. Elles sont utiles pour préparer l’architecture mais ne suffisent pas à conclure qu’une intégration commerciale est aujourd’hui ouverte sans accord. Aucune API de paiement ou d’affiliation ne doit donc être activée sans conditions actuelles vérifiées et, si nécessaire, contrat/identifiant partenaire.

ePagine constitue également une piste cohérente pour le livre numérique, notamment parce que son parcours commercial est déjà articulé avec le choix d’une librairie. Là encore, le branchement transactionnel doit être contractualisé proprement plutôt que simulé.

## Prochaine évolution des sources

Ordre recommandé :

1. stabiliser BnF SRU sur le corpus ;
2. augmenter progressivement le nombre d’éditions par œuvre ;
3. ajouter un connecteur d’offres commerciales dédié, séparé du connecteur bibliographique ;
4. privilégier une source/partenaire permettant le soutien à une librairie indépendante ;
5. ajouter les stocks locaux uniquement lorsqu’une donnée fiable et suffisamment fraîche existe ;
6. seulement ensuite construire un panier multi-sources.

## Règle de fraîcheur

Une donnée bibliographique peut être relativement durable. Un prix ou un stock ne l’est pas.

À terme, les offres devront donc avoir une politique d’expiration explicite :

- prix : revérification régulière ou affichage de la date ;
- stock : fenêtre de fraîcheur beaucoup plus courte ;
- prêt bibliothèque : statut « à vérifier sur la source » si aucun accès temps réel fiable n’est disponible.

Le site ne doit jamais transformer une ancienne observation en promesse actuelle.
