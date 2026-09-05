<?php

declare(strict_types=1);

final class Database
{
    public static function connect(array $config): PDO
    {
        $databasePath = $config['database_path'];
        $databaseDirectory = dirname($databasePath);

        if (!is_dir($databaseDirectory) && !mkdir($databaseDirectory, 0775, true) && !is_dir($databaseDirectory)) {
            throw new RuntimeException('Impossible de créer le dossier de données.');
        }

        $pdo = new PDO('sqlite:' . $databasePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 5000');

        // Le schéma est idempotent (CREATE ... IF NOT EXISTS). On le rejoue à
        // chaque connexion afin qu'une base existante reçoive aussi les nouvelles
        // tables du MVP sans devoir être supprimée manuellement.
        self::execFile($pdo, $config['schema_path'], 'schéma SQL');

        if (!empty($config['seed_path']) && is_file($config['seed_path'])) {
            self::execFile($pdo, $config['seed_path'], 'corpus initial');
        }

        return $pdo;
    }

    private static function execFile(PDO $pdo, string $path, string $label): void
    {
        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new RuntimeException('Impossible de lire le ' . $label . '.');
        }

        $pdo->exec($sql);
    }
}
