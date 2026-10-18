# Paged.js v0.4.3 — moteur de pagination local

- **Licence :** MIT, copyright (c) 2018 Adam Hyde (texte dans `LICENSE.md`).
- **Version :** `pagedjs@0.4.3`, utilisé uniquement pour la composition papier.
- **Provenance de la copie :** distribution v0.4.3 à en-tête MIT, relevée dans le dépôt GitHub `Nian2026/MSP`, commit immuable `895506874967e9cde8523c0a9c595e2f7e855ee0`. Le code est conservé sous `public/assets/vendor/pagedjs/`.
- **Contrôle de la provenance exacte :** en CI, `npm pack pagedjs@0.4.3 --ignore-scripts` puis comparaison byte-à-byte du bundle distribué officiellement sur npm avec le fichier versionné. Une différence bloque la fusion.
- **Fonctionnement :** aucun script de pagination chargé depuis un CDN lorsque l'auteur prévisualise son manuscrit. Le moteur reste gratuit et sous licence libre.
- **Confidentialité :** `print-preview.php` impose une CSP limitant scripts, images, réseau et soumissions. Les illustrations sont intégrées en `data:` après filtrage des formats par l'import DOCX/ODT. Aucun PDF n'est transmis à un tiers par cet aperçu.
- **Limite :** l'enregistrement PDF repose toujours sur le navigateur ; la certification PDF/X, les polices incorporées, fonds perdus et le BAT ne sont pas garantis.
