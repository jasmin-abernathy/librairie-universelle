# Fiches de livres proposées par les auteurs — sans réception de manuscrits

## Fonctionnement retenu (4 novembre 2026)

La Librairie propose un catalogue de fiches de livres saisies par leurs auteurs. Chaque fiche doit être vérifiée et explicitement publiée par l'administrateur. La plateforme ne reçoit pas de manuscrits, ne vend pas à la place des auteurs et n'encaisse aucun paiement.

Deux parcours indépendants :
- Préparation des fichiers : Atelier EPUB pour l'EPUB ; intérieur et couvertures PDF, dossier ZIP pour l'impression. L'auteur récupère lui-même les fichiers.
- Référencement et diffusion : public/proposer-livre.php -> public/admin/livres-auteurs.php -> public/livres-auteurs.php et public/livre-auteur.php. Le parcours public/diffuser-mon-livre.php aide également à contacter des librairies sans transmission automatique.

## Données et contrôle de publication

- Les propositions ne contiennent aucune pièce jointe : titre, auteur public, résumé, format, prix, ISBN facultatif, statut vendeur déclaré, lien HTTPS vers la boutique et/ou e-mail public, plus e-mail de suivi privé.
- Pour un vendeur professionnel, le SIRET de 14 chiffres est obligatoire. Il doit cliquer sur « Rechercher l’entreprise », choisir la fiche trouvée dans l’API Recherche d’entreprises de l’État, puis confirmer sa proposition. Le serveur consulte à nouveau l’API lors de l’enregistrement, ne fait jamais confiance au JavaScript et mémorise le nom légal et la date de vérification. L’administrateur voit ces données et peut ouvrir la fiche de l’Annuaire officiel. L’absence d’une API disponible empêche temporairement le dépôt professionnel, sans permettre le contournement. Une immatriculation retrouvée n’authentifie pas le lien entre le vendeur et l’entreprise.
- Migration SQLite idempotente : colonnes siret, registered_company_name, siret_verified_at ajoutées aux bases anciennes. La publication d’une fiche pro historique sans SIRET vérifié est refusée.
- Statut initial : pending ; seul l'administrateur authentifié peut publier, rejeter, masquer ou republier. L'administration vérifie chaque fiche.
- Les pages publiques interrogent uniquement le statut published et ne sélectionnent jamais l'e-mail de suivi ni les notes privées.
- Validation serveur, requêtes PDO paramétrées, CSRF, honeypot, délai de saisie, limitation par session et au maximum trois soumissions par adresse en 24 heures.
- Les pages ne déclenchent ni paiement, ni envoi d'e-mail à un libraire, ni commande chez un imprimeur.
- L'ancien formulaire de soumission de fichiers sur autoedition.php n'accepte plus de POST (HTTP 410). Les archives ne sont pas supprimées.
- Schéma SQLite séparé, migration idempotente au démarrage.

## Avant de rendre le service public

Vérifier sur l'hébergement la présence d'un ADMIN_TOKEN solide. Sans token, le serveur refuse les propositions. Tester l'ensemble du parcours : soumettre, confirmer l'invisibilité avant validation, approuver, inspecter la fiche publiée, puis la masquer. Tester la protection de l'administration et la confidentialité de la base.

Préparer CGU, politique de confidentialité (durée de conservation, droits, demande de retrait), politique éditoriale, procédure de signalement, transparence du référencement et informations vendeur. Vérifier les règles françaises et européennes applicables aux plateformes et vendeurs, au prix du livre et à la vente aux consommateurs. Le code ne constitue pas une validation juridique. Prévoir ensuite une procédure d'actualisation ou de suppression des fiches à la demande des auteurs.

Ne pas présenter une fiche validée comme une certification de l'identité du vendeur, de la qualité, des stocks ou du prix.
