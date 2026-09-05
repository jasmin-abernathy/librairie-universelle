<?php

declare(strict_types=1);

final class Database
{
    public static function connect(array $config): PDO
    {
        $databasePath = $config['database_path'];
        $databaseDirectory = dirname($databasePath);
        $isNewDatabase = !is_file($databasePath);

        if (!is_dir($databaseDirectory) && !mkdir($databaseDirectory, 0775, true) && !is_dir($databaseDirectory)) {
            throw new RuntimeException('Impossible de créer le dossier de données.');
        }

        $pdo = new PDO('sqlite:' . $databasePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 5000');

        if ($isNewDatabase) {
            $schema = file_get_contents($config['schema_path']);
            if ($schema === false) {
                throw new RuntimeException('Impossible de lire le schéma SQL.');
            }
            $pdo->exec($schema);
        }

        return $pdo;
    }
}
