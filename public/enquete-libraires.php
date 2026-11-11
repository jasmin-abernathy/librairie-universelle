<?php
declare(strict_types=1);

[$config, $pdo]=require dirname(__DIR__).'/src/bootstrap.php';
// Nettoyage opportuniste des réponses anciennes sur chaque accès à l’enquête.
$pdo->exec("DELETE FROM bookseller_survey_sessions WHERE updated_at < datetime('now', '-12 months')");

function shop_h(mixed $v):string {return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function shop_reply(int $status,array $payload):never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex');
    echo json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function shop_rules():array {
    return [
        'role'=>['one',['owner','manager','bookseller','other']],
        'location'=>['one',['rural','small','medium','city','unsure']],
        'staff'=>['one',['one','two_four','five_nine','ten_plus','prefer_not']],
        'audience'=>['many',['local','families','students','tourists','schools','online','other']],
        'challenges'=>['many',['charges','margin','cash','stocks','workload','visibility','logistics','competition','other']],
        'communication'=>['many',['word_of_mouth','website','newsletter','social','events','posters','local_press','none']],
        'stock_online'=>['one',['live','partial','no','unknown']],
        'online_order'=>['one',['own','shared','platform','none']],
        'services'=>['many',['reservation','delivery','advice','events','used','schools','ebooks','none']],
        'client_demands'=>['many',['availability','quick_pickup','online_buy','recommendations','used','events','accessibility','other']],
        'priority'=>['many',['stock','booking','training','communication','partnerships','culture','logistics','access','other']],
        'cooperation'=>['one',['active','interested','no','unknown']],
        'ebook_sales'=>['one',['yes','no','testing']],
        'ebook_future'=>['one',['important','possible','limited','unknown']],
        'paper_future'=>['one',['paper','digital','complementary','unknown']],
        'one_idea'=>['text',[]]
    ];
}
function shop_validate(mixed $input):array {
    if (!is_array($input)||array_is_list($input)||count($input)>20)throw new InvalidArgumentException('Réponses invalides.');
    $out=[];$rules=shop_rules();
    foreach($input as $key=>$value){
        if(!is_string($key)||!isset($rules[$key]))throw new InvalidArgumentException('Question inconnue.');
        [$type,$allowed]=$rules[$key];
        if($type==='text'){
            if(!is_string($value)||mb_strlen($value,'UTF-8')>400)throw new InvalidArgumentException('Texte trop long.');
            $out[$key]=trim(strip_tags($value));continue;
        }
        if($type==='one'){
            if(!is_string($value)||!in_array($value,$allowed,true))throw new InvalidArgumentException('Choix invalide.');
            $out[$key]=$value;continue;
        }
        if(!is_array($value)||count($value)>8||$value===[])throw new InvalidArgumentException('Sélection invalide.');
        $value=array_values(array_unique($value));
        foreach($value as $v){
            if(!is_string($v)||!in_array($v,$allowed,true))throw new InvalidArgumentException('Réponse inconnue.');
        }
        if(count($value)>8||(in_array($key,['challenges','priority'],true)&&count($value)>3))throw new InvalidArgumentException('Trop de réponses sélectionnées.');
        if(count($value)>1&&in_array('none',$value,true))throw new InvalidArgumentException('« Aucun » doit être sélectionné seul.');
        $out[$key]=$value;
    }
    return $out;
}
function shop_lookup(PDO $pdo,string $token):?array {
    if(!preg_match('/^[a-f0-9]{64}$/D',$token))return null;
    $st=$pdo->prepare('SELECT id,status,answers_json,created_at FROM bookseller_survey_sessions WHERE token_hash=? LIMIT 1');
    $st->execute([hash('sha256',$token)]);
    return $st->fetch(PDO::FETCH_ASSOC)?:null;
}
function shop_required(array $answers):bool {
    foreach(array_keys(shop_rules()) as $field){
        if($field==='one_idea')continue;
        if(!array_key_exists($field,$answers))return false;
    }
    return true;
}
if(isset($_GET['admin'])){
    AdminAuth::enforce($config);
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex');
    $rows=$pdo->query("SELECT source,answers_json,created_at,completed_at FROM bookseller_survey_sessions WHERE status='completed' ORDER BY completed_at")->fetchAll(PDO::FETCH_ASSOC);
    $exp=(string)($_GET['export']??'');
    if($exp==='json'){
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="enquete-libraires-2026.json"');
        echo json_encode(array_map(static fn($r)=>['source'=>$r['source'],'started_at'=>$r['created_at'],'completed_at'=>$r['completed_at'],'answers'=>json_decode($r['answers_json'],true)],$rows),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
    if($exp==='csv'){
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="enquete-libraires-2026.csv"');
        $out=fopen('php://output','wb');$headers=['source','started_at','completed_at',...array_keys(shop_rules())];
        fputcsv($out,$headers,';','"','');
        foreach($rows as $r){
            $a=json_decode($r['answers_json'],true)??[];
            $line=[$r['source'],$r['created_at'],$r['completed_at']];
            foreach(array_keys(shop_rules()) as $k){
                $v=$a[$k]??'';$v=is_array($v)?implode(' | ',$v):(string)$v;
                if(preg_match('/^\s*[=+\-@]/u',$v))$v="'".$v;
                $line[]=$v;
            }
            fputcsv($out,$line,';','"','');
        }
        fclose($out);exit;
    }
    $drafts=(int)$pdo->query("SELECT COUNT(*) FROM bookseller_survey_sessions WHERE status='active'")->fetchColumn();
    ?><!doctype html><html lang="fr"><head><meta charset="utf-8"><link rel="icon" href="/favicon.svg" type="image/svg+xml"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Administration enquête libraires</title><link rel="stylesheet" href="/assets/css/reader-survey.css?v=20261111-1"></head><body class="reader-survey"><main class="survey-shell"><section class="survey-card"><p class="survey-kicker">Administration protégée</p><h1>Enquête libraires · 2026</h1><p><?= count($rows) ?> contributions terminées · <?= $drafts ?> brouillons.</p><div class="survey-actions"><a class="survey-button" href="?admin=1&amp;export=json">Exporter JSON</a><a class="survey-button secondary" href="?admin=1&amp;export=csv">Exporter CSV</a><a class="survey-button secondary" href="/enquete.php">Retour aux enquêtes</a></div><p class="survey-help">Aucune identité de librairie ni identifiant de reprise dans les exports.</p></section></main></body></html><?php
    exit;
}

if(isset($_GET['api'])){
    if($_SERVER['REQUEST_METHOD']!=='POST')shop_reply(405,['error'=>'Méthode non autorisée.']);
    if(!str_starts_with(strtolower((string)($_SERVER['CONTENT_TYPE']??'')),'application/json'))shop_reply(415,['error'=>'Format JSON requis.']);
    $origin=(string)($_SERVER['HTTP_ORIGIN']??'');
    if($origin!==''&&strcasecmp((string)parse_url($origin,PHP_URL_HOST),(string)($_SERVER['HTTP_HOST']??''))!==0)shop_reply(403,['error'=>'Origine non autorisée.']);
    $raw=file_get_contents('php://input',false,null,0,18001);
    if($raw===false||strlen($raw)>18000)shop_reply(413,['error'=>'Requête trop longue.']);
    $payload=json_decode($raw,true);
    if(!is_array($payload))shop_reply(400,['error'=>'JSON invalide.']);
    $action=(string)$_GET['api'];
    if($action==='start'){
        if(($payload['consent']??false)!==true||!empty($payload['website']))shop_reply(400,['error'=>'Merci de confirmer votre participation.']);
        $src=(string)($payload['source']??'direct');
        if(!in_array($src,['direct','linkedin','librairie','mediatheque','association','reddit','autre'],true))$src='direct';
        $id=bin2hex(random_bytes(16));$token=bin2hex(random_bytes(32));
        $q=$pdo->prepare('INSERT INTO bookseller_survey_sessions(id,token_hash,source) VALUES(?,?,?)');
        $q->execute([$id,hash('sha256',$token),$src]);
        shop_reply(200,['token'=>$token,'status'=>'active','answers'=>new stdClass()]);
    }
    $token=(string)($payload['token']??'');
    $row=shop_lookup($pdo,$token);
    if(!$row)shop_reply(404,['error'=>'Lien de reprise introuvable.']);
    if($action==='resume')shop_reply(200,['status'=>$row['status'],'answers'=>json_decode($row['answers_json'],true)??[]]);
    if($action==='erase'){
        $q=$pdo->prepare('DELETE FROM bookseller_survey_sessions WHERE id=?');$q->execute([$row['id']]);
        shop_reply(200,['deleted'=>true]);
    }
    if(!in_array($action,['save','finish'],true))shop_reply(404,['error'=>'Action inconnue.']);
    if($row['status']!=='active')shop_reply(409,['error'=>'Contribution déjà terminée.']);
    try{$answers=shop_validate($payload['answers']??null);}catch(InvalidArgumentException $e){shop_reply(422,['error'=>$e->getMessage()]);}
    if($action==='finish'){
        if(!shop_required($answers))shop_reply(422,['error'=>'Certaines questions obligatoires sont sans réponse.']);
        if(strtotime((string)$row['created_at'])>time()-15)shop_reply(429,['error'=>'Merci de parcourir le questionnaire avant de l’envoyer.']);
    }
    $encoded=json_encode($answers,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
    $q=$pdo->prepare($action==='finish'
       ?"UPDATE bookseller_survey_sessions SET answers_json=?,status='completed',completed_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=? AND status='active'"
       :"UPDATE bookseller_survey_sessions SET answers_json=?,updated_at=CURRENT_TIMESTAMP WHERE id=? AND status='active'");
    $q->execute([$encoded,$row['id']]);
    shop_reply(200,['status'=>$action==='finish'?'completed':'active','saved'=>true]);
}
header('Cache-Control: no-store');
$source=(string)($_GET['src']??'direct');
if(!in_array($source,['direct','linkedin','librairie','mediatheque','association','reddit','autre'],true))$source='direct';
?>
<!doctype html><html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,follow">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/css/app.css?v=20261104-nav2">
<link rel="stylesheet" href="/assets/css/reader-survey.css?v=20261111-1">
<script src="/assets/js/bookseller-survey.js?v=20261111-1" defer></script>
<title>Questionnaire libraires — étude 2026</title>
<meta name="description" content="Enquête indépendante sur les pratiques commerciales et les besoins des librairies en 2026.">
</head>
<body class="reader-survey" data-source="<?= shop_h($source) ?>">
<a href="#main" class="skip-link">Aller au questionnaire</a>
<?php $currentNav='survey';require dirname(__DIR__).'/src/views/site-header.php';?>
<main class="survey-shell" id="main">
<a class="survey-back" href="/enquete.php">← Changer de questionnaire</a>
<section class="survey-card" id="survey-intro">
<p class="survey-kicker">Enquête indépendante · Professionnels du livre</p>
<h1>Quelles solutions sont vraiment utiles aux librairies ?</h1>
<p class="survey-lead">Je prolonge une étude menée en 2014 sur les librairies indépendantes. Cette nouvelle enquête porte sur vos clients, votre activité quotidienne, vos outils et les solutions qui pourraient vous aider.</p>
<p><strong>Environ 5 à 7 minutes.</strong> Le questionnaire s’adresse aux personnes qui exercent ou dirigent une librairie. Il ne commence volontairement pas par le livre numérique : tous les modes de vente sont concernés.</p>
<div class="survey-privacy"><h2>Votre confidentialité</h2>
<p>Le formulaire ne demande ni le nom de la librairie, ni son chiffre d’affaires exact, ni une adresse. Les réponses seront étudiées sous forme agrégée et utilisées dans l’étude 2026. Vous pouvez interrompre, reprendre et supprimer votre contribution grâce à un code conservé localement dans votre navigateur. Aucun compte ni courriel n’est nécessaire.</p>
<p>Ce site ne crée pas de profil publicitaire. Des journaux techniques de l’hébergement peuvent toutefois contenir des informations de connexion. Contact : <a href="mailto:jleveque@lepotager.org">jleveque@lepotager.org</a>.</p></div>
<label class="survey-consent"><input id="survey-consent" type="checkbox"> Je participe librement à cette étude selon les conditions présentées ci-dessus.</label>
<label class="survey-honeypot" aria-hidden="true">Site personnel <input id="survey-website" type="text" tabindex="-1" autocomplete="off"></label>
<p class="survey-error" id="survey-intro-error" role="alert" hidden></p>
<div class="survey-actions"><button id="survey-start" type="button" class="survey-button">Commencer</button><button id="survey-resume" type="button" class="survey-button secondary" hidden>Reprendre ma réponse</button></div>
</section>
<section class="survey-card" id="survey-questions" hidden>
<div class="survey-meta"><span id="survey-step-text">Question 1</span><span id="survey-save-status" role="status">Progression sauvegardée</span></div>
<progress id="survey-progress" max="100" value="0">0 %</progress>
<div id="survey-question-content" aria-live="polite"></div>
<p id="survey-question-error" class="survey-error" role="alert" hidden></p>
<div class="survey-actions"><button id="survey-prev" type="button" class="survey-button secondary">Précédent</button><button id="survey-next" type="button" class="survey-button">Continuer</button></div>
<button class="survey-link" id="survey-erase" type="button">Effacer ma participation</button>
</section>
<section class="survey-card" id="survey-complete" hidden>
<p class="survey-kicker">Contribution enregistrée</p><h1>Merci pour votre témoignage !</h1>
<p>Vos réponses contribueront à des préconisations marketing fondées sur les contraintes des librairies, plutôt que sur des solutions imposées de l’extérieur.</p>
<button class="survey-button secondary" type="button" id="survey-delete-completed">Supprimer ma réponse</button>
<a class="survey-button secondary" href="/enquete.php">Retour à l’enquête</a>
</section>
</main>
<footer class="survey-footer"><a href="/enquete.php">Les deux questionnaires</a> · <a href="/">Librairie universelle</a> · Étude 2026</footer>
</body></html>
