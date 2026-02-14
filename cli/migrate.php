#!/usr/bin/env php
<?php

require_once __DIR__ . '/../database/migration.php';
require_once __DIR__ . '/../src/Database/Connection.php';

use App\Database\Connection;

/**
 * Usage:
 *   php cli/migrate.php          - start migrations
 *   php cli/migrate.php status   - show migration status
 *   php cli/migrate.php reset    - reset database
 */

$config = require __DIR__ . '/../src/config.php';
Connection::init($config['database']);

try {
    $pdo = Connection::getInstance();
} catch (\Exception $e) {
    echo "Failed to connect to database: " . $e->getMessage() . "\n";
    exit(1);
}

$migrationsPath = __DIR__ . '/../database/migrations';
$runner = new MigrationRunner($pdo, $migrationsPath);

$command = $argv[1] ?? 'run';

switch ($command) {
    case 'run':
        $runner->run();
        break;

    case 'status':
        $runner->status();
        break;

    case 'reset':
        $runner->reset();
        break;

    case 'fresh':
        echo "Running fresh migration (reset + run)...\n";
        $runner->reset();
        $runner->run();
        break;

    default:
        echo "Unknown command: {$command}\n";
        echo "\nAvailable commands:\n";
        echo "  run     - Run pending migrations\n";
        echo "  status  - Show migration status\n";
        echo "  reset   - Drop all tables\n";
        echo "  fresh   - Reset database and run all migrations\n";
        exit(1);
}

exit(0);