<?php

declare(strict_types=1);

[$config, $pdo] = require dirname(__DIR__, 2) . '/src/bootstrap.php';
AdminAuth::enforce($config);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$submissionCounts = [];
foreach ($pdo->query('SELECT status, COUNT(*) AS count FROM author_submissions GROUP BY status')->fetchAll() as $row) {
    $submissionCounts[$row['status']] = (int) $row['count'];
}
$feedbackCount = (int) $pdo->query("SELECT COUNT(*) FROM feedback WHERE status = 'new'")->fetchColumn();
$worksCount = (int) $pdo->query('SELECT COUNT(*) FROM works')->fetchColumn();
$editionsCount = (int) $pdo->query('SELECT COUNT(*) FROM editions')->fetchColumn();
$latestSyncs = $pdo->query('SELECT * FROM sync_runs ORDER BY started_at DESC, id DESC LIMIT 10')->fetchAll();
$latestFeedback = $pdo->query("SELECT * FROM feedback ORDER BY created_at DESC, id DESC LIMIT 10")->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administration — <?= e($config['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="/admin/">🛠️ <span>Administration <small>alpha</small></span></a>
    <nav class="site-nav"><a href="/admin/submissions.php">Soumissions</a><a href="/" target="_blank" rel="noopener">Voir le site ↗</a></nav>
</header>
<main>
    <section class="page-hero compact">
        <p class="eyebrow">État du MVP</p>
        <h1>Ce qu’il faut regarder avant le reste.</h1>
        <div class="admin-stats">
            <article><strong><?= $worksCount ?></strong><span>œuvres</span></article>
            <article><strong><?= $editionsCount ?></strong><span>éditions</span></article>
            <article><strong><?= (int) ($submissionCounts['pending'] ?? 0) ?></strong><span>soumissions à lire</span></article>
            <article><strong><?= $feedbackCount ?></strong><span>retours nouveaux</span></article>
        </div>
    </section>

    <section class="content-section">
        <div class="section-heading"><h2>Autoédition</h2><a class="text-link" href="/admin/submissions.php">Tout voir →</a></div>
        <div class="proof-grid">
            <?php foreach (['pending' => 'À lire', 'needs_changes' => 'Corrections demandées', 'approved' => 'Validées', 'published' => 'Publiées', 'rejected' => 'Refusées'] as $status => $label): ?>
                <article><strong><?= (int) ($submissionCounts[$status] ?? 0) ?></strong><span><?= e($label) ?></span></article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="content-section">
        <h2>Dernières synchronisations</h2>
        <?php if ($latestSyncs === []): ?><p>Aucune synchronisation journalisée pour l’instant.</p><?php else: ?>
            <div class="table-wrap"><table><thead><tr><th>Source</th><th>Début</th><th>Statut</th><th>Ajouts</th><th>Ignorés</th><th>Conflits</th><th>Erreur</th></tr></thead><tbody>
            <?php foreach ($latestSyncs as $sync): ?><tr><td><?= e($sync['source_name']) ?></td><td><?= e($sync['started_at']) ?></td><td><?= e($sync['status']) ?></td><td><?= (int) $sync['imported_count'] ?></td><td><?= (int) $sync['skipped_count'] ?></td><td><?= (int) $sync['conflict_count'] ?></td><td><?= e($sync['error_message']) ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
        <?php endif; ?>
    </section>

    <section class="content-section">
        <h2>Derniers retours alpha</h2>
        <?php if ($latestFeedback === []): ?><p>Aucun retour pour l’instant.</p><?php else: ?>
            <div class="result-list">
            <?php foreach ($latestFeedback as $feedback): ?><article class="work-card"><div><p class="work-kind"><?= e($feedback['kind']) ?> · <?= e($feedback['status']) ?></p><p><?= nl2br(e($feedback['message'])) ?></p><?php if ($feedback['page_url']): ?><p><code><?= e($feedback['page_url']) ?></code></p><?php endif; ?></div><small><?= e($feedback['created_at']) ?></small></article><?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
