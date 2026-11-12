<?php
declare(strict_types=1);

[$config, $pdo] = require dirname(__DIR__,2).'/src/bootstrap.php';
AdminAuth::enforce($config);
require_once dirname(__DIR__,2).'/src/Interview.php';
header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');

function ei_e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
$row = Interview::ensureCecileDraft($pdo);
$error = '';
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!Csrf::validate($_POST['csrf'] ?? null)) throw new RuntimeException('Session expirée : rechargez la page.');
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'save_address' && $row['status'] === 'draft') {
            $email = trim((string)($_POST['email'] ?? ''));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email)>254) {
                throw new RuntimeException('Adresse e-mail invalide.');
            }
            $stmt=$pdo->prepare("UPDATE editorial_interviews SET recipient_email=?,updated_at=CURRENT_TIMESTAMP WHERE id=? AND status='draft'");
            $stmt->execute([$email,$row['id']]);
            $notice='Adresse enregistrée. Aucun e-mail envoyé.';
        } elseif ($action === 'activate' && $row['status'] === 'draft') {
            $stmt=$pdo->prepare("UPDATE editorial_interviews SET status='invited',updated_at=CURRENT_TIMESTAMP WHERE id=? AND status='draft'");
            $stmt->execute([$row['id']]);
            $notice='Lien individuel activé. L’invitation n’a pas encore été envoyée.';
        } elseif ($action === 'close' && in_array($row['status'], ['invited','in_progress','submitted'], true)) {
            $stmt=$pdo->prepare("UPDATE editorial_interviews SET status='closed',token_version=token_version+1,updated_at=CURRENT_TIMESTAMP WHERE id=?");
            $stmt->execute([$row['id']]);
            $notice='Lien révoqué. Les réponses restent archivées dans la base privée.';
        } elseif ($action === 'reopen' && $row['status'] === 'closed') {
            $target=(int)$row['cursor']===count(Interview::questions()) ? 'in_progress' : 'invited';
            $stmt=$pdo->prepare('UPDATE editorial_interviews SET status=?,updated_at=CURRENT_TIMESTAMP WHERE id=? AND status=?');
            $stmt->execute([$target,$row['id'],'closed']);
            $notice='Nouveau lien activé. L’ancien lien reste invalide.';
        } else {
            throw new RuntimeException('Action non permise dans cet état.');
        }
        $row=Interview::find($pdo,Interview::CECILE_ID) ?? $row;
    } catch (RuntimeException $exception) {
        $error=$exception->getMessage();
    }
}
if (isset($_GET['export']) && $_GET['export']==='json') {
    $payload=[
        'title'=>$row['title'],
        'respondent'=>$row['recipient_name'],
        'status'=>$row['status'],
        'submitted_at'=>$row['submitted_at'],
        'questions'=>array_map(static fn(array $q,int $i): array=>[
            'theme'=>$q['theme'], 'question'=>$q['question'],
            'response'=>Interview::answers($row)[(string)$i]??''
        ],Interview::questions(),array_keys(Interview::questions()))
    ];
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="entretien-cecile-bory-saillant-2026.json"');
    echo json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    exit;
}
$csrf=Csrf::token();
$active=in_array($row['status'], ['invited','in_progress'],true);
$submitted=$row['status']==='submitted';
$link=($active || $submitted) ? Interview::link($row,$config) : '';
$mailSubject='Entretien écrit — Librairie Georges et le numérique, douze ans après';
$mailBody="Bonjour Madame Bory-Saillant,\n\nNous avions échangé en 2013-2014 à l’occasion de mon mémoire sur les librairies indépendantes et le numérique. J’en prépare une édition revue en 2026 et souhaiterais recueillir votre regard sur ces douze années.\n\nJ’ai préparé un entretien écrit, organisé par thèmes, auquel vous pouvez répondre à votre rythme via ce lien privé :\n".$link."\n\nAucune réponse ne sera publiée sans relecture et validation de votre part.\n\nMerci beaucoup pour votre temps,\nJasmin Lévêque\nLe Potager du Web — Librairie universelle";
$mailto='mailto:'.$row['recipient_email'].'?subject='.rawurlencode($mailSubject).'&body='.rawurlencode($mailBody);
$answers=Interview::answers($row);
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<meta name="referrer" content="no-referrer">
<title>Entretiens — Administration Librairie universelle</title>
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/css/app.css">
<link rel="stylesheet" href="/assets/css/interview.css?v=1">
</head>
<body>
<a class="skip-link" href="#main">Aller au contenu</a>
<header class="site-header">
<a class="brand" href="/admin/">🛠️ Administration</a>
<nav class="site-nav"><a href="/admin/">Tableau de bord</a><a href="/" target="_blank" rel="noopener noreferrer">Site public</a><form method="post" action="/admin/deconnexion.php"><input type="hidden" name="csrf" value="<?= ei_e(Csrf::token()) ?>"><button type="submit">Se déconnecter</button></form></nav>
</header>
<main id="main" class="interview-shell admin-interview">
<section class="interview-card">
<p class="eyebrow">Enquête 2014–2026 · Entretiens écrits</p>
<h1>Entretien avec Cécile Bory-Saillant</h1>
<p>Un retour sur l’échange de 2014 à la librairie Georges, pour l’édition revue en 2026 du mémoire <em>Les librairies indépendantes face au marché numérique du livre</em>.</p>
<p><strong>État :</strong> <?= ei_e((string)$row['status']) ?> — <?= count(Interview::questions()) ?> questions, réparties par thèmes.</p>
<?php if ($error!==''): ?><p class="interview-error" role="alert"><?= ei_e($error) ?></p><?php endif; ?>
<?php if ($notice!==''): ?><p class="interview-notice" role="status"><?= ei_e($notice) ?></p><?php endif; ?>
<?php if ($row['status']==='draft'): ?>
    <h2>1. Vérifier la destinataire</h2>
    <p>L’adresse proposée provient du contact professionnel public de la librairie Georges. Vérifiez-la avant tout envoi.</p>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= ei_e($csrf) ?>">
        <label for="email">Adresse de destination</label>
        <input type="email" name="email" id="email" value="<?= ei_e((string)$row['recipient_email']) ?>" required>
        <button type="submit" name="action" value="save_address">Enregistrer l’adresse</button>
    </form>
    <h2>2. Activer son accès privé</h2>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= ei_e($csrf) ?>">
        <button type="submit" name="action" value="activate">Activer le lien d’invitation</button>
    </form>
