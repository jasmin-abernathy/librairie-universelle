<?php

declare(strict_types=1);

[$config, $pdo] = require dirname(__DIR__, 2) . '/src/bootstrap.php';
AdminAuth::enforce($config);
$service = new SelfPublishingService($pdo, $config);
$fileId = isset($_GET['id']) ? max(0, (int) $_GET['id']) : 0;
$file = $fileId > 0 ? $service->filePathForAdmin($fileId) : null;

if ($file === null || !is_file($file['path'])) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Fichier introuvable.\n";
    exit;
}

header('X-Content-Type-Options: nosniff');
header('Content-Type: ' . $file['mime_type']);
header('Content-Length: ' . (string) filesize($file['path']));
header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($file['original_name']));
readfile($file['path']);
