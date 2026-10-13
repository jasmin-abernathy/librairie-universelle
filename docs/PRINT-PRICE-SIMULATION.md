# Simulation d'impression dans le dépôt d'autoédition

`/autoedition.php` propose un simulateur de tirage dans l'étape « Version papier et impression ».

- Paramètres affichés : A5, dos carré collé, couverture couleur et intérieur noir et blanc 80 g ; 120/200/300 pages et 10/30/50/100 exemplaires, selon les paliers transmis par le pilote local.
- La grille n'est pas copiée dans ce dépôt : les prix **publiables et autorisés** sont récupérés de façon limitée via `MOSELLE_BASE_URL/api.php?action=print-price-grids` (sans jeton et sans accès aux données internes).
- Sans `MOSELLE_BASE_URL`, sans accord de l'imprimeur ou si le service ne répond pas : montant indisponible, aucune estimation fictive. Les autres étapes du formulaire restent utilisables.
- Le simulateur respecte le format choisi et la reliure. Seules les combinaisons exactes publiées sont utilisées, jamais les interpolations.
- Le solde est un repère pédagogique calculé à partir du prix papier librement fixé par l'auteur, moins une **remise libraire hypothétique de 30 %** et le coût unitaire d'impression. Il n'est pas une marge nette.
- Le paiement, la réservation, le tirage, l'affectation à une librairie et le retrait ne sont **pas activés** par ce module.

Pour activer des prix Corep : `librairie-moselle` doit d'abord enregistrer un véritable statut partenaire, une autorisation écrite de publication et une date de validité. Cette action est strictement humaine et réversible. Le prix public doit ensuite rester sous la responsabilité de l'autoéditeur ; ni les prix ni les achats ne sont verrouillés par ce comparateur.

Tests : `php tests/MosellePrintGridClientTest.php`, `node --check public/assets/js/printing-estimate.js` et la CI du dépôt.
