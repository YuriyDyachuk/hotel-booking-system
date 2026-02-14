<?php

namespace App\Database;

use PDO;
use PDOException;

/**
 * Singleton class for managing database connections using PDO.
 */
class Connection
{
    private static ?PDO $instance = null;
    private static array $config;

    /**
     * (Singleton)
     */
    private function __construct() {}

    /**
     * (Prevent cloning)
     */
    private function __clone() {}

    /**
     * @param array $config
     */
    public static function init(array $config): void
    {
        self::$config = $config;
    }

    /**
     * @return PDO
     * @throws PDOException
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            if (empty(self::$config)) {
                throw new \RuntimeException('Database config not initialized. Call Connection::init() first.');
            }

            $config = self::$config;
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            try {
                self::$instance = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options']
                );

                echo "✓ Database connection established\n";
            } catch (PDOException $e) {
                echo "✗ Database connection failed: " . $e->getMessage() . "\n";
                throw $e;
            }
        }

        return self::$instance;
    }

    /**
     * Close connection
     */
    public static function close(): void
    {
        self::$instance = null;
    }
}