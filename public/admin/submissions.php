<?php

declare(strict_types=1);

[$config, $pdo] = require dirname(__DIR__, 2) . '/src/bootstrap.php';
AdminAuth::enforce($config);
$service = new SelfPublishingService($pdo, $config);
$message = null;
$errorMessage = null;
$id = isset($_GET['id']) ? max(0, (int) $_GET['id']) : 0;
$statusFilter = isset($_GET['status']) ? (string) $_GET['status'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        $errorMessage = 'Session expirée.';
    } else {
        $id = max(0, (int) ($_POST['id'] ?? 0));
        $action = (string) ($_POST['action'] ?? '');
        try {
            if ($action === 'publish') {
                $workId = $service->publish($id);
                $message = 'Soumission publiée dans le catalogue — œuvre #' . $workId . '.';
            } elseif (in_array($action, ['approved', 'needs_changes', 'rejected'], true)) {
                $service->review($id, $action, $_POST['editorial_note'] ?? null);
                $message = 'Statut mis à jour.';
            } else {
                throw new InvalidArgumentException('Action inconnue.');
            }
        } catch (Throwable $error) {
            $errorMessage = $error->getMessage();
        }
    }
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function cents(?int $value): string
{
    return $value === null ? '—' : number_format($value / 100, 2, ',', ' ') . ' €';
}

function yesNo(mixed $value): string
{
    return (int) $value === 1 ? 'Oui' : 'Non';
}

function printSettingLabel(string $kind, ?string $value): string
{
    $labels = [
        'trim_size' => ['a5' => 'A5 — 14,8 × 21 cm', '140x210' => '14 × 21 cm', '135x215' => '13,5 × 21,5 cm', '152x229' => '15,2 × 22,9 cm / 6 × 9 pouces'],
        'binding' => ['paperback' => 'Broché', 'hardcover' => 'Relié'],
        'chapter_start' => ['right' => 'Page de droite', 'next' => 'Page suivante'],
        'page_number_position' => ['outside' => 'Extérieur gauche/droite', 'center' => 'Centrés en bas', 'none' => 'Masqués'],
        'front_matter_numbering' => ['roman' => 'Chiffres romains puis arabes', 'hidden' => 'Comptées mais masquées', 'arabic' => 'Chiffres arabes continus'],
        'gutter_mode' => ['auto' => 'Automatique', 'custom' => 'Personnalisée'],
    ];
    if ($value === null || $value === '') {
        return '—';
    }
    return $labels[$kind][$value] ?? $value;
}

$current = $id > 0 ? $service->find($id) : null;
$submissions = $service->listSubmissions($statusFilter);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <title>Soumissions — administration</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="/admin/">🛠️ <span>Administration <small>alpha</small></span></a>
    <nav class="site-nav"><a href="/admin/submissions.php" aria-current="page">Soumissions</a><a href="/admin/">Tableau de bord</a></nav>
