<?php

require_once __DIR__ . '/../../src/Database/Connection.php';
require_once __DIR__ . '/../../src/Helpers/Timer.php';

use App\Database\Connection;
use App\Helpers\Timer;

class RoomsSeeder
{
    private PDO $pdo;
    private const BATCH_SIZE = 500;

    private array $viewTypes = ['city', 'sea', 'mountain', 'garden', 'courtyard'];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function run(): void
    {
        echo "\n Seeding rooms...\n";
        echo str_repeat('-', 80) . "\n";

        Timer::start('rooms_seeder');

        $this->seedRooms();

        Timer::stop('rooms_seeder');
        echo "Rooms seeded successfully!\n\n";
    }

    private function seedRooms(): void
    {
        $hotels = $this->pdo->query("
            SELECT id, total_rooms, stars 
            FROM hotels 
            ORDER BY id
        ")->fetchAll(PDO::FETCH_ASSOC);

        $roomTypes = $this->pdo->query("
            SELECT id, name, slug 
            FROM room_types
        ")->fetchAll(PDO::FETCH_ASSOC);

        if (empty($hotels) || empty($roomTypes)) {
            echo " Hotels or room types not found. Please run previous seeders first.\n";
            return;
        }

        $totalRooms = array_sum(array_column($hotels, 'total_rooms'));
        $insertedTotal = 0;

        echo "  Total rooms to insert: {$totalRooms}\n";

        $values = [];
        $params = [];
        $batchCount = 0;

        foreach ($hotels as $hotel) {
            $roomsInHotel = (int)$hotel['total_rooms'];
            $hotelStars = (int)$hotel['stars'];

            for ($roomNum = 1; $roomNum <= $roomsInHotel; $roomNum++) {
                $roomType = $this->selectRoomType($roomTypes, $hotelStars);

                $floor = ceil($roomNum / 10);
                $roomNumber = $floor . str_pad(($roomNum % 10) ?: 10, 2, '0', STR_PAD_LEFT);

                $basePrice = $this->generatePrice($roomType['slug'], $floor, $hotelStars);

                $area = $this->generateArea($roomType['slug']);

                $bedsCount = $this->generateBedsCount($roomType['slug']);

                $viewType = $this->viewTypes[array_rand($this->viewTypes)];

                $hasBalcony = $floor > 1 && rand(1, 100) > 60;

                $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                array_push($params,
                    $hotel['id'],
                    $roomType['id'],
                    $roomNumber,
                    $floor,
                    $basePrice,
                    $area,
                    $bedsCount,
                    $hasBalcony,
                    $viewType,
                    true // is_available
                );

                $batchCount++;
                $insertedTotal++;

                if ($batchCount >= self::BATCH_SIZE) {
                    $this->insertBatch($values, $params);
                    $values = [];
                    $params = [];
                    $batchCount = 0;

                    $progress = round(($insertedTotal / $totalRooms) * 100, 1);
                    echo sprintf("\r  Progress: %d/%d rooms (%s%%)", $insertedTotal, $totalRooms, $progress);
                }
            }
        }

        if (!empty($values)) {
            $this->insertBatch($values, $params);
        }

        echo "\n  Inserted {$insertedTotal} rooms\n";
    }

    private function insertBatch(array $values, array $params): void
    {
        $this->pdo->beginTransaction();

        $sql = "INSERT INTO rooms 
                (hotel_id, room_type_id, room_number, floor, base_price, area, 
                 beds_count, has_balcony, view_type, is_available) 
                VALUES " . implode(', ', $values);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $this->pdo->commit();
    }

    private function selectRoomType(array $roomTypes, int $stars): array
    {
        $weights = [
            'standard' => $stars <= 3 ? 60 : 30,
            'deluxe' => 25,
            'suite' => $stars >= 4 ? 20 : 10,
            'penthouse' => $stars == 5 ? 5 : 2,
            'family' => 10,
            'studio' => 8
        ];

        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($weights as $slug => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                foreach ($roomTypes as $type) {
                    if ($type['slug'] === $slug) {
                        return $type;
                    }
                }
            }
        }

        // Fallback
        return $roomTypes[0];
    }

    private function generatePrice(string $typeSlug, int $floor, int $stars): float
    {
        $basePrices = [
            'standard' => 50,
            'deluxe' => 100,
            'suite' => 200,
            'penthouse' => 500,
            'family' => 120,
            'studio' => 80
        ];

        $basePrice = $basePrices[$typeSlug] ?? 50;

        $starMultiplier = 1 + (($stars - 3) * 0.3);

        $floorBonus = ($floor - 1) * 5;

        $variation = rand(-20, 20) / 100;

        $finalPrice = ($basePrice * $starMultiplier + $floorBonus) * (1 + $variation);

        return round($finalPrice, 2);
    }

    private function generateArea(string $typeSlug): int
    {
        $areas = [
            'standard' => [18, 25],
            'deluxe' => [25, 35],
            'suite' => [40, 60],
            'penthouse' => [80, 150],
            'family' => [35, 50],
            'studio' => [22, 30]
        ];

        $range = $areas[$typeSlug] ?? [18, 25];
        return rand($range[0], $range[1]);
    }

    private function generateBedsCount(string $typeSlug): int
    {
        $beds = [
            'standard' => 1,
            'deluxe' => rand(1, 2),
            'suite' => 2,
            'penthouse' => rand(2, 3),
            'family' => rand(2, 3),
            'studio' => 1
        ];

        return $beds[$typeSlug] ?? 1;
    }
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    $config = require __DIR__ . '/../../src/config.php';
    Connection::init($config['database']);
    $pdo = Connection::getInstance();

    $seeder = new RoomsSeeder($pdo);
    $seeder->run();
}