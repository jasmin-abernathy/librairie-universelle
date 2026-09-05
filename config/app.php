<?php

declare(strict_types=1);

$root = dirname(__DIR__);

return [
    'name' => getenv('APP_NAME') ?: 'Librairie universelle',
    'env' => getenv('APP_ENV') ?: 'production',
    'database_path' => getenv('DATABASE_PATH') ?: $root . '/data/app.sqlite',
    'schema_path' => $root . '/database/schema.sql',
];
