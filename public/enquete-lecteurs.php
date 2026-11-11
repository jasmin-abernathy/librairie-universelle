<?php
declare(strict_types=1);

[$config, $pdo] = require dirname(__DIR__) . '/src/bootstrap.php';
// Nettoyage opportuniste des réponses anciennes sur chaque accès à l’enquête.
$pdo->exec("DELETE FROM reader_survey_sessions WHERE updated_at < datetime('now', '-12 months')");

function reader_h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function reader_json(int $status, array $body): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex');
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function reader_rules(): array {
    return [
        'age_band'=>['one',['18_24','25_34','35_49','50_64','65_75','76_plus']],
        'territory'=>['one',['rural','small','medium','large','unknown']],
        'read_frequency'=>['one',['daily','weekly','monthly','rarely','never']],
        'formats'=>['many',['paper','ebook','audio','none']],
        'buy_volume'=>['one',['zero','one_two','three_five','six_ten','eleven_twenty','over_twenty']],
        'channels'=>['many',['indie','cultural_chain','chain_online','amazon','supermarket','used_online','author','other','none']],
        'main_channel'=>['one',['indie','cultural_chain','chain_online','amazon','supermarket','used_online','author','other','none']],
        'ebook_owned'=>['one',['yes','no']],
        'ebook_devices'=>['many',['ereader','phone','tablet','laptop','desktop','none']],
        'ebook_advantages'=>['many',['portable','quick','cheap','rare_titles','search','accessibility','none']],
        'ebook_disadvantages'=>['many',['physical','fatigue','cost','drm','catalog','none']],
        'used_books'=>['one',['often','sometimes','never']],
        'bookshop_visits'=>['one',['monthly','some','yearly','never']],
        'discovery'=>['many',['bookseller','relatives','social','traditional_media','algorithm','author','library','other']],
        'barriers'=>['many',['distance','hours','stock','price','ordering','unaware','none','other']],
        'services'=>['many',['stock','reservation','delivery','advice','events','used','ebooks','accessibility','none']],
        'waiting'=>['one',['same_day','two_days','one_week','depends','never']],
        'idea'=>['text',[]],
    ];
}
function reader_clean_answers(mixed $value): array {
    if (!is_array($value) || array_is_list($value) || count($value)>24) {
        throw new InvalidArgumentException('Réponses invalides.');
    }
    $rules=reader_rules();
    $clean=[];
    foreach ($value as $key=>$answer) {
        if (!is_string($key) || !isset($rules[$key])) {
            throw new InvalidArgumentException('Question inconnue.');
        }
        [$type,$choices]=$rules[$key];
        if ($type==='text') {
            if (!is_string($answer) || mb_strlen($answer,'UTF-8')>400) {
                throw new InvalidArgumentException('Réponse libre trop longue.');
            }
            $clean[$key]=trim(strip_tags($answer));
            continue;
        }
        if ($type==='one') {
            if (!is_string($answer) || !in_array($answer,$choices,true)) {
                throw new InvalidArgumentException('Choix invalide.');
            }
            $clean[$key]=$answer;
            continue;
        }
        if (!is_array($answer) || count($answer)>8 || $answer===[]) {
            throw new InvalidArgumentException('Sélection invalide.');
        }
        $answer=array_values(array_unique($answer));
        foreach ($answer as $item) {
            if (!is_string($item) || !in_array($item,$choices,true)) {
                throw new InvalidArgumentException('Sélection inconnue.');
            }
        }
        if (count($answer)>8 || ($key==='services' && count($answer)>3)) {
            throw new InvalidArgumentException('Trop de choix sélectionnés.');
        }
        if (count($answer)>1 && in_array('none',$answer,true)) {
            throw new InvalidArgumentException('« Aucun » doit être sélectionné seul.');
        }
        $clean[$key]=$answer;
    }
    return $clean;
}
function reader_find(PDO $pdo,string $token): ?array {
    if (!preg_match('/^[a-f0-9]{64}$/D',$token)) return null;
    $q=$pdo->prepare('SELECT id,status,answers_json,created_at FROM reader_survey_sessions WHERE token_hash=? LIMIT 1');
    $q->execute([hash('sha256',$token)]);
    return $q->fetch(PDO::FETCH_ASSOC) ?: null;
}
function reader_required_ok(array $answers): bool {
    $required=['age_band','territory','read_frequency','formats','buy_volume','channels','main_channel','ebook_owned','ebook_advantages','ebook_disadvantages','used_books','bookshop_visits','discovery','barriers','services','waiting'];
    foreach ($required as $field) if (!array_key_exists($field,$answers)) return false;
    if ($answers['ebook_owned']==='yes' && !array_key_exists('ebook_devices',$answers)) return false;
    if ($answers['channels']===['none'] && $answers['main_channel']!=='none') return false;
    return true;
}

