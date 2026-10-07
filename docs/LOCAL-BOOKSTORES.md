# Annuaire local des librairies

## Périmètre initial

L’annuaire démarre à Metz et en Moselle avec un recensement du 7 octobre 2026.

- 23 fiches sont conservées dans la base.
- 21 fiches sont actuellement en statut `listed`.
- 2 fiches sont en statut `review` et ne sont pas exposées par l’API publique tant que leur activité de librairie n’est pas suffisamment confirmée.
- Les grandes chaînes nationales généralistes ne font pas partie de ce premier périmètre.

La source et la date de dernière vérification restent attachées à chaque fiche.

## Recherche par proximité

L’adresse saisie par l’utilisateur n’est pas envoyée au serveur du Potager du Web.

Le navigateur interroge directement le service public d’autocomplétion de la Géoplateforme :

`https://data.geopf.fr/geocodage/completion/`

Le service s’appuie notamment sur la Base Adresse Nationale. Les requêtes sont temporisées ; les adresses publiques des librairies sont géocodées par lots puis leurs coordonnées sont mises en cache dans le stockage local du navigateur.

Le serveur de la Librairie universelle ne reçoit que la requête anonyme vers `/api/bookstores.php`, sans adresse ni coordonnées de l’utilisateur.

## Curation et exclusions

Une fiche peut être `listed`, `review` ou `hidden`.

Pour toute exclusion liée à une affiliation politique, ne jamais déduire une orientation à partir :

- des livres vendus ;
- d’un événement isolé ;
- d’un avis client ;
- des opinions privées supposées d’un salarié ou d’un dirigeant.

Une exclusion de ce type nécessite une affiliation publique et documentée du commerce, ou de sa direction agissant explicitement au nom du commerce, ainsi qu’une source fiable permettant d’établir la nature de l’organisation concernée. La source et la date doivent être conservées dans la note de curation interne avant de passer la fiche à `hidden`.

Le contrôle effectué lors du recensement initial n’a pas produit de preuve publique suffisamment solide justifiant de masquer une des 21 fiches affichées. Cette absence de résultat n’est pas une garantie définitive : les fiches restent révisables.
