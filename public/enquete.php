<?php
declare(strict_types=1);
$source=(string)($_GET['src']??'direct');
if (!in_array($source,['direct','linkedin','librairie','mediatheque','association','reddit','autre'],true)) $source='direct';
$src=$source!=='direct'?'?src='.rawurlencode($source):'';
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="canonical" href="https://librairie.lepotager.org/enquete.php">
<meta name="description" content="Étude indépendante 2026 sur les habitudes d'achat de livres et l'activité des librairies. Choisissez le questionnaire qui vous correspond.">
<meta name="theme-color" content="#f7f3ed">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<title>Enquête sur les livres et les librairies — 2026</title>
<link rel="stylesheet" href="/assets/css/app.css?v=20261104-nav2">
<link rel="stylesheet" href="/assets/css/reader-survey.css?v=20261111-1">
</head>
<body class="reader-survey">
<a href="#main" class="skip-link">Aller au contenu</a>
<?php $currentNav='survey'; require dirname(__DIR__).'/src/views/site-header.php'; ?>
<main class="survey-shell" id="main">
<section class="survey-card">
<p class="survey-kicker">Étude 2026 · Douze ans après</p>
<h1>Comment achetons-nous les livres aujourd’hui ?</h1>
<p class="survey-lead">En 2014, j’ai interrogé des lecteurs et des libraires sur les changements du marché du livre. Je renouvelle aujourd’hui cette enquête pour comprendre les habitudes d’achat, les besoins des librairies et les solutions qui pourraient réellement les aider.</p>
<p>Deux questionnaires indépendants, sans compte et sans publicité. <strong>Choisissez celui qui vous correspond :</strong></p>
<div class="survey-roles">
<a class="survey-role" href="/enquete-lecteurs.php<?= htmlspecialchars($src,ENT_QUOTES,'UTF-8') ?>">
<span class="survey-role-tag">Pour les particuliers</span>
<strong>Je suis lecteur ou lectrice</strong>
<span>Vos habitudes de lecture, vos circuits d’achat et les services que vous aimeriez trouver en librairie.</span>
<span><b>Répondre au questionnaire lecteurs →</b></span>
</a>
<a class="survey-role" href="/enquete-libraires.php<?= htmlspecialchars($src,ENT_QUOTES,'UTF-8') ?>">
<span class="survey-role-tag">Pour les professionnels</span>
<strong>Je représente une librairie</strong>
<span>Votre activité, les demandes des clients, vos pratiques de communication et les difficultés rencontrées.</span>
<span><b>Répondre au questionnaire libraires →</b></span>
</a>
</div>
<p class="survey-notice">Le questionnaire « libraires » s’adresse aux propriétaires, responsables ou personnes travaillant dans une librairie. Si vous êtes également lecteur ou lectrice, choisissez simplement le parcours sur lequel vous souhaitez témoigner : les réponses restent distinctes.</p>
</section>
<section class="survey-card">
<h2>Pourquoi cette enquête ?</h2>
<p>Elle prolonge mon mémoire de 2014, <em>Les librairies indépendantes face au marché numérique du livre</em>, pour construire des préconisations marketing adaptées aux réalités de 2026.</p>
<p>Les réponses seront étudiées de façon agrégée et confrontées aux études sectorielles. Les questionnaires ne cherchent ni à vendre un service, ni à valider une conclusion écrite d’avance.</p>
</section>
</main>
<footer class="survey-footer"><a href="/">Librairie universelle</a> · Étude indépendante · <a href="mailto:jleveque@lepotager.org">Contact</a></footer>
</body>
</html>
