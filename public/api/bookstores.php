<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

[, $pdo] = require dirname(__DIR__, 2) . '/src/bootstrap.php';

$statement = $pdo->query(<<<'SQL'
SELECT
    directory_key,
    name,
    group_name,
    address,
    postal_code,
    city,
    category,
    independence_status,
    specialization,
    phone,
    website_url,
    website_status,
    ordering_status,
    ordering_notes,
    source_label,
    source_url,
    last_checked
FROM bookstores
WHERE directory_status = 'listed'
ORDER BY city COLLATE NOCASE, name COLLATE NOCASE
SQL);

$bookstores = $statement->fetchAll();

echo json_encode([
    'count' => count($bookstores),
    'bookstores' => $bookstores,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
