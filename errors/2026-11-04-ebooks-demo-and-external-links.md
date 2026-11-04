# 2026-11-04 — offres ebooks de démonstration et liens externes

## Symptômes
- La page ebooks présentait d'emblée des offres initiales datées, donnant l'impression d'offres à jour et vérifiées.
- Un intitulé « Partenaire test » et un ancien « Livre indépendant de test » étaient susceptibles de réapparaître dans une base existante.
- Des sorties vers des vendeurs, sources et services partenaires ne portaient pas toutes `target="_blank"`.

## Causes
- `database/seed.sql` insérait six offres de démarrage, en plus des métadonnées bibliographiques utiles.
- `tests/MvpWorkflowTest.php` créait un faux vendeur dans une base SQLite temporaire : cette fixture n'aurait pas dû être reprise ailleurs ni assimilée à un vrai partenaire.
- Plusieurs templates PHP et une ancre créée en JavaScript ne réutilisaient pas la convention d'ouverture des liens externes.

## Correctifs
- Suppression des six offres du corpus d'initialisation ; conservation des véritables œuvres/éditions et du répertoire de librairies.
- Suppression de tout le bloc de faux partenaire dans le test MVP ; les tests de fonctionnalités métier sont préservés dans une base de CI temporaire.
- Filtrage commun des anciennes offres de démonstration qui pourraient encore exister dans SQLite, ainsi que de la source fictive et de l'ancienne œuvre nommée « Livre indépendant de test ». La recherche de l'accueil et la fiche de détail de cette œuvre de test sont aussi exclues.
- La page ebooks n'affiche plus de propositions préchargées : recherche explicite requise avant les résultats.
- Liens HTTP(S) externes en nouvel onglet avec `noopener noreferrer` dans les templates et générateurs JS, sans changer les liens internes.
- Test de non-régression sur l'absence de faux catalogue et sur les liens externes.
- Mise à jour des consignes dans AGENTS.md pour éviter le retour d'échantillons visibles.

## Limites et gestion des erreurs
- Les enregistrements déjà présents dans une base de production distante sont **masqués par le code**. Cette opération ne les efface pas physiquement de SQLite : ne pas annoncer un nettoyage matériel de la base sans accès au serveur.
- Une première opération groupée de modification a été refusée par le contrôle d'exécution des outils ; le travail a été poursuivi avec des changements plus petits et des blobs Git validés avant commit.
- Le déploiement nécessite le mécanisme GitHub → o2switch existant. Un commit ni une CI verte ne prouvent que la production sert la version attendue.

## Prévention
Contrôler le parcours public à vide, avec recherche et après réimport, distinguer les fixtures isolées des données réellement diffusées, et auditer les ancres externes/JS sur toutes les surfaces lors de chaque ajout.