if (isset($_GET['admin'])) {
    AdminAuth::enforce($config);
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex');
    $rows=$pdo->query("SELECT source,answers_json,created_at,completed_at FROM reader_survey_sessions WHERE status='completed' ORDER BY completed_at ASC")->fetchAll(PDO::FETCH_ASSOC);
    $export=(string)($_GET['export']??'');
    if ($export==='json') {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="enquete-lecteurs-2026.json"');
        echo json_encode(array_map(static fn(array $r): array => ['source'=>$r['source'],'started_at'=>$r['created_at'],'completed_at'=>$r['completed_at'],'answers'=>json_decode($r['answers_json'],true)],$rows),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
    if ($export==='csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="enquete-lecteurs-2026.csv"');
        $stream=fopen('php://output','wb');
        $headers=['source','started_at','completed_at',...array_keys(reader_rules())];
        fputcsv($stream,$headers,';', '"','');
        foreach($rows as $r) {
            $a=json_decode($r['answers_json'],true)??[];
            $line=[$r['source'],$r['created_at'],$r['completed_at']];
            foreach(array_keys(reader_rules()) as $k) {
                $v=$a[$k]??'';
                $v=is_array($v)?implode(' | ',$v):(string)$v;
                // Prevent formulas when opening a free-text answer in spreadsheet software.
                if (preg_match('/^\s*[=+\-@]/u',$v)) $v="'".$v;
                $line[]=$v;
            }
            fputcsv($stream,$line,';', '"','');
        }
        fclose($stream);
        exit;
    }
    $active=(int)$pdo->query("SELECT COUNT(*) FROM reader_survey_sessions WHERE status='active'")->fetchColumn();
    ?><!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,follow"><title>Enquête lecteurs — administration</title><link rel="stylesheet" href="/assets/css/reader-survey.css?v=20261111-1"></head><body class="reader-survey"><main class="survey-shell"><section class="survey-card"><p class="survey-kicker">Administration protégée</p><h1>Enquête lecteurs · 2026</h1><p><?= count($rows) ?> réponses terminées, <?= $active ?> brouillons.</p><div class="survey-actions"><a class="survey-button" href="?admin=1&amp;export=json">Exporter JSON</a><a class="survey-button secondary" href="?admin=1&amp;export=csv">Exporter CSV</a><a class="survey-button secondary" href="/">Accueil</a></div><p class="survey-help">Les exports excluent l’identifiant de reprise et les réponses inachevées.</p></section></main></body></html><?php
    exit;
}

if (isset($_GET['api'])) {
    if ($_SERVER['REQUEST_METHOD']!=='POST') reader_json(405,['error'=>'Méthode non autorisée.']);
    if (!str_starts_with(strtolower((string)($_SERVER['CONTENT_TYPE']??'')),'application/json')) reader_json(415,['error'=>'Format JSON requis.']);
    $origin=(string)($_SERVER['HTTP_ORIGIN']??'');
    if ($origin!=='' && strcasecmp((string)parse_url($origin,PHP_URL_HOST),(string)($_SERVER['HTTP_HOST']??''))!==0) {
        reader_json(403,['error'=>'Origine non autorisée.']);
    }
    $raw=file_get_contents('php://input',false,null,0,18001);
    if ($raw===false || strlen($raw)>18000) reader_json(413,['error'=>'Requête trop longue.']);
    $input=json_decode($raw,true);
    if (!is_array($input)) reader_json(400,['error'=>'JSON invalide.']);
    $action=(string)$_GET['api'];
    if ($action==='start') {
        if (($input['consent']??false)!==true || ($input['adult']??false)!==true || !empty($input['website'])) {
            reader_json(400,['error'=>'Confirmez votre accord et votre majorité.']);
        }
        $source=(string)($input['source']??'direct');
        if (!in_array($source,['direct','linkedin','librairie','mediatheque','association','reddit','autre'],true)) $source='direct';
        $token=bin2hex(random_bytes(32));
        $id=bin2hex(random_bytes(16));
        $stmt=$pdo->prepare("INSERT INTO reader_survey_sessions(id,token_hash,source) VALUES(?,?,?)");
        $stmt->execute([$id,hash('sha256',$token),$source]);
        reader_json(200,['token'=>$token,'status'=>'active','answers'=>new stdClass()]);
    }
    $token=(string)($input['token']??'');
    $row=reader_find($pdo,$token);
    if (!$row) reader_json(404,['error'=>'Lien de reprise introuvable ou expiré.']);
    if ($action==='resume') reader_json(200,['status'=>$row['status'],'answers'=>json_decode((string)$row['answers_json'],true)??[]]);
    if ($action==='erase') {
        $stmt=$pdo->prepare('DELETE FROM reader_survey_sessions WHERE id=?');
        $stmt->execute([$row['id']]);
        reader_json(200,['deleted'=>true]);
    }
    if (!in_array($action,['save','finish'],true)) reader_json(404,['error'=>'Action inconnue.']);
    if ($row['status']!=='active') reader_json(409,['error'=>'Cette participation est déjà terminée.']);
    try {
        $answers=reader_clean_answers($input['answers']??null);
    } catch (InvalidArgumentException $e) {
        reader_json(422,['error'=>$e->getMessage()]);
    }
    if ($action==='finish') {
        if (!reader_required_ok($answers)) reader_json(422,['error'=>'Certaines questions obligatoires sont sans réponse.']);
        if (strtotime((string)$row['created_at'])>time()-15) reader_json(429,['error'=>'Merci de prendre le temps de parcourir le questionnaire.']);
    }
    $json=json_encode($answers,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
    $stmt=$pdo->prepare($action==='finish'
        ?"UPDATE reader_survey_sessions SET answers_json=?,status='completed',completed_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=? AND status='active'"
        :"UPDATE reader_survey_sessions SET answers_json=?,updated_at=CURRENT_TIMESTAMP WHERE id=? AND status='active'");
    $stmt->execute([$json,$row['id']]);
    reader_json(200,['status'=>$action==='finish'?'completed':'active','saved'=>true]);
}

header('Cache-Control: no-store');
$source=(string)($_GET['src']??'direct');
if (!in_array($source,['direct','linkedin','librairie','mediatheque','association','reddit','autre'],true)) $source='direct';
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="canonical" href="https://librairie.lepotager.org/enquete-lecteurs.php">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<meta name="description" content="Enquête indépendante 2026 sur la lecture et les achats de livres, pour prolonger un mémoire de 2014. Réponses sans compte et sans publicité.">
<meta name="theme-color" content="#f7f3ed">
<title>Enquête lecteurs 2026 — Librairie universelle</title>
<link rel="stylesheet" href="/assets/css/app.css?v=20261104-nav2">
<link rel="stylesheet" href="/assets/css/reader-survey.css?v=20261111-1">
<script src="/assets/js/reader-survey.js?v=20261111-1" defer></script>
</head>
<body class="reader-survey" data-source="<?= reader_h($source) ?>">
<a class="skip-link" href="#main">Aller au questionnaire</a>
<?php $currentNav='survey'; require dirname(__DIR__).'/src/views/site-header.php'; ?>
<main id="main" class="survey-shell">
<a class="survey-back" href="/enquete.php">← Changer de questionnaire</a>
<section class="survey-card" id="survey-intro">
<p class="survey-kicker">Enquête indépendante · 2014–2026</p>
<h1>Où, comment et pourquoi achetez-vous vos livres ?</h1>
<p class="survey-lead">Douze ans après une première enquête de fin d’études, je m’intéresse aux habitudes de lecture et à la place des librairies indépendantes. Vos réponses aideront à formuler des recommandations concrètes.</p>
<p><strong>Environ 5 à 7 minutes.</strong> Il n’est pas nécessaire d’acheter en librairie pour répondre : les réponses des personnes qui achètent exclusivement ailleurs sont particulièrement utiles.</p>
<ul class="survey-facts"><li>Sans compte, sans adresse e-mail et sans publicité.</li><li>Questions comparables à celles de 2014 et nouvelles questions sur les services attendus.</li><li>Progression enregistrée : vous pouvez revenir terminer plus tard depuis ce navigateur.</li></ul>
<div class="survey-privacy"><h2>Vos données</h2><p>Cette enquête recueille votre tranche d’âge, vos pratiques déclarées et, si vous le souhaitez, une courte remarque libre. Aucun nom, courriel ou adresse précise n’est demandé. Un code aléatoire enregistré sur votre appareil permet de reprendre ou supprimer votre contribution. Les résultats seront analysés de manière agrégée pour la seconde édition de l’étude. Les réponses vieilles de plus de douze mois sont purgées lors des accès à l’enquête ; vous pouvez aussi supprimer votre contribution depuis ce navigateur.</p><p>La participation est facultative. Vous pouvez interrompre ou effacer votre réponse depuis le questionnaire. Responsable : Jasmin Lévêque — <a href="mailto:jleveque@lepotager.org">jleveque@lepotager.org</a>.</p></div>
<label class="survey-consent"><input id="survey-adult" type="checkbox"> J’ai 18 ans ou plus.</label>
<label class="survey-consent"><input id="survey-consent" type="checkbox"> J’accepte de participer à cette enquête selon les conditions présentées ci-dessus.</label>
<label class="survey-honeypot" aria-hidden="true">Site personnel <input id="survey-website" type="text" tabindex="-1" autocomplete="off"></label>
<p id="survey-intro-error" class="survey-error" role="alert" hidden></p>
<div class="survey-actions"><button type="button" id="survey-start" class="survey-button">Commencer l’enquête</button><button type="button" id="survey-resume" class="survey-button secondary" hidden>Reprendre ma réponse</button></div>
</section>
<section class="survey-card" id="survey-questions" hidden>
<div class="survey-meta"><span id="survey-step-text">Question 1</span><span id="survey-save-status" role="status">Réponses enregistrées progressivement</span></div>
<progress id="survey-progress" max="100" value="0">0 %</progress>
<div id="survey-question-content" aria-live="polite"></div>
<p class="survey-error" id="survey-question-error" role="alert" hidden></p>
<div class="survey-actions"><button class="survey-button secondary" id="survey-prev" type="button">Précédent</button><button class="survey-button" id="survey-next" type="button">Continuer</button></div>
<button class="survey-link" id="survey-erase" type="button">Effacer ma participation</button>
</section>
<section class="survey-card" id="survey-complete" hidden>
<p class="survey-kicker">Contribution enregistrée</p><h1>Merci d’avoir participé !</h1>
<p>Votre réponse rejoint l’enquête 2026. Les résultats agrégés seront utilisés pour comparer les habitudes de lecture et mieux comprendre ce qui peut aider les librairies indépendantes.</p>
<p class="survey-help">Votre code de reprise reste dans ce navigateur si vous souhaitez demander la suppression de cette contribution.</p>
<button class="survey-button secondary" id="survey-delete-completed" type="button">Supprimer ma réponse</button>
<a class="survey-button secondary" href="/">Revenir à la librairie</a>
</section>
</main>
<footer class="survey-footer"><a href="/">Librairie universelle</a> · Étude indépendante sur les circuits du livre · <a href="https://lobsoco.com/les-francais-face-aux-librairies-independantes/" target="_blank" rel="noopener noreferrer">Étude de référence SLF–ObSoCo 2026</a></footer>
</body>
</html>
