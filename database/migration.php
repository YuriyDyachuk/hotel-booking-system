<?php

require_once __DIR__ . '/../src/Database/Connection.php';
require_once __DIR__ . '/../src/Helpers/Timer.php';

use App\Database\Connection;
use App\Helpers\Timer;

class MigrationRunner
{
    private PDO $pdo;
    private string $migrationsPath;

    public function __construct(PDO $pdo, string $migrationsPath)
    {
        $this->pdo = $pdo;
        $this->migrationsPath = $migrationsPath;
    }

    public function run(): void
    {
        echo "\n";
        echo str_repeat('=', 80) . "\n";
        echo "DATABASE MIGRATION\n";
        echo str_repeat('=', 80) . "\n\n";

        Timer::start('total_migration');

        $this->createMigrationsTable();

        $migrations = $this->getMigrationFiles();

        if (empty($migrations)) {
            echo "No migration files found in: {$this->migrationsPath}\n";
            return;
        }

        echo "Found " . count($migrations) . " migration file(s)\n\n";

        $executed = 0;
        $skipped = 0;

        foreach ($migrations as $migration) {
            if ($this->isMigrationExecuted($migration)) {
                echo " Skipping: {$migration} (already executed)\n";
                $skipped++;
                continue;
            }

            echo " Executing: {$migration}\n";

            Timer::start($migration);

            try {
                $this->executeMigration($migration);
                $this->markMigrationAsExecuted($migration);

                Timer::stop($migration);
                echo " Success\n\n";
                $executed++;
            } catch (\Exception $e) {
                echo " Failed: " . $e->getMessage() . "\n\n";
                break;
            }
        }

        Timer::stop('total_migration');

        echo str_repeat('=', 80) . "\n";
        echo "SUMMARY\n";
        echo str_repeat('=', 80) . "\n";
        echo "Executed: {$executed}\n";
        echo "Skipped: {$skipped}\n";
        echo "Total: " . count($migrations) . "\n";
        echo str_repeat('=', 80) . "\n\n";
    }

    public function reset(): void
    {
        echo "\n";
        echo str_repeat('=', 80) . "\n";
        echo "RESET DATABASE\n";
        echo str_repeat('=', 80) . "\n\n";

        echo "WARNING: This will drop ALL tables!\n";
        echo "Are you sure? Type 'yes' to continue: ";

        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);

        if ($line !== 'yes') {
            echo "Aborted.\n\n";
            return;
        }

        Timer::start('reset');

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        $stmt = $this->pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo "\n Dropping tables...\n";
        foreach ($tables as $table) {
            echo "   Dropping: {$table}\n";
            $this->pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }

        $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        Timer::stop('reset');

        echo "\n Database reset complete!\n\n";
    }

    public function status(): void
    {
        echo "\n";
        echo str_repeat('=', 80) . "\n";
        echo " MIGRATION STATUS\n";
        echo str_repeat('=', 80) . "\n\n";

        $this->createMigrationsTable();

        $migrations = $this->getMigrationFiles();
        $executed = $this->getExecutedMigrations();

        if (empty($migrations)) {
            echo "No migration files found.\n\n";
            return;
        }

        echo sprintf("%-50s %s\n", "Migration", "Status");
        echo str_repeat('-', 80) . "\n";

        foreach ($migrations as $migration) {
            $status = in_array($migration, $executed) ? "Executed" : "Pending";
            $executedAt = '';

            if (in_array($migration, $executed)) {
                $stmt = $this->pdo->prepare("SELECT executed_at FROM migrations WHERE migration = ?");
                $stmt->execute([$migration]);
                $executedAt = $stmt->fetchColumn();
                $executedAt = " ({$executedAt})";
            }

            echo sprintf("%-50s %s%s\n", $migration, $status, $executedAt);
        }

        echo "\n";
    }

    private function createMigrationsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                migration VARCHAR(255) UNIQUE NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";
        $this->pdo->exec($sql);
    }

    private function getMigrationFiles(): array
    {
        $files = glob($this->migrationsPath . '/*.sql');

        if ($files === false) {
            return [];
        }

        sort($files);

        return array_map('basename', $files);
    }

    private function isMigrationExecuted(string $migration): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM migrations WHERE migration = ?");
        $stmt->execute([$migration]);
        return $stmt->fetchColumn() > 0;
    }

    private function getExecutedMigrations(): array
    {
        $stmt = $this->pdo->query("SELECT migration FROM migrations ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function executeMigration(string $migration): void
    {
        $filePath = $this->migrationsPath . '/' . $migration;

        if (!file_exists($filePath)) {
            throw new \RuntimeException("Migration file not found: {$filePath}");
        }

        $sql = file_get_contents($filePath);

        if ($sql === false) {
            throw new \RuntimeException("Failed to read migration file: {$filePath}");
        }

        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) { return !empty($stmt); }
        );

        foreach ($statements as $statement) {
            try {
                $this->pdo->exec($statement);
            } catch (\PDOException $e) {
                throw new \RuntimeException(
                    "Failed to execute statement in {$migration}: " . $e->getMessage()
                );
            }
        }
    }

    private function markMigrationAsExecuted(string $migration): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->execute([$migration]);
    }
}