<?php elseif ($active): ?>
    <h2>L’invitation est prête</h2>
    <p>Ce lien individuel est confidentiel : toute personne qui le possède peut répondre. Ne le publiez pas et ne le transmettez qu’à la destinataire vérifiée.</p>
    <label for="invite-link">Lien privé à envoyer</label>
    <input id="invite-link" class="interview-url" readonly value="<?= ei_e($link) ?>" aria-describedby="invite-note">
    <p id="invite-note">L’adresse et les réponses ne figurent pas dans le dépôt GitHub.</p>
    <div class="interview-actions">
        <a class="button-link" href="<?= ei_e($mailto) ?>">Préparer l’e-mail d’invitation</a>
        <a class="button-link secondary" href="<?= ei_e($link) ?>" target="_blank" rel="noopener noreferrer">Tester le parcours</a>
    </div>
    <p class="interview-hint">Le bouton d’e-mail ouvre votre messagerie et préremplit le message : vérifiez puis envoyez depuis votre compte. Ce site ne déclare pas l’invitation envoyée.</p>
<?php elseif ($submitted): ?>
    <h2>Réponses transmises, en attente de relecture éditoriale</h2>
    <p>Aucune publication automatique. La personne doit pouvoir valider la version finale avant sa mise en ligne.</p>
<?php elseif ($row['status']==='closed'): ?>
    <p>Accès fermé. Le lien précédent ne fonctionne plus.</p>
<?php endif; ?>
<div class="interview-actions">
<?php if (in_array($row['status'],['invited','in_progress','submitted'],true)): ?>
    <form method="post"><input type="hidden" name="csrf" value="<?= ei_e($csrf) ?>"><button class="secondary" type="submit" name="action" value="close">Révoquer l’accès</button></form>
<?php elseif ($row['status']==='closed'): ?>
    <form method="post"><input type="hidden" name="csrf" value="<?= ei_e($csrf) ?>"><button type="submit" name="action" value="reopen">Rouvrir avec un nouveau lien</button></form>
<?php endif; ?>
<a class="button-link secondary" href="?export=json">Exporter questions et réponses (JSON)</a>
</div>
</section>
<section class="interview-card">
<h2>Questionnaire et réponses privées</h2>
<?php $lastTheme=''; foreach (Interview::questions() as $n=>$question): ?>
    <?php if ($lastTheme!==$question['theme']): $lastTheme=$question['theme']; ?><h3><?= ei_e($lastTheme) ?></h3><?php endif; ?>
    <div class="interview-answer">
        <p><strong><?= $n+1 ?>. <?= ei_e($question['question']) ?></strong></p>
        <?php if (trim((string)($answers[(string)$n]??''))!==''): ?>
            <p><?= nl2br(ei_e((string)$answers[(string)$n])) ?></p>
        <?php else: ?><p class="interview-hint">Pas encore de réponse.</p><?php endif; ?>
    </div>
<?php endforeach; ?>
</section>
</main></body></html>
