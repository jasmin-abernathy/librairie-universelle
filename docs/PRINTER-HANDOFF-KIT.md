# Dossier d’impression — contrôle local avant transfert

L'URL `/dossier-impression.php` complète les étapes papier : génération du PDF intérieur depuis un DOCX/ODT, PDF de la première et de la quatrième, puis création d'une **fiche de transmission**.

Le contrôle lit uniquement **5 octets** de chaque fichier côté navigateur afin de reconnaître l'en-tête `%PDF-`, puis vérifie les trois rôles et leurs noms, les champs du livre et les cases de vérification. Il n'envoie ni fichiers, ni métadonnées, ni e-mails à un tiers. Les PDF restent des pièces jointes à transmettre volontairement par l'auteur.

La fiche JSON (noms et tailles des trois PDF, description et date) et le message à copier aident à faire confirmer les conditions par l'imprimeur. Les fichiers ne sont jamais intégrés à la fiche et **aucun ZIP ni PDF/X ne sont fabriqués** ici.

**Limites :** vérifier l'en-tête n'est pas vérifier les pages, les dimensions, les images, les polices, le fond perdu, le code-barres, la couleur ou le BAT. Le dossier ne peut pas être qualifié de « prêt à imprimer » sans cette validation humaine. Toute commande et tout devis restent manuels.

Les tarifs Corep restent indicatifs et n'ont pas été modifiés ; pas de comparaison des autres imprimeurs.
