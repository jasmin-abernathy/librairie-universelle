<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/config/app.php';

require_once __DIR__ . '/HttpClient.php';
require_once __DIR__ . '/DiscoverySource.php';
require_once __DIR__ . '/DiscoveryCache.php';
require_once __DIR__ . '/BnfDiscoverySource.php';
require_once __DIR__ . '/GallicaOpdsSource.php';
require_once __DIR__ . '/GallicaDiscoverySource.php';
require_once __DIR__ . '/WikisourceDiscoverySource.php';
require_once __DIR__ . '/OpenLibraryDiscoverySource.php';
require_once __DIR__ . '/DoabDiscoverySource.php';
require_once __DIR__ . '/StandardEbooksGithubSource.php';
require_once __DIR__ . '/OpdsDiscoverySource.php';
require_once __DIR__ . '/DiscoveryRegistry.php';

return $config;
