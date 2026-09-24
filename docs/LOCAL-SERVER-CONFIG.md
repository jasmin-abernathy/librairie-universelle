# Configuration locale serveur

La production peut être configurée sans modifier `public/.htaccess` ni committer de secrets.

Copier :

```bash
cp config/local.example.php config/local.php
chmod 600 config/local.php
```

Puis éditer `config/local.php`.

Le fichier est ignoré par Git. Les variables d'environnement restent prioritaires lors de la construction de la configuration de base, puis `config/local.php` peut remplacer les valeurs finales pour le serveur.

Pour utiliser un emplacement différent :

```bash
export APP_LOCAL_CONFIG=/chemin/prive/librairie-config.php
```

Le fichier doit retourner un tableau PHP.
