# Autoédition : onglets et génération de PDF depuis des manuscrits bureautiques

## Navigation
La page /autoedition.php présente quatre onglets : Livre papier (défaut), Mon livre, Version numérique, Déposer mon projet. L'onglet papier contient la mise en page, l'aperçu PDF, les couvertures et l'impression. Les autres onglets préservent les champs de soumission. Sans JavaScript, tout reste visible. Le lien ?partie=paper ouvre la partie papier.

## Conversion
Le champ office_manuscript accepte les formats DOCX et ODT. Les fichiers DOC binaires anciens doivent être réenregistrés au format DOCX ou ODT dans Word/LibreOffice ; TXT n'est pas pris en charge. Ne pas affirmer que l'ancien .doc est converti.
OfficePrintSource.php extrait titres, paragraphes, mise en évidence de base DOCX et images compatibles du package, sans modifier le document source. Les archives ZIP/XML sont bornées, le contenu est échappé, aucune ressource distante ou macro n'est exécutée.
Il s'agit d'une recomposition éditoriale : tableaux, notes, objets flottants, styles personnalisés et polices particulières nécessitent une vérification par l'auteur. Le PDF final n'est pas garanti PDF/X.

## Générateur et contrôle
Le bouton de l'onglet Livre papier envoie le manuscrit uniquement à /print-preview.php (aperçu temporaire) qui applique PrintSettings et Paged.js. Le navigateur propose ensuite l'impression en PDF : échelle 100 %, sans en-têtes/pieds ajoutés, arrière-plans inclus. L'auteur doit revoir le document et le BAT, puis déposer le PDF résultant dans print_pdf.
La compatibilité historique avec une entrée EPUB dans /print-preview.php est conservée, mais aucun EPUB original n'est modifié. Atelier EPUB n'est pas concerné.

## Format du pilote Corep Metz
L'intérieur des livres et les couvertures sont composés exclusivement en A5 (148 × 210 mm), quel que soit le format initial du DOCX ou de l'ODT. Le serveur refuse les anciens réglages d'un autre format et le parcours public n'en propose plus. Les anciens brouillons de couverture ne rétablissent pas de dimension alternative. Aucune conversion par réduction d'un PDF A4 n'est pratiquée ; le manuscrit est recomposé, les marges et les pages sont recalculées. Un ouvrage long peut donc gagner beaucoup de pages.

Les débuts de chapitre sur page de droite sont proposés par défaut, avec insertion des pages blanches nécessaires par le moteur de pagination. Après export, le nombre de pages, la taille des caractères, la position des chapitres, les pages blanches, les images et les exigences propres à l'imprimeur doivent encore être contrôlés. L'outil prépare un PDF à faire valider, il ne garantit pas automatiquement un BAT conforme.

## À ne pas promettre
Cette première version prend en charge DOCX et ODT, pas les anciens DOC ; il reste à faire valider en conditions réelles les polices, images, pagination, recto verso, fonds perdus et rendu navigateur. Pas de transfert automatique vers Corep.

## Vérifications
PHP lint et Node syntax check de la CI, tests/OfficePrintSourceTest.php, tests/EpubPrintSourceTest.php et HTTP smoke de l'interface.
## Préservation et alertes du manuscrit
Les paragraphes situés dans des cellules de tableaux DOCX ne sont plus extraits comme du texte ordinaire. Les tableaux restent exclus de cette première recomposition et un avertissement invite à les vérifier dans le PDF. Les styles de caractères DOCX explicitement désactivés restent sans gras/italique. Pour les ODT, le gras et l'italique des styles de texte (`content.xml` et `styles.xml`) sont repris, y compris un niveau d'héritage de styles, sans importer des feuilles CSS arbitraires. Une illustration manquante, trop volumineuse ou non compatible est signalée clairement : l'auteur doit corriger le manuscrit avant de considérer le PDF complet.
