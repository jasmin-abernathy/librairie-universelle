# Vérification du vrai Git live, indépendante du mécanisme de déploiement

Le point de lecture **public, minimal et sans secret** `/deploy-version.php` récupère le SHA exact du `HEAD` du dépôt présent sur o2switch. Il lit les références Git (branche normale, références packées ou HEAD détachée) **sans exécuter de commande système**. Si la copie du site ne dispose pas d'un dépôt Git lisible, il renvoie HTTP 503 : on ne prétend pas avoir confirmé sa version.

Le workflow GitHub `Verify deployed Librairie`, déclenché après chaque changement de `main`, compare le SHA attendu au résultat HTTP réel. Il vérifie ensuite les pages de l'autoédition, du dossier pour l'imprimeur, la pagination et le catalogue, avec jusqu'à 12 tentatives toutes les 20 secondes pour absorber le délai de synchronisation.

Pourquoi ne pas écrire `deploy-version.txt` dans le script de déploiement ? Le site peut être mis à jour par différents mécanismes (cron, webhook, relais SSH). Un marqueur créé seulement par l'un d'eux reste absent ou obsolète alors que le code est réellement déployé. La lecture dynamique du HEAD évite ce piège.

La réponse n'expose **que** les 40 caractères hexadécimaux de la révision Git, jamais le contenu ou le chemin du dépôt. HTTP 200 sans concordance de SHA n'est pas une preuve d'un déploiement réussi.
