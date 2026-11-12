<?php
declare(strict_types=1);

[$config, $pdo] = require dirname(__DIR__).'/src/bootstrap.php';
require_once dirname(__DIR__).'/src/Interview.php';
header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');

function itw_e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
$id = (string)($_GET['i'] ?? '');
$key = (string)($_GET['k'] ?? '');
$row = $id === Interview::CECILE_ID ? Interview::find($pdo,$id) : null;
if ($row === null || !Interview::canOpen($row, $config, $key)) {
    http_response_code(404);
    echo 'Cet entretien privé est introuvable ou son lien a été révoqué.';
    exit;
}

$error = '';
$questions = Interview::questions();
$count = count($questions);
$pauseNotice = false;
$unsavedAnswer = null;

/* Autosaves are separate from navigation and never complete the questionnaire. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'autosave') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        if (!Csrf::validate($_POST['csrf'] ?? null) || !Interview::sessionAuthorized($row)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Session expirée : reconnectez-vous pour enregistrer.']);
            exit;
        }
        $cursor = filter_var($_POST['cursor'] ?? null, FILTER_VALIDATE_INT);
        if ($cursor === false || $cursor === null) {
            throw new InvalidArgumentException('Question invalide.');
        }
        $fingerprint = Interview::saveDraft($pdo, $row, (int)$cursor,
            (string)($_POST['answer'] ?? ''), (string)($_POST['fingerprint'] ?? ''));
        echo json_encode(['ok' => true, 'fingerprint' => $fingerprint], JSON_THROW_ON_ERROR);
    } catch (InvalidArgumentException $exception) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
    } catch (RuntimeException $exception) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!Csrf::validate($_POST['csrf'] ?? null)) {
            throw new RuntimeException('Formulaire expiré. Rechargez cette page.');
        }
        if (!Interview::sessionAuthorized($row)) {
            $authAction = (string)($_POST['action'] ?? '');
            if ($authAction === 'send_code') {
                Interview::sendCode($pdo, $row, $config);
            } elseif ($authAction === 'verify_code') {
                if (!Interview::verifyCode($pdo, $row, $config, trim((string)($_POST['code'] ?? '')))) {
                    throw new RuntimeException('Code incorrect ou expiré. Demandez un nouveau code si nécessaire.');
                }
                // The PHP session identifier is rotated after the second authentication factor.
                session_regenerate_id(true);
            } else {
                throw new RuntimeException('Vérifiez d’abord votre adresse e-mail.');
            }
        } else {
        $requested = (int)($_POST['cursor'] ?? -1);
        if ($requested !== (int)$row['cursor']) throw new RuntimeException('Étape modifiée. Rechargez la page.');
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'submit') {
            if ($requested !== $count || !in_array($row['status'], ['invited','in_progress'], true)) {
                throw new RuntimeException('Le questionnaire n’est pas encore terminé.');
            }
            $answers = Interview::answers($row);
            foreach ($questions as $n => $question) {
                if ($question['required'] && trim((string)($answers[(string)$n] ?? '')) === '') {
                    throw new RuntimeException('La présentation est incomplète.');
                }
            }
            if (($_POST['consent'] ?? '') !== 'yes') {
                throw new RuntimeException('Merci de confirmer l’accord pour la préparation éditoriale.');
            }
            $stmt = $pdo->prepare("UPDATE editorial_interviews SET status='submitted', submitted_at=CURRENT_TIMESTAMP,
                consent_at=CURRENT_TIMESTAMP, updated_at=CURRENT_TIMESTAMP WHERE id=? AND cursor=? AND status IN ('invited','in_progress')");
            $stmt->execute([$row['id'],$count]);
            if ($stmt->rowCount() !== 1) throw new RuntimeException('Entretien déjà envoyé ou modifié.');
        } elseif (in_array($action, ['next','back'], true)) {
            Interview::saveAnswer($pdo, $row, $requested, (string)($_POST['answer'] ?? ''),
                $action, (string)($_POST['fingerprint'] ?? ''));
        } elseif ($action === 'save_exit') {
            Interview::saveDraft($pdo, $row, $requested, (string)($_POST['answer'] ?? ''),
                (string)($_POST['fingerprint'] ?? ''));
            unset($_SESSION['editorial_interview_auth'][$row['id']]);
            $_SESSION['interview_pause_notice'][$row['id']] = true;
            session_regenerate_id(true);
        } elseif ($action === 'back_from_review' && $requested === $count) {
            $stmt = $pdo->prepare("UPDATE editorial_interviews SET cursor=?,updated_at=CURRENT_TIMESTAMP WHERE id=? AND cursor=? AND status IN ('invited','in_progress')");
            $stmt->execute([$count-1,$row['id'],$count]);
        } else {
            throw new RuntimeException('Action inconnue.');
        }
        }
        header('Location: '.(string)($_SERVER['REQUEST_URI'] ?? '/interview.php'),true,303);
        exit;
    } catch (RuntimeException|InvalidArgumentException $exception) {
        $error = $exception->getMessage();
        $unsavedAnswer = isset($_POST['answer']) ? (string)$_POST['answer'] : null;
    }
    $row = Interview::find($pdo, $id) ?? $row;
}
$answers = Interview::answers($row);
$cursor = min($count,max(0,(int)$row['cursor']));
$step = $questions[$cursor] ?? null;
$done = $row['status'] === 'submitted';
$csrf = Csrf::token();
$authenticated = Interview::sessionAuthorized($row);
if (!$authenticated && isset($_SESSION['interview_pause_notice'][$row['id']])) {
    $pauseNotice = true;
    unset($_SESSION['interview_pause_notice'][$row['id']]);
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<meta name="referrer" content="no-referrer">
<title>Entretien avec Cécile Bory-Saillant — Librairie universelle</title>
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/css/app.css">
<link rel="stylesheet" href="/assets/css/interview.css?v=1">
</head>
<body>
<a class="skip-link" href="#main">Aller à l’entretien</a>
<header class="site-header interview-header"><span class="brand">📚 Librairie universelle <small>· Entretien privé</small></span></header>
<main id="main" class="interview-shell">
<section class="interview-card">
<p class="eyebrow">Enquête 2014–2026 · entretien écrit</p>
<h1>Douze ans après : la librairie Georges face au numérique</h1>
<p>Bonjour Cécile, et merci d’avoir accepté de revenir sur notre échange de 2014. Ce nouvel entretien prolonge mon mémoire sur les librairies indépendantes et la révolution numérique du livre. Il porte sur les choix réellement faits depuis, les conditions économiques et les perspectives du métier.</p>
<p>Une question à la fois, dans l’ordre ; votre texte est sauvegardé automatiquement après une courte pause. Vous pouvez aussi choisir « Enregistrer et quitter » pour reprendre plus tard avec le même lien et un nouveau code. <strong>Rien n’est publié automatiquement.</strong></p>
<?php if ($error !== ''): ?><p class="interview-error" role="alert"><?= itw_e($error) ?></p><?php endif; ?>
<?php if ($pauseNotice): ?><p class="interview-notice" role="status">Votre brouillon a bien été enregistré. Vous pouvez fermer cette page et reprendre quand vous le souhaitez avec votre lien d’invitation.</p><?php endif; ?>
<?php if (!$authenticated): ?>
    <h2>Accéder à votre entretien</h2>
    <p>Pour préserver la confidentialité de vos réponses, un code à six chiffres est envoyé uniquement à l’adresse professionnelle enregistrée par la rédaction. Ce code est valable dix minutes.</p>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= itw_e($csrf) ?>">
        <button type="submit" name="action" value="send_code">Recevoir un code par e-mail</button>
    </form>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= itw_e($csrf) ?>">
        <label for="code">Votre code de vérification</label>
        <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required>
        <button type="submit" name="action" value="verify_code">Ouvrir l’entretien</button>
    </form>
<?php elseif ($done): ?>
    <h2>Merci pour cet entretien !</h2>
    <p>Vos réponses ont été transmises à la rédaction et le formulaire est verrouillé. Une version éditée pourra vous être proposée pour relecture avant toute publication.</p>
<?php elseif ($step !== null): ?>
    <div class="interview-progress" aria-label="Progression">
        <span>Question <?= $cursor+1 ?> sur <?= $count ?></span>
        <progress max="<?= $count ?>" value="<?= $cursor ?>"></progress>
    </div>
    <p class="interview-theme"><?= itw_e($step['theme']) ?></p>
    <form method="post" id="interview-answer-form">
        <input type="hidden" name="csrf" value="<?= itw_e($csrf) ?>">
        <input type="hidden" name="cursor" value="<?= $cursor ?>">
        <input type="hidden" name="fingerprint" id="interview-answer-fingerprint" value="<?= itw_e(Interview::draftFingerprint($row, $cursor)) ?>">
        <label for="answer" class="interview-question"><?= itw_e($step['question']) ?></label>
        <textarea id="answer" name="answer" rows="9" maxlength="18000" <?= $step['required']?'required':'' ?> autocomplete="off"><?= itw_e($unsavedAnswer ?? (string)($answers[(string)$cursor] ?? '')) ?></textarea>
        <p id="interview-save-status" class="interview-save-status" role="status" aria-live="polite">Votre réponse sera enregistrée lorsque vous utiliserez un bouton.</p>
        <?php if (!$step['required']): ?><p class="interview-hint">Question facultative : vous pouvez laisser ce champ vide.</p><?php endif; ?>
        <?php if (isset($questions[$cursor+1])): ?><p class="interview-next">Ensuite : <?= itw_e($questions[$cursor+1]['question']) ?></p><?php endif; ?>
        <div class="interview-actions">
            <?php if ($cursor > 0): ?><button type="submit" class="secondary" name="action" value="back" formnovalidate>Précédente</button><?php endif; ?>
            <button type="submit" name="action" value="next"><?= $cursor === $count-1 ? 'Relire et terminer' : 'Enregistrer et continuer' ?></button>
            <button type="submit" class="secondary" name="action" value="save_exit" formnovalidate>Enregistrer et quitter</button>
        </div>
    </form>
<?php else: ?>
    <h2>Votre réponse est prête</h2>
    <p>Vous avez parcouru les <?= $count ?> questions. Vos réponses restent privées : la rédaction en préparera une version, qui vous sera soumise pour relecture avant toute publication.</p>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= itw_e($csrf) ?>">
        <input type="hidden" name="cursor" value="<?= $cursor ?>">
        <label class="interview-consent"><input type="checkbox" name="consent" value="yes" required> J’accepte que mes réponses soient utilisées pour préparer un entretien éditorial, sous réserve de ma validation avant publication.</label>
        <div class="interview-actions">
            <button type="submit" class="secondary" name="action" value="back_from_review">Relire mes réponses</button>
            <button type="submit" name="action" value="submit">Transmettre l’entretien</button>
        </div>
    </form>
<?php endif; ?>
</section>
<p class="interview-privacy">Les réponses sont conservées dans l’espace privé du site, accessibles à la rédaction uniquement. Pour demander une modification ou la suppression des données avant publication : <a href="mailto:jleveque@lepotager.org">jleveque@lepotager.org</a>.</p>
</main>
<?php if ($authenticated && !$done && $step !== null): ?>
<script src="/assets/js/interview-autosave.js?v=20261112-1" defer></script>
<?php endif; ?>
</body>
</html>
