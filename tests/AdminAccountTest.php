<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/Database.php';
require dirname(__DIR__).'/src/Csrf.php';
require dirname(__DIR__).'/src/AdminAccounts.php';

$path = sys_get_temp_dir().'/admin-accounts-'.bin2hex(random_bytes(8)).'.sqlite';
$config = require dirname(__DIR__).'/config/app.php';
$config['database_path']=$path;
$config['seed_path']=null;
try {
    $pdo=Database::connect($config);
    if (AdminAccounts::exists($pdo)) throw new RuntimeException('Database must start without accounts.');
    $token=AdminAccounts::setupToken($pdo);
    if (strlen($token)!==64 || !AdminAccounts::tokenValid($pdo,$token)) throw new RuntimeException('Setup token unusable.');
    if (AdminAccounts::tokenValid($pdo,str_repeat('0',64))) throw new RuntimeException('Forged setup token accepted.');
    $blocked=false;
    try { AdminAccounts::createFirst($pdo,$token,'test@example.org','short'); }
    catch(InvalidArgumentException $e) {$blocked=true;}
    if (!$blocked) throw new RuntimeException('Weak password accepted.');
    $id=AdminAccounts::createFirst($pdo,$token,'test@example.org','very-strong-example-passphrase');
    if ($id<1 || !AdminAccounts::exists($pdo) || AdminAccounts::tokenValid($pdo,$token)) {
        throw new RuntimeException('One-use setup failed.');
    }
    $blocked=false;
    try { AdminAccounts::setupToken($pdo); } catch(RuntimeException $e) {$blocked=true;}
    if (!$blocked) throw new RuntimeException('Second setup allowed.');
    $_SERVER['REMOTE_ADDR']='127.0.0.1';
    if (AdminAccounts::attemptLogin($pdo,'test@example.org','wrong')!==null) {
        throw new RuntimeException('Invalid password accepted.');
    }
    for($i=0;$i<7;$i++) AdminAccounts::attemptLogin($pdo,'bad@example.org','wrong');
    if (AdminAccounts::attemptLogin($pdo,'bad@example.org','wrong')!==null) {
        throw new RuntimeException('Rate limiting failure.');
    }
    if (AdminAccounts::attemptLogin($pdo,'test@example.org','very-strong-example-passphrase')!==$id) {
        throw new RuntimeException('Valid password rejected.');
    }
    AdminAccounts::signIn($id);
    if (!AdminAccounts::signedIn()) throw new RuntimeException('Session not established.');
    AdminAccounts::clearLogin();
    if (AdminAccounts::signedIn()) throw new RuntimeException('Sign-out not enforced.');
    echo "AdminAccountTest OK\n";
} finally {
    $pdo=null;
    @unlink($path);
    @unlink($path.'-wal');
    @unlink($path.'-shm');
}
