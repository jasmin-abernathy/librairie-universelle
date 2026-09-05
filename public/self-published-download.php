<?php

declare(strict_types=1);

[$config, $pdo] = require dirname(__DIR__) . '/src/bootstrap.php';
$service = new SelfPublishingService($pdo, $config);
$submissionId = isset($_GET['submission']) ? max(0, (int) $_GET['submission']) : 0;
$file = $submissionId > 0 ? $service->freeEbookPath($submissionId) : null;

if ($file === null || !is_file($file['path'])) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Ce téléchargement gratuit n’est pas disponible.\n";
    exit;
}

header('X-Content-Type-Options: nosniff');
header('Content-Type: application/epub+zip');
header('Content-Length: ' . (string) filesize($file['path']));
header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($file['original_name']));
readfile($file['path']);
