<?php

declare(strict_types=1);

final class SyncLog
{
    public static function start(PDO $pdo, string $sourceName): int
    {
        $statement = $pdo->prepare('INSERT INTO sync_runs (source_name) VALUES (:source)');
        $statement->execute([':source' => $sourceName]);
        return (int) $pdo->lastInsertId();
    }

    public static function finish(PDO $pdo, int $runId, string $status, int $imported, int $skipped, int $conflicts, ?string $error = null): void
    {
        if (!in_array($status, ['success', 'partial', 'failed'], true)) {
            $status = 'failed';
        }

        $statement = $pdo->prepare(<<<'SQL'
UPDATE sync_runs
SET finished_at = CURRENT_TIMESTAMP,
    status = :status,
    imported_count = :imported,
    skipped_count = :skipped,
    conflict_count = :conflicts,
    error_message = :error
WHERE id = :id
SQL);
        $statement->execute([
            ':status' => $status,
            ':imported' => $imported,
            ':skipped' => $skipped,
            ':conflicts' => $conflicts,
            ':error' => $error,
            ':id' => $runId,
        ]);
    }
}