</header>
<main>
    <section class="page-hero compact">
        <p class="eyebrow">Validation humaine</p>
        <h1>Soumissions d’autoédition</h1>
        <p class="lede">Aucune soumission n’est publiée automatiquement. Les fichiers peuvent être ouverts uniquement depuis cette zone protégée.</p>
    </section>

    <?php if ($message): ?><div class="success-box" role="status"><?= e($message) ?></div><?php endif; ?>
    <?php if ($errorMessage): ?><div class="error-box" role="alert"><?= e($errorMessage) ?></div><?php endif; ?>

    <?php if ($current): ?>
        <section class="content-section">
            <div class="section-heading"><h2>#<?= (int) $current['id'] ?> — <?= e($current['title']) ?></h2><span><?= e($current['status']) ?></span></div>
            <div class="admin-detail-grid">
                <div>
                    <h3>Projet</h3>
                    <dl class="detail-list">
                        <div><dt>Auteur</dt><dd><?= e($current['author_name']) ?><?= $current['pseudonym'] ? ' / ' . e($current['pseudonym']) : '' ?></dd></div>
                        <div><dt>E-mail</dt><dd><?= e($current['email']) ?></dd></div>
                        <div><dt>Langue</dt><dd><?= e($current['language']) ?></dd></div>
                        <div><dt>ISBN EPUB</dt><dd><?= e($current['isbn_ebook']) ?: '—' ?></dd></div>
                        <div><dt>ISBN papier</dt><dd><?= e($current['isbn_paper']) ?: '—' ?></dd></div>
                        <div><dt>Prix ebook</dt><dd><?= cents($current['ebook_price_cents'] !== null ? (int) $current['ebook_price_cents'] : null) ?></dd></div>
                        <div><dt>Prix papier</dt><dd><?= cents($current['paper_price_cents'] !== null ? (int) $current['paper_price_cents'] : null) ?></dd></div>
                        <div><dt>Diffusion ebook</dt><dd><?= e($current['ebook_distribution']) ?></dd></div>
                    </dl>
                    <?php if ($current['print_settings'] !== null): ?>
                        <?php $print = $current['print_settings']; ?>
                        <h3>Composition papier demandée</h3>
                        <dl class="detail-list">
                            <div><dt>Format</dt><dd><?= e(printSettingLabel('trim_size', $print['trim_size'])) ?></dd></div>
                            <div><dt>Reliure</dt><dd><?= e(printSettingLabel('binding', $print['binding'])) ?></dd></div>
                            <div><dt>Sommaire auto</dt><dd><?= yesNo($print['toc_enabled']) ?></dd></div>
                            <div><dt>Chapitres</dt><dd><?= e(printSettingLabel('chapter_start', $print['chapter_start'])) ?></dd></div>
                            <div><dt>Pagination</dt><dd><?= e(printSettingLabel('page_number_position', $print['page_number_position'])) ?></dd></div>
                            <div><dt>Ouverture chapitre</dt><dd><?= (int) $print['hide_chapter_openers'] === 1 ? 'Numéro masqué' : 'Numéro visible' ?></dd></div>
                            <div><dt>Pages liminaires</dt><dd><?= e(printSettingLabel('front_matter_numbering', $print['front_matter_numbering'])) ?></dd></div>
                            <div><dt>Fond perdu</dt><dd><?= (int) $print['bleed_mm'] ?> mm</dd></div>
                            <div><dt>Marge intérieure</dt><dd><?= e(printSettingLabel('gutter_mode', $print['gutter_mode'])) ?><?= $print['gutter_mm'] !== null ? ' — ' . e(number_format((float) $print['gutter_mm'], 1, ',', ' ')) . ' mm' : '' ?></dd></div>
                        </dl>
                    <?php endif; ?>
                    <h3>Présentation</h3>
                    <p><?= nl2br(e($current['description'])) ?></p>
                </div>
                <div>
                    <h3>Fichiers</h3>
                    <?php if ($current['files'] === []): ?><p>Aucun fichier.</p><?php else: ?><ul class="file-list">
                        <?php foreach ($current['files'] as $file): ?><li><strong><?= e($file['kind']) ?></strong> — <?= e($file['original_name']) ?> · <?= number_format(((int) $file['size_bytes']) / 1048576, 2, ',', ' ') ?> Mo · <a href="/admin/file.php?id=<?= (int) $file['id'] ?>">ouvrir/télécharger</a></li><?php endforeach; ?>
                    </ul><?php endif; ?>

                    <form class="submission-form review-form" method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(Csrf::token()) ?>">
                        <input type="hidden" name="id" value="<?= (int) $current['id'] ?>">
                        <label>Note éditoriale
                            <textarea name="editorial_note" rows="6" maxlength="5000"><?= e($current['editorial_note']) ?></textarea>
                        </label>
                        <div class="review-actions">
                            <button type="submit" name="action" value="approved">Valider</button>
                            <button type="submit" name="action" value="needs_changes" class="secondary-action">Demander des corrections</button>
                            <button type="submit" name="action" value="rejected" class="danger-action">Refuser</button>
                            <?php if ($current['status'] === 'approved'): ?><button type="submit" name="action" value="publish" class="secondary-action">Publier dans le catalogue</button><?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="content-section">
        <div class="filter-pills">
            <a href="/admin/submissions.php"<?= $statusFilter === null ? ' aria-current="page"' : '' ?>>Toutes</a>
            <?php foreach (['pending', 'needs_changes', 'approved', 'published', 'rejected'] as $status): ?><a href="/admin/submissions.php?status=<?= urlencode($status) ?>"<?= $statusFilter === $status ? ' aria-current="page"' : '' ?>><?= e($status) ?></a><?php endforeach; ?>
        </div>
        <div class="table-wrap"><table><thead><tr><th>ID</th><th>Titre</th><th>Auteur</th><th>Statut</th><th>Fichiers</th><th>Déposé</th><th></th></tr></thead><tbody>
        <?php foreach ($submissions as $submission): ?><tr><td>#<?= (int) $submission['id'] ?></td><td><?= e($submission['title']) ?></td><td><?= e($submission['pseudonym'] ?: $submission['author_name']) ?></td><td><?= e($submission['status']) ?></td><td><?= (int) $submission['file_count'] ?></td><td><?= e($submission['submitted_at']) ?></td><td><a href="/admin/submissions.php?id=<?= (int) $submission['id'] ?>">examiner</a></td></tr><?php endforeach; ?>
        </tbody></table></div>
    </section>
</main>
</body>
</html>
