<?php

declare(strict_types=1);

final class FeedbackService
{
    public static function submit(PDO $pdo, array $config, array $input): int
    {
        if (!$config['feedback_enabled']) {
            throw new RuntimeException('Le canal de retour n’est pas encore ouvert.');
        }

        $kind = (string) ($input['kind'] ?? 'general');
        if (!in_array($kind, ['general', 'error', 'accessibility', 'catalog', 'self_publishing'], true)) {
            $kind = 'general';
        }

        $message = trim((string) ($input['message'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $pageUrl = trim((string) ($input['page_url'] ?? ''));
        if ($message === '' || mb_strlen($message) > 5000) {
            throw new InvalidArgumentException('Le message est vide ou trop long.');
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Adresse e-mail invalide.');
        }

        $statement = $pdo->prepare(<<<'SQL'
INSERT INTO feedback (page_url, kind, message, email)
VALUES (:page_url, :kind, :message, :email)
SQL);
        $statement->execute([
            ':page_url' => $pageUrl !== '' ? mb_substr($pageUrl, 0, 500) : null,
            ':kind' => $kind,
            ':message' => $message,
            ':email' => $email !== '' ? $email : null,
        ]);

        return (int) $pdo->lastInsertId();
    }
}
