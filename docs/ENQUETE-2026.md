# Enquête livres et librairies — 2026

## Point d'entrée unique

- https://librairie.lepotager.org/enquete.php
- Choix « lectrice / lecteur » : /enquete-lecteurs.php
- Choix « je représente une librairie » : /enquete-libraires.php

Les deux enquêtes sont volontairement séparées : questions, stockage et export.
Un participant peut fermer le navigateur et reprendre si son stockage local persiste.
Aucune adresse e-mail n'est demandée.

## Reprise, suppression et protection

- Le serveur ne conserve que le SHA-256 du token aléatoire de 32 octets généré au début.
- Le navigateur conserve le token original dans localStorage, dans une clé propre à chaque enquête.
- Le bouton « Effacer ma participation » supprime la ligne en base, y compris la contribution terminée.
- Les bases anciennes sont purgées quand la page est consultée au-delà de 12 mois.
- On ne demande ni nom de librairie, ni adresse précise, ni chiffre d'affaires exact.
- Le serveur de l'hébergeur peut avoir ses propres journaux techniques, indépendants du formulaire.
- Pas de publicité ni d’outil de suivi ajouté.

## Administration

Authentification BASIC existante de la librairie, à partir du jeton ADMIN_TOKEN :

- /enquete-lecteurs.php?admin=1
- /enquete-libraires.php?admin=1

Exports de réponses **terminées seulement** en JSON et CSV, sans token de reprise ni ID interne.
Les éventuels textes libres dans les CSV sont neutralisés quand leur premier caractère pourrait déclencher une formule.

## Diffusion

Ajouter un paramètre src à l'URL du point d'entrée :
?src=linkedin, ?src=librairie, ?src=mediatheque, ?src=association, ?src=reddit, ?src=autre.

Le paramètre est transmis aux deux parcours pour comparer les biais de recrutement ; il n'identifie pas la personne.

## Limites d'interprétation

L'enquête originale de janvier 2014 comporte 405 réponses actuellement dans le Google Sheets ; le mémoire en comptait 395 à la date de rédaction. Ce sont des échantillons de convenance, non représentatifs de la population française. Les questions 2026 restent seulement partiellement comparables lorsqu'elles ont changé de formulation ou d'options.

L'enquête de 2014 auprès des libraires avait deux réponses. Ne pas en faire une série statistique ; elle a servi de retour qualitatif sur la difficulté à recruter ces professionnels et à poser d'abord la question du support numérique.

Le sondage 2026 n'est pas une évaluation de l'utilisation de Librairie universelle et ne doit pas servir à profiler les visiteurs du catalogue.

## Participation des mineurs

Le parcours lecteurs ne se limite pas aux 18 ans et plus : catégories `under_15` et `15_17`, puis adultes. Pour un jeune de moins de 15 ans, l'interface sollicite l'accord du jeune et de son parent/titulaire de l'autorité parentale avant de créer la session. Une trace déclarative sans données nominatives est stockée dans `reader_survey_consents` et supprimée en cascade avec la réponse. Pour les 15–17 ans, le consentement autonome est prévu en tant que participation à un service en ligne au regard de l'article 45 de la loi Informatique et Libertés. Cela ne constitue pas une majorité numérique générale ; si ce projet est formellement qualifié de recherche scientifique et fondé sur le consentement, vérifier les exigences spécifiques auprès de la CNIL. Le formulaire ne vérifie pas l'identité du parent et ne prétend pas le faire. Références : CNIL, « Recommandation 4 » ; article 45 de la loi Informatique et Libertés.
