# Notes de vérification du corpus MVP

Ce document consigne les choix bibliographiques et juridiques du petit corpus de démonstration. Le principe est de ne jamais confondre le statut d’une **œuvre** avec celui d’une traduction, d’une préface, d’illustrations ou d’une édition précise.

## Règle générale en France

L’article L123-1 du Code de la propriété intellectuelle prévoit que les droits patrimoniaux persistent pendant l’année civile du décès de l’auteur puis les soixante-dix années qui suivent.

La BnF indique en 2026 que, selon la règle générale, les auteurs morts avant 1956 font partie du domaine public, sous réserve des cas particuliers. Certains régimes spéciaux imposent donc une vérification distincte plutôt qu’un calcul automatique.

## Victor Hugo — Les Misérables

- Auteur : Victor Hugo (1802-1885)
- Première publication : 1862
- Œuvre originale en français : domaine public.
- Source de lecture vérifiée pour le MVP : Wikisource, édition Émile Testard, 1890.
- Le MVP renvoie vers la source et n’héberge pas encore sa propre copie du fichier.

## George Orwell

- Auteur : George Orwell / Eric Arthur Blair (1903-1950)
- Selon la règle générale française, les droits patrimoniaux sur les œuvres originales d’Orwell sont expirés depuis le 1er janvier 2021.
- Cela ne rend **pas automatiquement libres** les traductions françaises, préfaces, notes, illustrations ou éditions modernes.
- Le MVP affiche donc « domaine public — œuvre originale » et ne propose aucune traduction française gratuite sans vérification séparée.

### Œuvres Orwell du corpus initial

- Nineteen Eighty-Four / 1984 — 1949
- Animal Farm / La Ferme des animaux — 1945
- Homage to Catalonia / Hommage à la Catalogne — 1938
- The Road to Wigan Pier / Le Quai de Wigan — 1937
- Down and Out in Paris and London / Dans la dèche à Paris et à Londres — 1933
- Burmese Days / Une histoire birmane — 1934

## Pourquoi le corpus contient aussi des œuvres protégées

Le MVP ne doit pas seulement reconnaître les cas faciles de domaine public. Le corpus comprend donc aussi des œuvres encore protégées en France en 2026, notamment d’Albert Camus, Frank Herbert, Ursula K. Le Guin et J. R. R. Tolkien. Elles servent à vérifier que l’interface n’affiche jamais un accès gratuit par simple rapprochement de date ou de titre.

## Cas volontairement « à vérifier »

*Le Petit Prince* est conservé avec le statut `review` dans le prototype. Antoine de Saint-Exupéry bénéficie d’un régime français particulier lié notamment à la mention « Mort pour la France » ; le produit ne doit donc pas transformer une date de décès en verdict automatique.

Ce cas sert de test de conception : lorsqu’une règle juridique est incertaine ou particulière, le moteur doit afficher « À vérifier » plutôt que fabriquer une certitude.

## Taille actuelle du corpus

Le corpus de démonstration contient 21 œuvres. Il est volontairement petit mais diversifié : œuvres françaises et étrangères, domaine public, œuvres protégées, traductions à traiter séparément, formats numériques et cas juridique particulier.

L’objectif n’est pas encore l’exhaustivité. Il est de valider la structure `œuvre → édition → offre/source` avant de brancher des imports plus larges.

## Sources de référence

- Légifrance — Code de la propriété intellectuelle, articles L123-1 et L123-10.
- BnF — « Les auteurs du domaine public dans data.bnf.fr » (17 mars 2026).
- data.bnf.fr — George Orwell (1903-1950), ark:/12148/cb11918228x.
- data.bnf.fr — Victor Hugo (1802-1885), ark:/12148/cb11907966z.
- Catalogue général BnF — notices de Nineteen Eighty-Four, Animal Farm et autres œuvres du corpus.
- Wikisource — Les Misérables, édition Émile Testard, 1890.

## Règle produit

Un statut « domaine public » au niveau d’une œuvre ne doit jamais entraîner automatiquement un bouton « télécharger en français ». Un bouton de lecture/téléchargement dépend d’une **offre ou source précise**, dans une langue et une édition identifiées, dont les droits ont été vérifiés.