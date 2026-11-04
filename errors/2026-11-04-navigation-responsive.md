# 2026-11-04 — navigation encombrée, menu mobile absent

## Symptôme
Le menu public de Librairie universelle affichait sept liens au même niveau, avec des retours à la ligne sur écrans étroits et une hiérarchie incompréhensible entre lecture, autoédition et diffusion. Une capture utilisateur montrait plusieurs rangées de liens volumineux sous le logo.

## Cause
Le composant commun `src/views/site-header.php` rendait une liste plane. La feuille `app.css` utilisait `flex-wrap:wrap` et, sous 620 px, empilait le logo et la navigation sans bouton burger. De nouvelles entrées avaient été ajoutées sans réexaminer le parcours ni l'espace disponible.

## Correctif
- Regrouper la navigation en quatre destinations lecteurs puis « Pour les auteurs » (préparation, référencement vérifié, contact de librairies) et « À propos » (projet, politique sans IA).
- Remplacer le libellé provisoire « nom de travail » par la description factuelle « Livres, librairies et auteurs ».
- Barre bureau compacte sur une seule ligne, menus secondaires natifs `details/summary`, et menu burger en dessous de 1120 px.
- Garder l'accès sans JavaScript (liste déroulée), permettre l'ouverture/fermeture via un bouton `aria-expanded`, Escape, clic extérieur, clic sur un lien et changement de largeur ; focus clavier restauré.
- Styles cohérents avec les variables du site, au moins 44 px pour les cibles tactiles, défilement si le menu dépasse la hauteur de l'écran.
- Invalider les anciennes URLs des CSS sur toutes les pages utilisant l'en-tête partagé, y compris l'import de work.css.
- Tests PHP (ordre, liens, active states, CSS et discoverability) et Node (burger, état ARIA, clavier, extérieur et redimensionnement), plus sondes HTTP en CI.

## Prévention
Appliquer la section « UI/UX sweep » de repo-factory avant l'ajout de toute entrée. Une navigation doit être réévaluée au niveau du composant commun et testée sur écran compact et écran large. N'annoncer le déploiement qu'après concordance SHA et marqueur HTML dans le live.

## Non-régression CI

- Symptôme : CI au SHA 336b7fa a échoué sur « Rupture responsive manquante » alors que le CSS portait bien le breakpoint 1120 px.
- Cause : l'assertion PHP était restée fixée à 1040 px après l'ajustement du breakpoint et l'invalidation des caches.
- Correctif : faire correspondre le test au breakpoint réellement retenu (1120 px), puis rejouer la CI sur le SHA final.
- Règle : après un ajustement de valeur partagée par CSS/JS/tests, vérifier toutes les occurrences fonctionnelles avant le commit.
