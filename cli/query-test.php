#!/usr/bin/env php
<?php

require_once __DIR__ . '/../src/Database/Connection.php';
require_once __DIR__ . '/../src/Database/QueryLogger.php';
require_once __DIR__ . '/../src/Helpers/Debug.php';
require_once __DIR__ . '/../src/Helpers/Timer.php';

use App\Database\Connection;
use App\Database\QueryLogger;
use App\Helpers\Debug;
use App\Helpers\Timer;

/**
 * Usage:
 *   php cli/query-test.php
 */

$config = require __DIR__ . '/../src/config.php';
Connection::init($config['database']);

try {
    $pdo = Connection::getInstance();
} catch (\Exception $e) {
    echo "✗ Failed to connect to database: " . $e->getMessage() . "\n";
    exit(1);
}

QueryLogger::enable(true);

echo "\n";
echo str_repeat('=', 80) . "\n";
echo "🔍 QUERY TESTING AND ANALYSIS\n";
echo str_repeat('=', 80) . "\n\n";

// ============================================
// TEST 1: Simple SELECT
// ============================================
echo "TEST 1: Simple SELECT\n";
echo str_repeat('-', 80) . "\n";

$sql = "SELECT * FROM hotels LIMIT 10";
Debug::explain($pdo, $sql);

Timer::start('test1');
$stmt = QueryLogger::execute($pdo, $sql);
$results = $stmt->fetchAll();
Timer::stop('test1');

echo "Returned rows: " . count($results) . "\n\n";

// ============================================
// TEST 2: JOIN request
// ============================================
echo "TEST 2: Complex JOIN\n";
echo str_repeat('-', 80) . "\n";

$sql = "
    SELECT 
        h.id,
        h.name,
        c.name as city,
        co.name as country,
        COUNT(r.id) as total_rooms,
        h.rating
    FROM hotels h
    JOIN cities c ON h.city_id = c.id
    JOIN countries co ON c.country_id = co.id
    LEFT JOIN rooms r ON h.id = r.hotel_id
    WHERE h.is_active = 1
    GROUP BY h.id
    LIMIT 10
";

Debug::explain($pdo, $sql);

Timer::start('test2');
$stmt = QueryLogger::execute($pdo, $sql);
$results = $stmt->fetchAll();
Timer::stop('test2');

echo "Returned rows: " . count($results) . "\n";
Debug::dump($results[0] ?? [], 'Sample result');

// ============================================
// TEST 3: Search for available rooms (COMPLEX) - requires multiple joins and date range filtering
// ============================================
echo "\nTEST 3: Available rooms search (COMPLEX)\n";
echo str_repeat('-', 80) . "\n";

$checkIn = '2026-06-01';
$checkOut = '2026-06-05';
$cityName = 'Kyiv';

$sql = "
    SELECT 
        h.id AS hotel_id,
        h.name AS hotel_name,
        h.stars,
        h.rating,
        r.id AS room_id,
        r.room_number,
        r.base_price,
        rt.name AS room_type
    FROM rooms r
    JOIN hotels h ON r.hotel_id = h.id
    JOIN cities c ON h.city_id = c.id
    JOIN room_types rt ON r.room_type_id = rt.id
    LEFT JOIN bookings b ON r.id = b.room_id
        AND b.status IN ('confirmed', 'pending')
        AND NOT (b.check_out <= ? OR b.check_in >= ?)
    WHERE c.name = ?
      AND r.is_available = TRUE
      AND h.is_active = TRUE
      AND b.id IS NULL
    ORDER BY h.rating DESC, r.base_price ASC
    LIMIT 20
";

Debug::explain($pdo, $sql, [$checkIn, $checkOut, $cityName]);

Timer::start('test3');
$stmt = QueryLogger::execute($pdo, $sql, [$checkIn, $checkOut, $cityName]);
$results = $stmt->fetchAll();
Timer::stop('test3');

echo "Returned rows: " . count($results) . "\n";
if (!empty($results)) {
    Debug::dump($results[0], 'Sample result');
}

// ============================================
// TEST 4: Aggregation - statistics for a hotel over the last 6 months
// ============================================
echo "\nTEST 4: Hotel statistics (AGGREGATION)\n";
echo str_repeat('-', 80) . "\n";

$hotelId = 1;

$sql = "
    SELECT 
        DATE_FORMAT(b.check_in, '%Y-%m') AS month,
        COUNT(DISTINCT b.id) AS total_bookings,
        COUNT(DISTINCT CASE WHEN b.status = 'confirmed' THEN b.id END) AS confirmed_bookings,
        SUM(b.total_price) AS total_revenue,
        AVG(b.total_price) AS avg_booking_price,
        AVG(DATEDIFF(b.check_out, b.check_in)) AS avg_nights
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE r.hotel_id = ?
      AND b.check_in >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
      AND b.status != 'cancelled'
    GROUP BY month
    ORDER BY month DESC
";

Debug::explain($pdo, $sql, [$hotelId]);

Timer::start('test4');
$stmt = QueryLogger::execute($pdo, $sql, [$hotelId]);
$results = $stmt->fetchAll();
Timer::stop('test4');

echo "Returned rows: " . count($results) . "\n";
if (!empty($results)) {
    Debug::dump($results, 'Statistics by month');
}

// ============================================
// test 5: Users with the highest spending (COMPLEX) - requires multiple joins and aggregation
// ============================================
echo "\nTEST 5: Top spending users\n";
echo str_repeat('-', 80) . "\n";

$sql = "
    SELECT 
        u.id,
        u.email,
        u.first_name,
        u.last_name,
        COUNT(b.id) as total_bookings,
        SUM(b.total_price) as total_spent,
        AVG(b.total_price) as avg_per_booking,
        MAX(b.check_in) as last_booking_date
    FROM users u
    JOIN bookings b ON u.id = b.user_id
    WHERE b.status IN ('confirmed', 'completed')
      AND b.payment_status = 'paid'
    GROUP BY u.id
    HAVING total_bookings >= 3
    ORDER BY total_spent DESC
    LIMIT 10
";

Debug::explain($pdo, $sql);

Timer::start('test5');
$stmt = QueryLogger::execute($pdo, $sql);
$results = $stmt->fetchAll();
Timer::stop('test5');

echo "Returned rows: " . count($results) . "\n";
if (!empty($results)) {
    Debug::dump($results, 'Top 10 spenders');
}

// ============================================
//
// ============================================
echo "\n";
echo str_repeat('=', 80) . "\n";
echo "QUERY EXECUTION SUMMARY\n";
echo str_repeat('=', 80) . "\n";

QueryLogger::printStats();
QueryLogger::printDetailed();

$logFile = __DIR__ . '/../logs/query-test-' . date('Y-m-d-His') . '.log';
@mkdir(dirname($logFile), 0755, true);
QueryLogger::saveToFile($logFile);

echo " All tests completed!\n\n";