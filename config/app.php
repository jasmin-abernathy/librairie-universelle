<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$boolEnv = static function (string $name, bool $default = false): bool {
    $value = getenv($name);
    if ($value === false || $value === '') {
        return $default;
    }

    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
};

return [
    'name' => getenv('APP_NAME') ?: 'Librairie universelle',
    'env' => getenv('APP_ENV') ?: 'production',
    'database_path' => getenv('DATABASE_PATH') ?: $root . '/data/app.sqlite',
    'schema_path' => $root . '/database/schema.sql',
    'seed_path' => $root . '/database/seed.sql',
    'storage_path' => getenv('STORAGE_PATH') ?: $root . '/storage',
    'self_publishing_enabled' => $boolEnv('SELF_PUBLISHING_ENABLED', false),
    'feedback_enabled' => $boolEnv('FEEDBACK_ENABLED', false),
    'admin_token' => getenv('ADMIN_TOKEN') ?: '',
    'offer_max_age_days' => max(1, (int) (getenv('OFFER_MAX_AGE_DAYS') ?: 7)),
    'max_epub_bytes' => max(1_048_576, (int) (getenv('MAX_EPUB_BYTES') ?: 52_428_800)),
    'max_pdf_bytes' => max(1_048_576, (int) (getenv('MAX_PDF_BYTES') ?: 104_857_600)),
    'max_cover_bytes' => max(262_144, (int) (getenv('MAX_COVER_BYTES') ?: 15_728_640)),
];
