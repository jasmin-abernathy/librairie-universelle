<?php

declare(strict_types=1);

/**
 * Copier ce fichier vers config/local.php sur le serveur.
 * config/local.php est ignoré par Git et ne doit jamais être committé.
 */
return [
    'name' => 'Librairie universelle',
    'env' => 'production',
    'database_path' => '/home/TON_LOGIN/private/librairie-universelle/app.sqlite',
    'storage_path' => '/home/TON_LOGIN/private/librairie-universelle/storage',
    'self_publishing_enabled' => false,
    'feedback_enabled' => false,
    'admin_token' => 'REMPLACER_PAR_UN_SECRET_LONG',
    'offer_max_age_days' => 7,
    'moselle_base_url' => '',
    'atelier_epub_url' => 'https://atelier-epub.lepotager.org',
];
