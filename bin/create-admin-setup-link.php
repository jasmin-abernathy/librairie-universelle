<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
[$config,$pdo] = require dirname(__DIR__).'/src/bootstrap.php';
try {
    $token=AdminAccounts::setupToken($pdo);
    echo "https://librairie.lepotager.org/admin/connexion.php?initialisation=".$token."\n";
    fwrite(STDERR, "Lien de création du premier compte : usage unique, validité 24 heures. Ne jamais le publier.\n");
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage()."\n");
    exit(1);
}
