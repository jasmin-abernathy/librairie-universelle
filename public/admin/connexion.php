<?php
declare(strict_types=1);
[$config,$pdo] = require dirname(__DIR__,2).'/src/bootstrap.php';
header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');

if (AdminAccounts::signedIn()) {
    header('Location: /admin/', true, 303);
    exit;
}

$error = '';
$setupQuery = (string)($_GET['initialisation'] ?? '');
if ($setupQuery !== '') {
    if (AdminAccounts::tokenValid($pdo, $setupQuery)) {
        $_SESSION['librairie_admin_setup_token'] = $setupQuery;
        // The secret must not remain in the browser URL or HTTP Referer.
        header('Location: /admin/connexion.php',true,303);
        exit;
    }
    $error = 'Ce lien de création de compte est invalide, expiré ou déjà utilisé.';
}
$hasAdmin = AdminAccounts::exists($pdo);
$setup = (string)($_SESSION['librairie_admin_setup_token'] ?? '');
$canSetup = !$hasAdmin && $setup!=='' && AdminAccounts::tokenValid($pdo,$setup);
if ($hasAdmin) unset($_SESSION['librairie_admin_setup_token']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!Csrf::validate($_POST['csrf'] ?? null)) {
            throw new RuntimeException('Ce formulaire a expiré. Rechargez la page.');
        }
        $action=(string)($_POST['action']??'');
        if ($action==='create' && $canSetup) {
            $password=(string)($_POST['password']??'');
            if (!hash_equals($password,(string)($_POST['confirm']??''))) {
                throw new RuntimeException('Les deux mots de passe ne correspondent pas.');
            }
            $id=AdminAccounts::createFirst($pdo,$setup,(string)($_POST['email']??''),$password);
        } elseif ($action==='login' && $hasAdmin) {
            $id=AdminAccounts::attemptLogin($pdo,(string)($_POST['email']??''),(string)($_POST['password']??''));
            if ($id===null) throw new RuntimeException('Identifiants incorrects ou trop de tentatives. Réessayez plus tard.');
        } else {
            throw new RuntimeException('Cette opération n’est pas disponible.');
        }
        AdminAccounts::signIn($id);
        $return = (string)($_SESSION['librairie_admin_return']??'/admin/');
        unset($_SESSION['librairie_admin_return']);
        $path=(string)(parse_url($return,PHP_URL_PATH)?:'');
        if ((!str_starts_with($path,'/admin/') && !in_array($path,['/enquete-lecteurs.php','/enquete-libraires.php'],true))
            || str_starts_with($return,'//')
            || str_contains($return,"\\")
            || str_contains($return,"\r")
            || str_contains($return,"\n")
            || str_contains($path,'connexion.php')) {
            $return='/admin/';
        }
        header('Location: '.$return,true,303);
        exit;
    } catch (InvalidArgumentException|RuntimeException $exception) {
        $error=$exception->getMessage();
    }
}
$csrf=Csrf::token();
function auth_e(string $text): string {return htmlspecialchars($text,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<meta name="referrer" content="no-referrer">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/css/app.css">
<link rel="stylesheet" href="/assets/css/admin-login.css?v=1">
<title><?= $canSetup?'Créer le compte administrateur':'Connexion' ?> — Librairie universelle</title>
</head>
<body class="admin-login-page">
<a class="skip-link" href="#auth-main">Aller au formulaire</a>
<header class="site-header auth-header"><a class="brand" href="/">📚 Librairie universelle</a></header>
<main id="auth-main" class="auth-main">
<section class="auth-card">
<p class="eyebrow">Accès privé · Administration</p>
<?php if ($canSetup): ?>
<h1>Créer mon compte administrateur</h1>
<p>Ce formulaire configure le premier compte du site. Son lien d’initialisation est personnel, valable une seule fois. Les inscriptions publiques sont désactivées.</p>
<?php elseif ($hasAdmin): ?>
<h1>Connexion</h1>
<p>Retrouve ton espace d’administration, tes entretiens et leurs réponses privées.</p>
<?php else: ?>
<h1>Initialisation protégée</h1>
<p>Le premier compte doit être créé à partir d’un lien d’initialisation privé. Aucun compte ne peut être ouvert librement depuis cette page.</p>
<?php endif; ?>
<?php if ($error!==''): ?><p class="auth-error" role="alert"><?= auth_e($error) ?></p><?php endif; ?>
<?php if ($canSetup||$hasAdmin): ?>
<form method="post" class="auth-form">
<input type="hidden" name="csrf" value="<?= auth_e($csrf) ?>">
<input type="hidden" name="action" value="<?= $canSetup?'create':'login' ?>">
<label for="email">Adresse e-mail</label>
<input id="email" name="email" type="email" autocomplete="username" maxlength="254" required>
<label for="password">Mot de passe</label>
<input id="password" name="password" type="password" autocomplete="<?= $canSetup?'new-password':'current-password' ?>" <?= $canSetup?'minlength="12"':'' ?> required>
<?php if ($canSetup): ?>
<label for="confirm">Confirmer le mot de passe</label>
<input id="confirm" name="confirm" type="password" autocomplete="new-password" minlength="12" required>
<p class="auth-hint">12 caractères minimum. Un mot de passe unique et un gestionnaire de mots de passe sont recommandés.</p>
<?php endif; ?>
<button type="submit"><?= $canSetup?'Créer mon compte':'Me connecter' ?></button>
</form>
<?php endif; ?>
<p class="auth-hint">Cette interface utilise une session sécurisée ; les mots de passe ne sont pas transmis par e-mail. Aucun compte supplémentaire ne peut être créé sans procédure d’administration.</p>
</section>
</main>
</body></html>
