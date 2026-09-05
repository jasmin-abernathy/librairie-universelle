<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/config/app.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Search.php';
require_once __DIR__ . '/WorkCatalog.php';
require_once __DIR__ . '/CatalogSource.php';
require_once __DIR__ . '/HttpClient.php';
require_once __DIR__ . '/BnfSruSource.php';
require_once __DIR__ . '/CatalogImporter.php';

$pdo = Database::connect($config);

return [$config, $pdo];
