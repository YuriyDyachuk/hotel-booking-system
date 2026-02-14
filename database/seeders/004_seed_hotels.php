<?php

require_once __DIR__ . '/../../src/Database/Connection.php';
require_once __DIR__ . '/../../src/Helpers/Timer.php';

use App\Database\Connection;
use App\Helpers\Timer;

class HotelsSeeder
{
    private PDO $pdo;
    private const BATCH_SIZE = 100;
    private const TOTAL_HOTELS = 2000;

    private array $hotelNames = [
        'Grand', 'Royal', 'Imperial', 'Continental', 'Plaza', 'Palace', 'Ritz',
        'Hilton', 'Marriott', 'Hyatt', 'Sheraton', 'Renaissance', 'Intercontinental',
        'Luxury', 'Premium', 'Elite', 'Golden', 'Silver', 'Crystal', 'Diamond',
        'Central', 'Downtown', 'Riverside', 'Seaside', 'Mountain View', 'Park',
        'Boutique', 'Comfort', 'Business', 'Airport', 'City', 'Metro'
    ];

    private array $hotelTypes = [
        'Hotel', 'Resort', 'Inn', 'Suites', 'Lodge', 'Residences', 'Apartments'
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function run(): void
    {
        echo "\n Seeding hotels...\n";
        echo str_repeat('-', 80) . "\n";

        Timer::start('hotels_seeder');

        $this->seedHotels();

        Timer::stop('hotels_seeder');
        echo "Hotels seeded successfully!\n\n";
    }

    private function seedHotels(): void
    {
        $cities = $this->pdo->query("
            SELECT c.id, c.name, c.is_popular, co.name as country 
            FROM cities c 
            JOIN countries co ON c.country_id = co.id
        ")->fetchAll(PDO::FETCH_ASSOC);

        if (empty($cities)) {
            echo " No cities found. Please run cities seeder first.\n";
            return;
        }

        $totalBatches = ceil(self::TOTAL_HOTELS / self::BATCH_SIZE);
        $inserted = 0;

        for ($batch = 0; $batch < $totalBatches; $batch++) {
            $this->pdo->beginTransaction();

            $values = [];
            $params = [];

            for ($i = 0; $i < self::BATCH_SIZE && $inserted < self::TOTAL_HOTELS; $i++) {
                $hotelNum = $inserted + 1;

                $city = $cities[array_rand($cities)];

                $hotelName = $this->generateHotelName($city['name']);

                $address = $this->generateAddress($hotelNum);

                $description = $this->generateDescription($hotelName, $city['name'], $city['country']);

                $stars = $city['is_popular'] && rand(1, 100) > 30
                    ? rand(4, 5)
                    : rand(3, 5);

                $rating = $this->generateRating($stars);

                $totalRooms = $this->generateRoomsCount($stars);

                $email = strtolower(str_replace(' ', '', $hotelName)) . '@hotel.com';
                $phone = '+' . rand(1, 999) . '-' . rand(100, 999) . '-' . rand(1000, 9999);
                $website = 'www.' . strtolower(str_replace(' ', '', $hotelName)) . '.com';

                $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                array_push($params,
                    $city['id'],
                    $hotelName,
                    $address,
                    $description,
                    $stars,
                    $rating,
                    $totalRooms,
                    $email,
                    $phone,
                    $website
                );

                $inserted++;
            }

            if (!empty($values)) {
                $sql = "INSERT INTO hotels 
                        (city_id, name, address, description, stars, rating, total_rooms, 
                         email, phone, website) 
                        VALUES " . implode(', ', $values);

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            }

            $this->pdo->commit();

            $progress = round(($inserted / self::TOTAL_HOTELS) * 100, 1);
            echo sprintf("\r  Progress: %d/%d hotels (%s%%)", $inserted, self::TOTAL_HOTELS, $progress);
        }

        echo "\n  Inserted {$inserted} hotels\n";
    }

    private function generateHotelName(string $cityName): string
    {
        $prefix = $this->hotelNames[array_rand($this->hotelNames)];
        $type = $this->hotelTypes[array_rand($this->hotelTypes)];

        $formats = [
            "{$prefix} {$type}",
            "{$cityName} {$prefix} {$type}",
            "{$prefix} {$cityName} {$type}",
            "The {$prefix} {$type}",
        ];

        return $formats[array_rand($formats)];
    }

    private function generateAddress(int $num): string
    {
        $streets = [
            'Main Street', 'Central Avenue', 'Park Lane', 'River Road', 'Hill Street',
            'Broadway', 'Market Street', 'Station Road', 'High Street', 'Beach Boulevard'
        ];

        $street = $streets[array_rand($streets)];
        $building = rand(1, 500);

        return "{$building} {$street}";
    }

    private function generateDescription(string $hotelName, string $city, string $country): string
    {
        $descriptions = [
            "Located in the heart of {$city}, {$hotelName} offers luxurious accommodations with modern amenities.",
            "Experience exceptional hospitality at {$hotelName}, your perfect retreat in {$city}, {$country}.",
            "{$hotelName} combines comfort and elegance, providing guests with an unforgettable stay in {$city}.",
            "Discover the perfect blend of luxury and convenience at {$hotelName}, situated in beautiful {$city}.",
            "Welcome to {$hotelName}, where comfort meets style in the vibrant city of {$city}."
        ];

        return $descriptions[array_rand($descriptions)];
    }

    private function generateRating(int $stars): float
    {
        $baseRating = [
            3 => 3.5,
            4 => 4.0,
            5 => 4.5
        ];

        $base = $baseRating[$stars] ?? 3.5;
        $variation = (rand(0, 50) - 25) / 100; // ±0.25

        return round(max(1, min(5, $base + $variation)), 2);
    }

    private function generateRoomsCount(int $stars): int
    {
        $ranges = [
            3 => [20, 50],
            4 => [40, 80],
            5 => [50, 120]
        ];

        $range = $ranges[$stars] ?? [20, 50];
        return rand($range[0], $range[1]);
    }
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    $config = require __DIR__ . '/../../src/config.php';
    Connection::init($config['database']);
    $pdo = Connection::getInstance();

    $seeder = new HotelsSeeder($pdo);
    $seeder->run();
}