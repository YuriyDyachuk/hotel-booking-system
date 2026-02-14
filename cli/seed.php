#!/usr/bin/env php
<?php

require_once __DIR__ . '/../src/Database/Connection.php';
require_once __DIR__ . '/../src/Helpers/Timer.php';

use App\Database\Connection;
use App\Helpers\Timer;

/**
 * Usage:
 *   php cli/seed.php
 *   php cli/seed.php countries
 *   php cli/seed.php --list
 */

$config = require __DIR__ . '/../src/config.php';
Connection::init($config['database']);

try {
    $pdo = Connection::getInstance();
} catch (\Exception $e) {
    echo "Failed to connect to database: " . $e->getMessage() . "\n";
    exit(1);
}

$seeders = [
    'countries' => [
        'name' => 'Countries & Cities',
        'file' => '001_seed_countries_cities.php',
        'class' => 'CountriesCitiesSeeder'
    ],
    'room-types' => [
        'name' => 'Room Types',
        'file' => '002_seed_room_types.php',
        'class' => 'RoomTypesSeeder'
    ],
    'users' => [
        'name' => 'Users (100K)',
        'file' => '003_seed_users.php',
        'class' => 'UsersSeeder'
    ],
    'hotels' => [
        'name' => 'Hotels (1K)',
        'file' => '004_seed_hotels.php',
        'class' => 'HotelsSeeder'
    ],
    'rooms' => [
        'name' => 'Rooms (50K)',
        'file' => '005_seed_rooms.php',
        'class' => 'RoomsSeeder'
    ],
    'bookings' => [
        'name' => 'Bookings (1M)',
        'file' => '006_seed_bookings.php',
        'class' => 'BookingsSeeder'
    ],
    'reviews' => [
        'name' => 'Reviews',
        'file' => '007_seed_reviews.php',
        'class' => 'ReviewsSeeder'
    ]
];

function showSeedersList(array $seeders): void
{
    echo "\n";
    echo str_repeat('=', 80) . "\n";
    echo "AVAILABLE SEEDERS\n";
    echo str_repeat('=', 80) . "\n\n";

    foreach ($seeders as $key => $seeder) {
        echo sprintf("  %-20s - %s\n", $key, $seeder['name']);
    }

    echo "\n";
    echo "Usage:\n";
    echo "  php cli/seed.php              - Run all seeders\n";
    echo "  php cli/seed.php countries    - Run specific seeder\n";
    echo "  php cli/seed.php --list       - Show this list\n";
    echo "\n";
}

function runSeeder(PDO $pdo, array $seederInfo): bool
{
    $seedersPath = __DIR__ . '/../database/seeders';
    $filePath = $seedersPath . '/' . $seederInfo['file'];

    if (!file_exists($filePath)) {
        echo "Seeder file not found: {$filePath}\n";
        return false;
    }

    require_once $filePath;

    $className = $seederInfo['class'];

    if (!class_exists($className)) {
        echo "Seeder class not found: {$className}\n";
        return false;
    }

    try {
        $seeder = new $className($pdo);
        $seeder->run();
        return true;
    } catch (\Exception $e) {
        echo "Seeder failed: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
        return false;
    }
}

function runAllSeeders(PDO $pdo, array $seeders): void
{
    echo "\n";
    echo str_repeat('=', 80) . "\n";
    echo "RUNNING ALL SEEDERS\n";
    echo str_repeat('=', 80) . "\n";

    Timer::start('all_seeders');

    $executed = 0;
    $failed = 0;

    foreach ($seeders as $key => $seeder) {
        echo "\n" . str_repeat('-', 80) . "\n";
        echo "Running: {$seeder['name']}\n";
        echo str_repeat('-', 80) . "\n";

        if (runSeeder($pdo, $seeder)) {
            $executed++;
        } else {
            $failed++;
            echo "\n Stopping due to error.\n";
            break;
        }
    }

    Timer::stop('all_seeders');

    echo "\n";
    echo str_repeat('=', 80) . "\n";
    echo "SUMMARY\n";
    echo str_repeat('=', 80) . "\n";
    echo "Executed: {$executed}\n";
    echo "Failed: {$failed}\n";
    echo "Total: " . count($seeders) . "\n";
    echo str_repeat('=', 80) . "\n\n";
}

$command = $argv[1] ?? 'all';

if ($command === '--list') {
    showSeedersList($seeders);
    exit(0);
}

if ($command === 'all') {
    runAllSeeders($pdo, $seeders);
    exit(0);
}

if (isset($seeders[$command])) {
    echo "\n";
    echo str_repeat('=', 80) . "\n";
    echo "RUNNING SEEDER: {$seeders[$command]['name']}\n";
    echo str_repeat('=', 80) . "\n";

    $success = runSeeder($pdo, $seeders[$command]);
    exit($success ? 0 : 1);
}

echo "Unknown seeder: {$command}\n";
showSeedersList($seeders);
exit(1);