<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Ce script doit être exécuté en ligne de commande.\n");
    exit(1);
}

$config = require dirname(__DIR__) . '/config/app.php';

$requiredExtensions = ['pdo_sqlite', 'mbstring', 'SimpleXML', 'fileinfo'];
$recommendedExtensions = ['curl', 'zip'];
$errors = 0;

fwrite(STDOUT, "Librairie universelle — préflight\n");
fwrite(STDOUT, "PHP " . PHP_VERSION . "\n\n");

foreach ($requiredExtensions as $extension) {
    $loaded = extension_loaded($extension);
    fwrite(STDOUT, sprintf("[%s] extension %s\n", $loaded ? 'OK' : 'ERREUR', $extension));
    if (!$loaded) {
        $errors++;
    }
}
foreach ($recommendedExtensions as $extension) {
    fwrite(STDOUT, sprintf("[%s] extension %s (recommandée)\n", extension_loaded($extension) ? 'OK' : 'INFO', $extension));
}

$databaseDirectory = dirname((string) $config['database_path']);
$storageDirectory = rtrim((string) $config['storage_path'], '/');
foreach ([$databaseDirectory => 'données SQLite', $storageDirectory => 'stockage privé'] as $directory => $label) {
    if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
        fwrite(STDOUT, "[ERREUR] impossible de créer {$label}: {$directory}\n");
        $errors++;
        continue;
    }
    $writable = is_writable($directory);
    fwrite(STDOUT, sprintf("[%s] %s accessible en écriture: %s\n", $writable ? 'OK' : 'ERREUR', $label, $directory));
    if (!$writable) {
        $errors++;
    }
}

if ($config['self_publishing_enabled'] || $config['feedback_enabled']) {
    if ((string) $config['admin_token'] === '') {
        fwrite(STDOUT, "[ERREUR] ADMIN_TOKEN doit être défini avant d’ouvrir les fonctions alpha.\n");
        $errors++;
    } else {
        fwrite(STDOUT, "[OK] ADMIN_TOKEN configuré.\n");
    }
} else {
    fwrite(STDOUT, "[OK] écritures publiques fermées par défaut.\n");
}

fwrite(STDOUT, sprintf("[INFO] autoédition: %s\n", $config['self_publishing_enabled'] ? 'ouverte' : 'fermée'));
fwrite(STDOUT, sprintf("[INFO] feedback: %s\n", $config['feedback_enabled'] ? 'ouvert' : 'fermé'));
fwrite(STDOUT, sprintf("[INFO] fraîcheur max des offres: %d jours\n", (int) $config['offer_max_age_days']));

if ($errors > 0) {
    fwrite(STDERR, "\nPréflight terminé avec {$errors} erreur(s).\n");
    exit(2);
}

fwrite(STDOUT, "\nPréflight OK.\n");
