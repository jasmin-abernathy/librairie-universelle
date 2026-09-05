<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

try {
    [, $pdo] = require dirname(__DIR__) . '/src/bootstrap.php';
    $pdo->query('SELECT 1')->fetchColumn();
    http_response_code(200);
    echo json_encode(['status' => 'ok'], JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(503);
    echo json_encode(['status' => 'error'], JSON_UNESCAPED_SLASHES);
}
