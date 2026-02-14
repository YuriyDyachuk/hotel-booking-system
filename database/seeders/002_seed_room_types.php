<?php

require_once __DIR__ . '/../../src/Database/Connection.php';
require_once __DIR__ . '/../../src/Helpers/Timer.php';

use App\Database\Connection;
use App\Helpers\Timer;

class RoomTypesSeeder
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function run(): void
    {
        echo "\n Seeding room types...\n";
        echo str_repeat('-', 80) . "\n";

        Timer::start('room_types_seeder');

        $roomTypes = [
            [
                'name' => 'Standard',
                'slug' => 'standard',
                'max_guests' => 2,
                'description' => 'Comfortable room with basic amenities, perfect for budget travelers',
                'amenities' => json_encode([
                    'WiFi', 'TV', 'Air Conditioning', 'Private Bathroom', 'Toiletries'
                ])
            ],
            [
                'name' => 'Deluxe',
                'slug' => 'deluxe',
                'max_guests' => 3,
                'description' => 'Spacious room with enhanced comfort and premium amenities',
                'amenities' => json_encode([
                    'WiFi', 'Smart TV', 'Air Conditioning', 'Minibar', 'Coffee Machine',
                    'Bathrobe', 'Slippers', 'Premium Toiletries'
                ])
            ],
            [
                'name' => 'Suite',
                'slug' => 'suite',
                'max_guests' => 4,
                'description' => 'Luxurious suite with separate living area and premium services',
                'amenities' => json_encode([
                    'WiFi', 'Smart TV', 'Air Conditioning', 'Minibar', 'Nespresso Machine',
                    'Living Room', 'Work Desk', 'Bathrobe', 'Slippers', 'Premium Toiletries',
                    'Room Service', 'Daily Cleaning'
                ])
            ],
            [
                'name' => 'Penthouse',
                'slug' => 'penthouse',
                'max_guests' => 6,
                'description' => 'Top-floor luxury apartment with panoramic views and exclusive services',
                'amenities' => json_encode([
                    'WiFi', 'Smart TV', 'Air Conditioning', 'Full Kitchen', 'Minibar',
                    'Nespresso Machine', 'Living Room', 'Dining Area', 'Multiple Bathrooms',
                    'Jacuzzi', 'Terrace', 'Bathrobe', 'Slippers', 'Premium Toiletries',
                    'Butler Service', 'Private Check-in', 'Complimentary Breakfast'
                ])
            ],
            [
                'name' => 'Family Room',
                'slug' => 'family',
                'max_guests' => 5,
                'description' => 'Large room designed for families with children',
                'amenities' => json_encode([
                    'WiFi', 'TV', 'Air Conditioning', 'Minibar', 'Baby Cot Available',
                    'Children Amenities', 'Extra Beds', 'Board Games'
                ])
            ],
            [
                'name' => 'Studio',
                'slug' => 'studio',
                'max_guests' => 2,
                'description' => 'Compact room with kitchenette, ideal for long stays',
                'amenities' => json_encode([
                    'WiFi', 'TV', 'Air Conditioning', 'Kitchenette', 'Microwave',
                    'Refrigerator', 'Dining Table', 'Work Desk'
                ])
            ]
        ];

        $sql = "INSERT INTO room_types (name, slug, max_guests, description, amenities) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);

        $count = 0;
        foreach ($roomTypes as $type) {
            $stmt->execute([
                $type['name'],
                $type['slug'],
                $type['max_guests'],
                $type['description'],
                $type['amenities']
            ]);
            $count++;
        }

        Timer::stop('room_types_seeder');
        echo " Inserted {$count} room types\n";
        echo " Room types seeded successfully!\n\n";
    }
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    $config = require __DIR__ . '/../../src/config.php';
    Connection::init($config['database']);
    $pdo = Connection::getInstance();

    $seeder = new RoomTypesSeeder($pdo);
    $seeder->run();
}