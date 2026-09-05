# Notes de vérification du corpus MVP

Ce document consigne les choix bibliographiques et juridiques du petit corpus de démonstration. Le principe est de ne jamais confondre le statut d’une **œuvre** avec celui d’une traduction, d’une préface, d’illustrations ou d’une édition précise.

## Règle générale en France

L’article L123-1 du Code de la propriété intellectuelle prévoit que les droits patrimoniaux persistent pendant l’année civile du décès de l’auteur puis les soixante-dix années qui suivent.

La BnF indique en 2026 que, selon la règle générale, les auteurs morts avant 1956 font partie du domaine public, sous réserve des cas particuliers.

## Victor Hugo — Les Misérables

- Auteur : Victor Hugo (1802-1885)
- Première publication : 1862
- Œuvre originale en français : domaine public.
- Source de lecture vérifiée pour le MVP : Wikisource, édition Émile Testard, 1890.
- Le MVP renvoie vers la source et n’héberge pas encore sa propre copie du fichier.
- Deux éditions papier Folio Classique 1999 sont distinguées par ISBN : tome I `9782070409228`, tome II `9782070409235`.
- La notice BnF correspondant à cette édition est conservée comme provenance bibliographique.

## George Orwell

- Auteur : George Orwell / Eric Arthur Blair (1903-1950)
- Selon la règle générale française, les droits patrimoniaux sur les œuvres originales d’Orwell sont expirés depuis le 1er janvier 2021.
- Cela ne rend **pas automatiquement libres** les traductions françaises, préfaces, notes, illustrations ou éditions modernes.
- Le MVP affiche donc « domaine public — texte original » et ne propose aucune traduction française gratuite sans vérification séparée.

### Œuvres Orwell du corpus initial

- Nineteen Eighty-Four / 1984 — 1949
- Animal Farm / La Ferme des animaux — 1945
- Homage to Catalonia / Hommage à la Catalogne — 1938
- The Road to Wigan Pier / Le Quai de Wigan — 1937
- Down and Out in Paris and London / Dans la dèche à Paris et à Londres — 1933
- Burmese Days / Une histoire birmane — 1934

### Éditions françaises de 1984 utilisées pour tester le regroupement

Le MVP distingue notamment :

- Folio, 2020, traduction Josée Kamoun — ISBN `9782072878497` ;
- Du monde entier, 2018, traduction Josée Kamoun — ISBN `9782072730030` ;
- Du monde entier, 2015, traduction Amélie Audiberti — ISBN `9782070248100` ;
- Folioplus classiques, 2015, traduction Amélie Audiberti — ISBN `9782070463695` ;
- Folio, 2007, traduction Amélie Audiberti — ISBN `9782070348626` ;
- Folio SF, 2021, traduction Amélie Audiberti — papier `9782072938221`, EPUB `9782072938245`.

Les notices BnF sont enregistrées dans `source_records` afin de conserver l’ARK de provenance sans confondre la notice avec une offre de vente.

### Exemple DRM : même ebook, offre différente

Pour l’EPUB `9782072938245`, deux vendeurs consultés le 5 septembre 2026 n’annoncent pas le même mécanisme de protection :

- Lavoisier : Adobe DRM, 9,49 € lors de la vérification ;
- E-librairie Leclerc : CARE, 9,49 € lors de la vérification.

Le schéma garde donc le DRM au niveau de l’**offre** lorsqu’il dépend du vendeur. Une fiche édition ne doit pas transformer une observation commerciale en propriété universelle du fichier.

## Autres cas du corpus

Le corpus comprend aussi :

- Alexandre Dumas, Jules Verne et Jane Austen pour multiplier les cas de domaine public et de traductions ;
- Albert Camus, Frank Herbert, Ursula K. Le Guin et J. R. R. Tolkien pour vérifier que des œuvres encore protégées ne reçoivent aucun accès gratuit déduit automatiquement ;
- `Le Petit Prince` avec le statut interne `review`, afin de tester un cas juridique qui ne doit pas être tranché par une simple formule automatique. Saint-Exupéry a reçu la mention « Mort pour la France » et l’article L123-10 du Code de la propriété intellectuelle prévoit une prorogation particulière ; l’interface doit donc assumer l’incertitude tant qu’une vérification complète du cas et de l’édition n’a pas été faite.

## Sources de référence

- Légifrance — Code de la propriété intellectuelle, articles L123-1 et L123-10.
- BnF — « Les auteurs du domaine public dans data.bnf.fr » (17 mars 2026).
- BnF API — service SRU du Catalogue général et Licence ouverte de l’État pour les métadonnées.
- data.bnf.fr — George Orwell (1903-1950), ark:/12148/cb11918228x.
- data.bnf.fr — Victor Hugo (1802-1885), ark:/12148/cb11907966z.
- Catalogue général BnF — notices des éditions françaises de *1984* et de *Les Misérables*.
- Wikisource — *Les Misérables*, édition Émile Testard, 1890.
- Lavoisier et E-librairie Leclerc — caractéristiques commerciales observées de l’EPUB Gallimard `9782072938245`.

## Règle produit

Un statut « domaine public » au niveau d’une œuvre ne doit jamais entraîner automatiquement un bouton « télécharger en français ». Un bouton de lecture/téléchargement dépend d’une **offre ou source précise**, dans une langue et une édition identifiées, dont les droits ont été vérifiés.

De même, un ISBN identifie une édition mais ne garantit ni son prix actuel, ni son stock, ni même nécessairement le DRM utilisé par chaque revendeur. Ces informations doivent rester datées et rattachées à la source qui les affirme.
