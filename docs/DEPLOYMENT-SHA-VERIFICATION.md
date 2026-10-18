# Vérification de la production par SHA

Le script de déploiement `scripts/server-update.sh` publie le SHA exact du dépôt live dans `public/deploy-version.txt` seulement après réussite du preflight. L'écriture est atomique et le fichier ne contient aucune donnée privée.

Le workflow GitHub `Verify deployed Librairie`, exécuté après chaque push sur `main` ou manuellement, compare ce SHA au commit attendu, puis vérifie les ressources publiques de l'autoédition, du dossier imprimeur, de la pagination et du catalogue. Il retente 12 fois toutes les 20 secondes.

HTTP 200 **sans concordance du SHA** n'est plus une preuve suffisante. Le contrôle n'interagit avec aucun fichier utilisateur et ne déploie rien. En cas d'échec, vérifier le relais de mise à jour serveur avant d'affirmer que le site est à jour.
