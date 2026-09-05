# Principes produit et données

## 1. Pas d’IA

Le projet n’utilise pas de modèle d’IA, de machine learning, d’embeddings ni de profilage prédictif pour rechercher, classer ou recommander des livres.

Une fonctionnalité future n’est acceptable que si son comportement peut être décrit par une règle compréhensible et vérifiable. Exemples acceptables :

- même auteur/autrice ;
- même collection ;
- même thème explicitement renseigné ;
- même langue ou période ;
- sélection rédigée et signée par une librairie, une bibliothèque ou une personne éditrice ;
- tri choisi par l’utilisateur : distance, prix, disponibilité, date, ordre alphabétique.

Toute recommandation doit afficher **pourquoi elle est montrée**.

## 2. Pas de profilage caché

Le MVP ne construit pas de profil publicitaire ni de score comportemental. L’historique de lecture ou d’achat n’a pas vocation à alimenter un classement secret.

Si une personnalisation explicite est ajoutée plus tard, elle doit être facultative, compréhensible, exportable et supprimable.

## 3. L’œuvre est le pivot

Une œuvre n’est pas une édition.

Une œuvre peut avoir :

- plusieurs ISBN ;
- plusieurs éditeurs ;
- plusieurs traductions ;
- une édition papier encore protégée alors que le texte original est dans le domaine public ;
- des illustrations, traductions, préfaces ou appareils critiques ayant leurs propres droits.

Le statut juridique doit donc être attaché avec prudence au bon niveau.

## 4. Domaine public : prudence par défaut

`public_domain_status = yes` ne doit être enregistré qu’avec une justification/source suffisante. En cas de doute : `review` ou `unknown`.

La plateforme peut pointer vers une ressource externe librement accessible sans pour autant supposer que sa réutilisation commerciale ou son réhébergement sont autorisés.

## 5. Sources visibles

Chaque donnée importée doit pouvoir être rattachée à une source et, lorsque pertinent, à un identifiant externe, une URL, des conditions d’utilisation et une licence de données.

## 6. Sobriété et accessibilité

- rendu serveur d’abord ;
- JavaScript uniquement pour amélioration progressive ;
- pas de chargement publicitaire ou analytique tiers par défaut ;
- polices système tant qu’une identité visuelle ne nécessite pas autre chose ;
- navigation clavier et HTML sémantique ;
- pas d’animation nécessaire à la compréhension.
