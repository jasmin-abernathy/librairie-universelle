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

## Profil Corep Metz : A5 et contrôles avant impression
Le profil **Corep Metz** prépare l'intérieur A5 (148 × 210 mm) et les deux faces de couverture séparées en A5, sans traits de coupe ni fonds perdus sur les couvertures. Le manuscrit DOCX/ODT n'est pas réduit depuis une page A4 : le moteur le recompose, recalcule les marges, adapte la pagination et insère au besoin des pages blanches pour commencer les chapitres à droite. Corep peut aussi extraire les deux couvertures d'un PDF intérieur, mais les fichiers séparés restent conseillés pour vérifier le dossier.

Rachel (Corep Metz) a indiqué **340 feuilles en recto seul ou 680 pages imprimées en recto-verso** pour un livre sur papier 80 g classique. L'aperçu contrôle le **nombre réel de pages** et désactive l'export Corep lorsqu'il dépasse **680 pages recto-verso**. Cela ne signifie pas qu'un autre prestataire applique la même limite, ni que Corep garantit un autre papier sans validation. En cas de dépassement, demander une fabrication particulière ou envisager plusieurs volumes.

La recommandation de caractères « 9 ou 10 mm » figurant dans l'e-mail de Rachel est **interprétée dans ce projet comme 9–10 pt typographiques**. Le moteur utilise **10,5 pt** pour le corps du texte A5, recommande au moins **10 pt** en lecture courante et fixe **9 pt** pour les éléments secondaires plus petits (code, préformaté, tableaux). Vérifier visuellement la taille réelle dans le PDF exporté à 100 %, particulièrement après recomposition depuis un manuscrit A4.

Le profil **Bookelis** permet d'autres formats et un gabarit de couverture complète avec tranche ; aucune restriction Corep à 680 pages ne s'y applique. Dans tous les cas, un BAT reste à valider chez l'imprimeur : le moteur n'homologue pas automatiquement les polices incorporées, les images, les marges, le fond perdu et la conformité du fichier PDF.

## À ne pas promettre
Cette première version prend en charge DOCX et ODT, pas les anciens DOC ; il reste à faire valider en conditions réelles les polices, images, pagination, recto verso, fonds perdus et rendu navigateur. Pas de transfert automatique vers Corep.

## Vérifications
PHP lint et Node syntax check de la CI, tests/OfficePrintSourceTest.php, tests/EpubPrintSourceTest.php et HTTP smoke de l'interface.
## Préservation et alertes du manuscrit
Les paragraphes situés dans des cellules de tableaux DOCX ne sont plus extraits comme du texte ordinaire. Les tableaux restent exclus de cette première recomposition et un avertissement invite à les vérifier dans le PDF. Les styles de caractères DOCX explicitement désactivés restent sans gras/italique. Pour les ODT, le gras et l'italique des styles de texte (`content.xml` et `styles.xml`) sont repris, y compris un niveau d'héritage de styles, sans importer des feuilles CSS arbitraires. Une illustration manquante, trop volumineuse ou non compatible est signalée clairement : l'auteur doit corriger le manuscrit avant de considérer le PDF complet.
