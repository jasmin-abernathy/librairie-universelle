<?php
declare(strict_types=1);

/**
 * Sitemap public de la Librairie.
 * Généré depuis le catalogue réel ; aucune donnée d'auteur privée, ni requête de recherche.
 */
header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=300');
header('X-Content-Type-Options: nosniff');

try {
    [, $pdo] = require dirname(__DIR__) . '/src/bootstrap.php';

    $base = 'https://librairie.lepotager.org';
    $pages = [
        '/', '/ebooks.php', '/livres-auteurs.php',
        '/autoedition.php', '/diffuser-mon-livre.php', '/amazon-et-librairies.php',
        '/prestations-auteurs.php', '/assistant-papier.php', '/dossier-impression.php',
        '/projet.php', '/publier-et-lire-local.php', '/sans-ia.php', '/conditions-generales.php',
    ];
    $entries = [];
    foreach ($pages as $path) {
        $entries[] = [$base . $path, null];
    }

    // Les fiches d'œuvres sont consultables publiquement par identifiant.
    // Ne pas indexer l'ancien contenu fictif de validation.
    $works = $pdo->query(
        "SELECT id, updated_at FROM works
         WHERE title <> 'Livre indépendant de test'
         ORDER BY id ASC LIMIT 40000"
    );
    while ($work = $works->fetch(PDO::FETCH_ASSOC)) {
        $entries[] = [$base . '/work.php?id=' . (int) $work['id'], $work['updated_at'] ?? null];
    }

    // Même règle de publication que la page publique : convention signée obligatoire.
    // Ne jamais exposer les fiches en attente, cachées ou rejetées.
    $authors = $pdo->query(
        "SELECT b.id, b.reviewed_at FROM author_book_listings b
         WHERE b.status = 'published'
           AND EXISTS (
             SELECT 1 FROM seller_agreements a
             WHERE a.seller_key = b.seller_key AND a.signed_at IS NOT NULL
           )
         ORDER BY b.id ASC LIMIT 9000"
    );
    while ($book = $authors->fetch(PDO::FETCH_ASSOC)) {
        $entries[] = [$base . '/livre-auteur.php?id=' . (int) $book['id'], $book['reviewed_at'] ?? null];
    }
} catch (Throwable $exception) {
    http_response_code(503);
    header('Cache-Control: no-store');
    error_log('Librairie sitemap generation failed: ' . $exception->getMessage());
    echo "Sitemap temporarily unavailable\n";
    exit;
}

$xml = static fn (string $value): string => htmlspecialchars(
    $value, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'
);
echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
foreach ($entries as [$url, $modified]) {
    echo "  <url><loc>", $xml($url), "</loc>";
    $date = is_string($modified) ? substr($modified, 0, 10) : '';
    if (preg_match('/^\\d{4}-\\d{2}-\\d{2}$/D', $date)) {
        echo '<lastmod>', $date, '</lastmod>';
    }
    echo "</url>\n";
}
echo "</urlset>\n";
