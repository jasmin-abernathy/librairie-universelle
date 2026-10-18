<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/GitRevision.php';
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
$revision = GitRevision::read(dirname(__DIR__));
if ($revision === null) {
    http_response_code(503);
    echo "unknown\n";
    exit;
}
echo $revision . "\n";
