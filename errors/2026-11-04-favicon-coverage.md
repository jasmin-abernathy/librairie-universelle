# 2026-11-04 — favicon manquante sur certaines pages

- **Symptôme :** la favicon `/favicon.svg` présente sur l’accueil n’était pas déclarée dans plusieurs nouvelles pages, notamment les fiches d’auteurs, la modération et les outils PDF.
- **Cause :** ces pages utilisent des `<head>` HTML locaux plutôt qu’un gabarit de document partagé. Lors d'une inspection initiale, une tentative de traitement supposant un seul `<head>` par fichier a échoué car `print-preview.php` a deux variantes HTML (erreur/édition).
- **Correction :** reprendre exactement la déclaration de `public/index.php` dans les en-têtes manquants, y compris le rendu 404 d'une fiche auteur et les deux états de prévisualisation PDF. Ne pas modifier ni dupliquer la ressource SVG.
- **Prévention :** test `tests/FaviconCoverageTest.php` répertoriant récursivement les pages HTML/PHP et vérifiant chaque bloc `<head>` : une favicon valide, unique et identique à celle de l’accueil.
- **Validation :** exécuter le test sous CI, puis vérifier le déploiement du commit et son marqueur sur le site si l'accès public le permet.
