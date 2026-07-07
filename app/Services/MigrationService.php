<?php

declare(strict_types=1);

namespace CaveTrip\Services;

use CaveTrip\Core\Application;
use PDO;

final class MigrationService
{
    public function __construct(private readonly Application $app)
    {
    }

    public function migrate(): array
    {
        $pdo = $this->app->db();
        $this->ensureMigrationTable($pdo);

        $applied = $this->appliedMigrations($pdo);
        $files = glob($this->app->rootPath('database/migrations/*.php')) ?: [];
        sort($files);

        $ran = [];
        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (in_array($name, $applied, true)) {
                continue;
            }

            $migration = require $file;
            if (!is_callable($migration)) {
                throw new \RuntimeException("Migration {$name} must return a callable.");
            }

            try {
                $migration($pdo);

                $stmt = $pdo->prepare('INSERT INTO migrations (migration, applied_at) VALUES (:migration, NOW())');
                $stmt->execute(['migration' => $name]);

                $ran[] = $name;
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                throw $e;
            }
        }

        return $ran;
    }

    private function ensureMigrationTable(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function appliedMigrations(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT migration FROM migrations ORDER BY id');
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }
}
