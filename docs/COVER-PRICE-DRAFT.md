# Quatrième de couverture et brouillon local

L'éditeur de couverture affiche le prix public TTC choisi par l'auteur sur la quatrième, sans le calculer automatiquement à partir des coûts de production. Le code-barres EAN-13 est calculé uniquement si l'ISBN-13 fourni est valide.

Les champs de texte, le format et les couleurs peuvent être sauvegardés **volontairement** sur l'appareil pendant 30 jours. Aucun compte ni transfert serveur ; les illustrations et PDF ne sont pas conservés. Sur ordinateur partagé, utiliser « Effacer la sauvegarde ».

L'export imprime séparément les deux faces ; aucune tranche n'est calculée. Le PDF résultant doit être vérifié dans le navigateur et validé par l'imprimeur (fonds perdus, traits de coupe, lisibilité du code-barres, paramètres de couleur, BAT). L'outil ne garantit pas PDF/X.

Un prix affiché ne constitue pas une commande ni une validation de distribution, et la mention du prix ne remplace pas les obligations légales de l'éditeur.

## Priorité au fichier de code-barres fourni
Lorsque l’auteur sélectionne une image de code-barres, cette image reste visible pendant les modifications d’ISBN, de prix ou de couleurs ; elle n’est jamais remplacée silencieusement par un code calculé. L’absence ou la suppression de l’image permet de revenir au code EAN-13 calculé à partir d’un ISBN valide. Un ISBN mal saisi continue à bloquer l’export de la quatrième, même si une image de code-barres est fournie. Ces décisions sont couvertes par les tests Node.
