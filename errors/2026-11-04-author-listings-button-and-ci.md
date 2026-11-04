# 2026-11-04 — CTA natif dans le parcours des livres d'auteurs

- Symptôme : sur /proposer-livre.php, le bouton « Envoyer ma fiche pour vérification » s'affichait comme un gros bouton gris natif, sans cohérence avec les CTA verts du site.
- Cause confirmée : l'ajout de la page ne réutilisait pas la classe de composant existante `button-link` définie dans public/assets/css/app.css ; les boutons de recherche SIRET et de modération avaient le même défaut.
- Correction : réutilisation de `button-link` et de sa variante `secondary`, alignement des contrôles sur les variables et formes de l'application, responsive, focus préservé, version des CSS/JS incrémentée pour contourner les caches.
- Validation : test de non-régression ajouté au scénario PHP AuthorListingsTest et test de réponses du back-office enrichi en diagnostic.
- Règle : lire d'abord AGENTS.md, COMPATIBILITY-AND-DEPRECATION-PLAYBOOK.md et le design system existant avant d'ajouter un contrôle dans une page, puis vérifier le responsive et le live, comme demandé par repo-factory.
- Note de déploiement : les runners GitHub rencontraient un `curl: (56) Recv failure: Connection reset by peer` en vérifiant le domaine ; cela ne démontre pas que le code n'a pas été déployé, et doit être diagnostiqué séparément.

## Balayage complémentaire

- La revue de parcours a aussi identifié le bouton de recherche du catalogue et le bouton créé dynamiquement dans les résultats SIRET comme boutons HTML natifs ; ils utilisent désormais eux aussi le composant `button-link`.
- La CI était bloquée par quatre assertions cherchant un titre supprimé de la page `/projet.php`. Elles ont été remplacées par le repère HTML stable `id="project-title"` ; la CI est redevenue verte (run 41875307093). Ne pas conserver des assertions de contenu obsolètes après une refonte éditoriale.
