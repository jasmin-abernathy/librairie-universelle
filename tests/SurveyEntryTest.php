<?php
declare(strict_types=1);

// Test structurel sans collecte de données ni appels HTTP.
$root=dirname(__DIR__);
$read=static fn(string $path):string => (string)file_get_contents($root.'/'.$path);
$fail=static function(string $message):never {fwrite(STDERR,$message."\n");exit(1);};
$entry=$read('public/enquete.php');
$header=$read('src/views/site-header.php');
$sitemap=$read('public/sitemap.php');
$schema=$read('database/schema.sql');
$admin=$read('public/admin/index.php');
foreach (['/enquete-lecteurs.php','/enquete-libraires.php'] as $route) {
    if(!str_contains($entry,'href="'.$route))$fail('Parcours absent du choix : '.$route);
}
if(!str_contains($header,"'survey' => ['href' => '/enquete.php'"))$fail('Entrée enquête absente de la navigation');
if(!str_contains($sitemap,"'/enquete.php'"))$fail('Entrée enquête absente du sitemap');
if(!str_contains($admin,'/enquete-lecteurs.php?admin=1')||!str_contains($admin,'/enquete-libraires.php?admin=1'))$fail('Accès aux exports manquant');
$pdo=new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$pdo->exec($schema);
foreach (['reader_survey_sessions','bookseller_survey_sessions'] as $table) {
    $check=$pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
    $check->execute([$table]);
    if(!$check->fetchColumn())$fail('Table absente : '.$table);
    $pdo->exec("INSERT INTO ".$table." (id,token_hash,source) VALUES ('a','".str_repeat('f',64)."','direct')");
    $p=$pdo->query("SELECT answers_json,status FROM ".$table." WHERE id='a'")->fetch(PDO::FETCH_ASSOC);
    if($p['status']!=='active'||$p['answers_json']!=='{}')$fail('Défauts de la table incohérents : '.$table);
}
foreach ([['public/enquete-lecteurs.php','public/assets/js/reader-survey.js'],['public/enquete-libraires.php','public/assets/js/bookseller-survey.js']] as [$php,$js]) {
    $backend=$read($php);$frontend=$read($js);
    if(!str_contains($backend,'AdminAuth::enforce($config)'))$fail('Export non protégé : '.$php);
    if(!str_contains($backend,"hash('sha256',"))$fail('Identifiant de reprise non haché : '.$php);
    if(!str_contains($backend,"'erase'"))$fail('Suppression manquante : '.$php);
    if(!str_contains($backend,'completed_at'))$fail('Statut final manquant : '.$php);
    preg_match_all("/\\bid:'([a-z_]+)'/",$frontend,$f);
    preg_match_all("/'([a-z_]+)'\\s*=>\\s*\\['(?:one|many|text)'/",$backend,$b);
    $front=array_values(array_unique($f[1]));$back=array_values(array_unique($b[1]));
    sort($front);sort($back);
    if($front!==$back)$fail('Questions JS/PHP différentes : '.$php.' '.json_encode(array_diff($front,$back)).' '.json_encode(array_diff($back,$front)));
    if(count($front)<15)$fail('Enquête incomplète : '.$php);
    if(!str_contains($backend,'?admin=1')||!str_contains($backend,'export=json')||!str_contains($backend,'export=csv'))$fail('Export manquant : '.$php);
}
echo "Enquête 2026 : deux parcours cohérents, liens, schéma SQLite, validation, export et suppression OK.\n";
