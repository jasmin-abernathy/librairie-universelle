<?php
declare(strict_types=1);

$currentNav = isset($currentNav) ? (string) $currentNav : '';
$siteNavItems = [
    'search' => ['href' => '/', 'label' => 'Chercher un livre'],
    'librairies' => ['href' => '/#librairies', 'label' => 'Librairies proches'],
    'ebooks' => ['href' => '/ebooks.php', 'label' => 'Livres numériques'],
    'authorbooks' => ['href' => '/livres-auteurs.php', 'label' => 'Livres d’auteurs'],
    'autoedition' => ['href' => '/autoedition.php', 'label' => 'Préparer mon livre'],
    'propose' => ['href' => '/proposer-livre.php', 'label' => 'Proposer mon livre au catalogue'],
    'diffusion' => ['href' => '/diffuser-mon-livre.php', 'label' => 'Contacter des librairies'],
    'projet' => ['href' => '/projet.php', 'label' => 'Le projet'],
    'sans-ia' => ['href' => '/sans-ia.php', 'label' => 'Pourquoi sans IA ?'],
];
$readerKeys = ['search', 'librairies', 'ebooks', 'authorbooks'];
$authorKeys = ['autoedition', 'propose', 'diffusion'];
$aboutKeys = ['projet', 'sans-ia'];
$escapeNav = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<header class="site-header" data-site-navigation>
    <a class="brand" href="/" aria-label="Accueil — Librairie universelle">
        <span class="brand-mark" aria-hidden="true">📚</span>
        <span class="brand-copy"><span class="brand-name">Librairie universelle</span><small>Livres, librairies et auteurs</small></span>
    </a>
    <button class="site-nav-toggle" type="button" aria-controls="site-primary-navigation" aria-expanded="false" aria-label="Ouvrir le menu">
        <span>Menu</span>
        <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
    </button>
    <nav class="site-nav" id="site-primary-navigation" aria-label="Navigation principale">
        <?php foreach ($readerKeys as $key): $siteNavItem = $siteNavItems[$key]; ?>
            <a href="<?= $escapeNav($siteNavItem['href']) ?>"<?= $currentNav === $key ? ' aria-current="page"' : '' ?>><?= $escapeNav($siteNavItem['label']) ?></a>
        <?php endforeach; ?>
        <details class="site-nav-group"<?= in_array($currentNav, $authorKeys, true) ? ' data-active-group="true"' : '' ?>>
            <summary>Pour les auteurs</summary>
            <div class="site-nav-submenu">
                <?php foreach ($authorKeys as $key): $siteNavItem = $siteNavItems[$key]; ?>
                    <a href="<?= $escapeNav($siteNavItem['href']) ?>"<?= $currentNav === $key ? ' aria-current="page"' : '' ?>><?= $escapeNav($siteNavItem['label']) ?></a>
                <?php endforeach; ?>
            </div>
        </details>
        <details class="site-nav-group"<?= in_array($currentNav, $aboutKeys, true) ? ' data-active-group="true"' : '' ?>>
            <summary>À propos</summary>
            <div class="site-nav-submenu">
                <?php foreach ($aboutKeys as $key): $siteNavItem = $siteNavItems[$key]; ?>
                    <a href="<?= $escapeNav($siteNavItem['href']) ?>"<?= $currentNav === $key ? ' aria-current="page"' : '' ?>><?= $escapeNav($siteNavItem['label']) ?></a>
                <?php endforeach; ?>
            </div>
        </details>
    </nav>
</header>
<script src="/assets/js/site-nav.js?v=20261104-3" defer></script>
