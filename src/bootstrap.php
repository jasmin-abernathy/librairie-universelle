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
require_once __DIR__ . '/GallicaOpdsSource.php';
require_once __DIR__ . '/GallicaImporter.php';
require_once __DIR__ . '/Isbn.php';
require_once __DIR__ . '/OfferSource.php';
require_once __DIR__ . '/OfferImporter.php';
require_once __DIR__ . '/MoselleAvailabilityClient.php';
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/UploadValidator.php';
require_once __DIR__ . '/SelfPublishingService.php';
require_once __DIR__ . '/EbookStorefront.php';
require_once __DIR__ . '/FeedbackService.php';
require_once __DIR__ . '/AdminAuth.php';
require_once __DIR__ . '/SyncLog.php';

$pdo = Database::connect($config);

return [$config, $pdo];
