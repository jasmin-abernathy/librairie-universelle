<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/config/app.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Search.php';
require_once __DIR__ . '/WorkCatalog.php';

$pdo = Database::connect($config);

return [$config, $pdo];
