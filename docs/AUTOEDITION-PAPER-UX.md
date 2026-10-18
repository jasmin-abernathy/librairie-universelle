# Parcours papier — ordre des étapes et ergonomie mobile

Ordre affiché dans le panneau « Livre papier » :
1. Importer un DOCX/ODT, régler la pagination, puis générer le PDF intérieur.
2. Convertir si nécessaire le PDF intérieur en PDF/X-4 ou PDF/X-1a. **Étape facultative** : le serveur peut ne pas être configuré, le PDF standard reste valide si l'imprimeur l'accepte.
3. Créer et enregistrer séparément les deux couvertures. Si l'imprimeur demande du PDF/X pour elles, revenir à l'atelier de conversion individuellement.
4. Estimer le coût d'impression sur la grille connue (indicative, sans modifier le comparateur d'imprimeurs).
5. Vérifier les trois PDF et constituer le dossier ZIP destiné à l'imprimeur.

Une navigation interne en haut du panneau donne accès immédiatement aux cinq étapes. L'ordre du HTML est identique à l'ordre de lecture et de tabulation ; aucune réorganisation purement visuelle par CSS ne vient inverser l'accessibilité.

Les boutons CTA sont compacts et fluides en mobile, le bouton PDF/X est visuellement secondaire pour éviter de le présenter comme obligatoire ou déjà activé. Le champ d'import JSON des brouillons possède un véritable input de fichier focusable au clavier, un label distinct et un statut lisible, sans découpe du texte par la largeur du bouton natif. Les tests PHP vérifient l'ordre des étapes, l'unicité des liens, la présence d'une navigation et du support mobile.
