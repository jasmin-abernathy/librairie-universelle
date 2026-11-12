<?php
declare(strict_types=1);

/** Session authentication for the site's existing administrative routes. */
final class AdminAuth
{
    public static function enforce(array $config): void
    {
        header('Cache-Control: private, no-store');
        header('X-Robots-Tag: noindex, nofollow');
        if (AdminAccounts::signedIn()) return;

        $request = (string)($_SERVER['REQUEST_URI'] ?? '/admin/');
        $path = (string)(parse_url($request, PHP_URL_PATH) ?: '');
        if (str_starts_with($path, '/admin/')
            || in_array($path, ['/enquete-lecteurs.php','/enquete-libraires.php'], true)) {
            $_SESSION['librairie_admin_return'] = $request;
        }
        // No WWW-Authenticate header: the browser must display the site login page.
        header('Location: /admin/connexion.php', true, 303);
        exit;
    }
}
