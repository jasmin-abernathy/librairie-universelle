<?php

declare(strict_types=1);

final class AdminAuth
{
    public static function enforce(array $config): void
    {
        $expected = (string) ($config['admin_token'] ?? '');
        if ($expected === '') {
            http_response_code(503);
            header('Content-Type: text/plain; charset=utf-8');
            echo "ADMIN_TOKEN n’est pas configuré.\n";
            exit;
        }

        $password = $_SERVER['PHP_AUTH_PW'] ?? null;
        if (!is_string($password)) {
            $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (is_string($authorization) && str_starts_with($authorization, 'Basic ')) {
                $decoded = base64_decode(substr($authorization, 6), true);
                if (is_string($decoded) && str_contains($decoded, ':')) {
                    [, $password] = explode(':', $decoded, 2);
                }
            }
        }

        if (!is_string($password) || !hash_equals($expected, $password)) {
            header('WWW-Authenticate: Basic realm="Librairie universelle alpha"');
            http_response_code(401);
            header('Content-Type: text/plain; charset=utf-8');
            echo "Authentification requise.\n";
            exit;
        }
    }
}
