<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/Database.php';
require dirname(__DIR__).'/src/Interview.php';
require dirname(__DIR__).'/src/Csrf.php';

$path=sys_get_temp_dir().'/interview-test-'.bin2hex(random_bytes(8)).'.sqlite';
$config=require dirname(__DIR__).'/config/app.php';
$config['database_path']=$path;
$config['seed_path']=null;
$config['admin_token']=str_repeat('test-secret-',8);
try {
    $pdo=Database::connect($config);
    $one=Interview::ensureCecileDraft($pdo);
    $two=Interview::ensureCecileDraft($pdo);
    if ($one['id']!==$two['id'] || count(Interview::questions())!==16) throw new RuntimeException('Brouillon non idempotent ou questions perdues.');
    if (Interview::canOpen($one,$config,str_repeat('0',64))) throw new RuntimeException('Un brouillon ne doit pas être accessible.');
    $pdo->exec("UPDATE editorial_interviews SET status='invited' WHERE id='cecile-bory-saillant-2026'");
    $row=Interview::find($pdo, Interview::CECILE_ID);
    parse_str((string)parse_url(Interview::link($row,$config),PHP_URL_QUERY),$params);
    if (!Interview::canOpen($row,$config,$params['k']??'')) throw new RuntimeException('Lien actif rejeté.');
    if (Interview::canOpen($row,$config,str_repeat('0',64))) throw new RuntimeException('Faux lien accepté.');
    if (Interview::sessionAuthorized($row)) throw new RuntimeException('Lien seul a autorisé les réponses.');
    // Simuler la réception d'un OTP sans envoyer de vrai courriel depuis les tests.
    $code='684231';
    $hash=hash_hmac('sha256',$row['id'].':'.$row['token_version'].':'.$code,$config['admin_token']);
    $otp=$pdo->prepare("UPDATE editorial_interviews SET otp_hash=?,otp_expires_at=? WHERE id=?");
    $otp->execute([$hash,time()+600,$row['id']]);
    $row=Interview::find($pdo,Interview::CECILE_ID);
    if (Interview::verifyCode($pdo,$row,$config,'000000')) throw new RuntimeException('Mauvais code accepté.');
    $row=Interview::find($pdo,Interview::CECILE_ID);
    if (!Interview::verifyCode($pdo,$row,$config,$code)) throw new RuntimeException('Code valide rejeté.');
    $row=Interview::find($pdo,Interview::CECILE_ID);
    if (!Interview::sessionAuthorized($row)) throw new RuntimeException('Session après validation OTP indisponible.');
    $blocked=false;
    try { Interview::saveAnswer($pdo,$row,0,'','next'); }
    catch(RuntimeException $e){$blocked=true;}
    if (!$blocked) throw new RuntimeException('Présentation vide acceptée.');
    Interview::saveAnswer($pdo,$row,0,'Cécile Bory-Saillant','next');
    $row=Interview::find($pdo,Interview::CECILE_ID);
    if ((int)$row['cursor']!==1 || Interview::answers($row)['0']!=='Cécile Bory-Saillant') throw new RuntimeException('Sauvegarde échouée.');
    Interview::saveAnswer($pdo,$row,1,'Librairie Georges','next');
    $row=Interview::find($pdo,Interview::CECILE_ID);
    Interview::saveAnswer($pdo,$row,2,'Une réponse libre','back');
    $row=Interview::find($pdo,Interview::CECILE_ID);
    if ((int)$row['cursor']!==1 || Interview::answers($row)['2']!=='Une réponse libre') throw new RuntimeException('Retour/sauvegarde échoué.');
    $pdo->exec("UPDATE editorial_interviews SET status='closed', token_version=token_version+1 WHERE id='cecile-bory-saillant-2026'");
    $row=Interview::find($pdo,Interview::CECILE_ID);
    if (Interview::canOpen($row,$config,$params['k']??'')) throw new RuntimeException('Ancien lien encore actif.');
    if (Interview::sessionAuthorized($row)) throw new RuntimeException('Session non révoquée.');
    echo "InterviewTest OK\n";
} finally {
    $pdo=null;
    @unlink($path);
    @unlink($path.'-wal');
    @unlink($path.'-shm');
}
