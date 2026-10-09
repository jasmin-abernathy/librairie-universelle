<?php
declare(strict_types=1);

$currentNav = isset($currentNav) ? (string) $currentNav : '';
$items = [
    'librairies' => ['href' => '/#librairies', 'label' => 'Librairies proches'],
    'ebooks' => ['href' => '/ebooks.php', 'label' => 'Ebooks'],
    'autoedition' => ['href' => '/autoedition.php', 'label' => 'Autoédition'],
    'projet' => ['href' => '/projet.php', 'label' => 'Le projet'],
    'sans-ia' => ['href' => '/sans-ia.php', 'label' => 'Sans IA'],
];
?>
<header class="site-header">
    <a class="brand" href="/" aria-label="Accueil — Librairie universelle">📚 <span>Librairie universelle <small>nom de travail</small></span></a>
    <nav class="site-nav" aria-label="Navigation principale">
        <?php foreach ($items as $key => $item): ?>
            <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"<?= $currentNav === $key ? ' aria-current="page"' : '' ?>><?= htmlspecialchars($item['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></a>
        <?php endforeach; ?>
    </nav>
</header>
