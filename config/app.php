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

$config = [
    'name' => getenv('APP_NAME') ?: 'Librairie universelle',
    'env' => getenv('APP_ENV') ?: 'production',
    'database_path' => getenv('DATABASE_PATH') ?: $root . '/data/app.sqlite',
    'schema_path' => $root . '/database/schema.sql',
    'seed_path' => $root . '/database/seed.sql',
    'storage_path' => getenv('STORAGE_PATH') ?: $root . '/storage',
    'self_publishing_enabled' => $boolEnv('SELF_PUBLISHING_ENABLED', false),
    'feedback_enabled' => $boolEnv('FEEDBACK_ENABLED', false),
    'federated_search_enabled' => $boolEnv('FEDERATED_SEARCH_ENABLED', true),
    'discovery_cache_ttl' => max(60, (int) (getenv('DISCOVERY_CACHE_TTL') ?: 1800)),
    'discovery_timeout_seconds' => max(2, min(8, (int) (getenv('DISCOVERY_TIMEOUT_SECONDS') ?: 5))),
    'admin_token' => getenv('ADMIN_TOKEN') ?: '',
    'offer_max_age_days' => max(1, (int) (getenv('OFFER_MAX_AGE_DAYS') ?: 7)),
    'max_epub_bytes' => max(1_048_576, (int) (getenv('MAX_EPUB_BYTES') ?: 52_428_800)),
    'max_pdf_bytes' => max(1_048_576, (int) (getenv('MAX_PDF_BYTES') ?: 104_857_600)),
    'max_cover_bytes' => max(262_144, (int) (getenv('MAX_COVER_BYTES') ?: 15_728_640)),
    'moselle_base_url' => rtrim((string) (getenv('MOSELLE_BASE_URL') ?: ''), '/'),
    'atelier_epub_url' => trim((string) (getenv('ATELIER_EPUB_URL') ?: '')),
    // Do not expose untrusted PDFs to Ghostscript without an explicit operator opt-in.
    'pdfx_enabled' => $boolEnv('PDFX_ENABLED', false),
    'pdfx_gs_bin' => (string) (getenv('PDFX_GS_BIN') ?: '/usr/bin/gs'),
    'pdfx_timeout_bin' => (string) (getenv('PDFX_TIMEOUT_BIN') ?: '/usr/bin/timeout'),
    'pdfx_icc_profile' => (string) (getenv('PDFX_ICC_PROFILE') ?: ''),
    'pdfx_output_condition' => (string) (getenv('PDFX_OUTPUT_CONDITION') ?: 'Custom CMYK - confirm with printer'),
    'pdfx_max_bytes' => min(26214400, max(1048576, (int) (getenv('PDFX_MAX_BYTES') ?: 26214400))),
];

$localConfigPath = getenv('APP_LOCAL_CONFIG') ?: $root . '/config/local.php';
if (is_file($localConfigPath)) {
    $localConfig = require $localConfigPath;
    if (!is_array($localConfig)) {
        throw new RuntimeException('Le fichier de configuration locale doit retourner un tableau PHP.');
    }
    $config = array_replace($config, $localConfig);
}

return $config;
