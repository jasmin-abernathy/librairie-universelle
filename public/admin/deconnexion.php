<?php
declare(strict_types=1);
[$config,$pdo] = require dirname(__DIR__,2).'/src/bootstrap.php';
header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Méthode non autorisée.');
}
if (!AdminAccounts::signedIn() || !Csrf::validate($_POST['csrf'] ?? null)) {
    http_response_code(403);
    exit('Déconnexion non autorisée.');
}
AdminAccounts::clearLogin();
session_regenerate_id(true);
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
header('Location: /admin/connexion.php',true,303);
exit;
