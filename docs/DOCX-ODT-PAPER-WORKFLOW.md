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

## À ne pas promettre
Cette première version prend en charge DOCX et ODT, pas les anciens DOC ; il reste à faire valider en conditions réelles les polices, images, pagination, recto verso, fonds perdus et rendu navigateur. Pas de transfert automatique vers Corep.

## Vérifications
PHP lint et Node syntax check de la CI, tests/OfficePrintSourceTest.php, tests/EpubPrintSourceTest.php et HTTP smoke de l'interface.