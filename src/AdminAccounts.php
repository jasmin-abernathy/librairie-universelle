<?php
declare(strict_types=1);

/** Restricted administrative accounts. No public registration. */
final class AdminAccounts
{
    public static function exists(PDO $pdo): bool
    {
        return (int)$pdo->query('SELECT COUNT(*) FROM admin_accounts')->fetchColumn() > 0;
    }

    public static function setupToken(PDO $pdo, int $ttl = 86400): string
    {
        if (self::exists($pdo)) throw new RuntimeException('Un compte administrateur existe déjà.');
        $token = bin2hex(random_bytes(32));
        $pdo->exec('DELETE FROM admin_setup_tokens');
        $stmt = $pdo->prepare('INSERT INTO admin_setup_tokens(token_hash,expires_at) VALUES(?,?)');
        $stmt->execute([hash('sha256', $token), time() + $ttl]);
        return $token;
    }

    public static function tokenValid(PDO $pdo, string $token): bool
    {
        if (self::exists($pdo) || !preg_match('/\\A[a-f0-9]{64}\\z/D', $token)) return false;
        $stmt = $pdo->prepare('SELECT expires_at FROM admin_setup_tokens WHERE token_hash = ?');
        $stmt->execute([hash('sha256', $token)]);
        $expiry = $stmt->fetchColumn();
        return $expiry !== false && (int)$expiry >= time();
    }

    public static function createFirst(PDO $pdo, string $token, string $email, string $password): int
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            throw new InvalidArgumentException('Adresse e-mail invalide.');
        }
        if (strlen($password) < 12 || strlen($password) > 256) {
            throw new InvalidArgumentException('Choisissez un mot de passe de 12 caractères minimum.');
        }
        $pdo->beginTransaction();
        try {
            if (self::exists($pdo) || !self::tokenValid($pdo, $token)) {
                throw new RuntimeException('Lien d’initialisation invalide ou déjà utilisé.');
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare('INSERT INTO admin_accounts(email,password_hash) VALUES (?,?)');
            $insert->execute([$email, $hash]);
            $id = (int)$pdo->lastInsertId();
            $pdo->exec('DELETE FROM admin_setup_tokens');
            $pdo->commit();
            return $id;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    public static function attemptLogin(PDO $pdo, string $email, string $password): ?int
    {
        $email = strtolower(trim($email));
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $key = hash('sha256', $ip.'|'.$email);
        $now = time();
        $get = $pdo->prepare('SELECT failures,last_failure FROM admin_login_attempts WHERE id=?');
        $get->execute([$key]);
        $block = $get->fetch(PDO::FETCH_ASSOC);
        if ($block && (int)$block['failures'] >= 7 && $now - (int)$block['last_failure'] < 900) {
            return null;
        }
        $stmt = $pdo->prepare('SELECT id,password_hash FROM admin_accounts WHERE email=?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (is_array($user) && password_verify($password, (string)$user['password_hash'])) {
            $pdo->prepare('DELETE FROM admin_login_attempts WHERE id=?')->execute([$key]);
            $pdo->prepare('UPDATE admin_accounts SET last_login_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$user['id']]);
            if (password_needs_rehash((string)$user['password_hash'], PASSWORD_DEFAULT)) {
                $pdo->prepare('UPDATE admin_accounts SET password_hash=? WHERE id=?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            }
            return (int)$user['id'];
        }
        $failures = (!$block || $now - (int)$block['last_failure'] >= 900) ? 1 : (int)$block['failures'] + 1;
        $pdo->prepare('INSERT INTO admin_login_attempts(id,failures,last_failure) VALUES(?,?,?)
            ON CONFLICT(id) DO UPDATE SET failures=excluded.failures,last_failure=excluded.last_failure')
            ->execute([$key, $failures, $now]);
        return null;
    }

    public static function signIn(int $accountId): void
    {
        Csrf::token();
        session_regenerate_id(true);
        $_SESSION['librairie_admin_id'] = $accountId;
        $_SESSION['librairie_admin_issued_at'] = time();
        $_SESSION['librairie_admin_seen_at'] = time();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        unset($_SESSION['librairie_admin_setup_token']);
    }

    public static function signedIn(): bool
    {
        Csrf::token();
        $id = $_SESSION['librairie_admin_id'] ?? null;
        $now = time();
        if (!is_int($id) || $id < 1
            || $now - (int)($_SESSION['librairie_admin_seen_at'] ?? 0) > 3600
            || $now - (int)($_SESSION['librairie_admin_issued_at'] ?? 0) > 43200) {
            self::clearLogin();
            return false;
        }
        $_SESSION['librairie_admin_seen_at'] = $now;
        return true;
    }

    public static function clearLogin(): void
    {
        Csrf::token();
        unset($_SESSION['librairie_admin_id'],$_SESSION['librairie_admin_issued_at'],
              $_SESSION['librairie_admin_seen_at']);
    }
}
