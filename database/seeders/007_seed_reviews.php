<?php

require_once __DIR__ . '/../../src/Database/Connection.php';
require_once __DIR__ . '/../../src/Helpers/Timer.php';

use App\Database\Connection;
use App\Helpers\Timer;

class ReviewsSeeder
{
    private PDO $pdo;
    private const BATCH_SIZE = 1000;
    private const REVIEW_PROBABILITY = 30;

    private array $reviewTitles = [
        'Excellent stay!',
        'Great experience',
        'Highly recommended',
        'Perfect location',
        'Amazing hotel',
        'Wonderful service',
        'Good value for money',
        'Nice and clean',
        'Disappointing',
        'Could be better',
        'Average experience',
        'Not worth the price',
        'Comfortable stay',
        'Beautiful rooms',
        'Friendly staff'
    ];

    private array $positivePros = [
        'Excellent location in city center',
        'Very clean and comfortable rooms',
        'Friendly and helpful staff',
        'Great breakfast options',
        'Beautiful views',
        'Modern facilities',
        'Good value for money',
        'Quiet and peaceful',
        'Close to attractions',
        'Spacious rooms'
    ];

    private array $negativeCons = [
        'Rooms could be cleaner',
        'Noise from the street',
        'WiFi connection issues',
        'Limited parking',
        'Breakfast not included',
        'Small bathroom',
        'Outdated furniture',
        'Poor soundproofing',
        'Slow check-in process',
        'Limited amenities'
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function run(): void
    {
        echo "\n Seeding reviews...\n";
        echo str_repeat('-', 80) . "\n";

        Timer::start('reviews_seeder');

        $this->seedReviews();

        Timer::stop('reviews_seeder');
        echo "Reviews seeded successfully!\n\n";
    }

    private function seedReviews(): void
    {
        echo "  Fetching completed bookings...\n";

        $stmt = $this->pdo->query("
            SELECT b.id, b.user_id, r.hotel_id
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            WHERE b.status = 'completed'
            ORDER BY RAND()
        ");

        $completedBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalBookings = count($completedBookings);

        if ($totalBookings == 0) {
            echo "No completed bookings found.\n";
            return;
        }

        $reviewsToCreate = (int)($totalBookings * (self::REVIEW_PROBABILITY / 100));

        echo "  Completed bookings: {$totalBookings}\n";
        echo "  Reviews to create: {$reviewsToCreate}\n\n";

        $inserted = 0;
        $values = [];
        $params = [];
        $batchCount = 0;

        shuffle($completedBookings);

        for ($i = 0; $i < $reviewsToCreate && $i < $totalBookings; $i++) {
            $booking = $completedBookings[$i];

            $ratings = $this->generateRatings();

            $title = $this->reviewTitles[array_rand($this->reviewTitles)];
            $comment = $this->generateComment($ratings['overall_rating']);
            $pros = $ratings['overall_rating'] >= 4 ? $this->positivePros[array_rand($this->positivePros)] : null;
            $cons = $ratings['overall_rating'] <= 3 ? $this->negativeCons[array_rand($this->negativeCons)] : null;

            $isVisible = rand(1, 100) <= 80;
            $isVerified = rand(1, 100) <= 90;
            $helpfulCount = rand(0, 50);

            $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            array_push($params,
                $booking['hotel_id'],
                $booking['user_id'],
                $booking['id'],
                $ratings['overall_rating'],
                $ratings['cleanliness_rating'],
                $ratings['staff_rating'],
                $ratings['location_rating'],
                $ratings['value_rating'],
                $ratings['comfort_rating'],
                $title,
                $comment,
                $pros,
                $cons,
                $isVerified,
                $isVisible
            );

            $batchCount++;
            $inserted++;

            if ($batchCount >= self::BATCH_SIZE) {
                $this->insertBatch($values, $params);
                $values = [];
                $params = [];
                $batchCount = 0;

                $progress = round(($inserted / $reviewsToCreate) * 100, 1);
                echo sprintf("\r  Progress: %d/%d reviews (%s%%)", $inserted, $reviewsToCreate, $progress);
            }
        }

        if (!empty($values)) {
            $this->insertBatch($values, $params);
        }

        echo "\n Inserted {$inserted} reviews\n";

        $this->updateHotelRatings();
    }

    private function insertBatch(array $values, array $params): void
    {
        $this->pdo->beginTransaction();

        $sql = "INSERT INTO reviews 
                (hotel_id, user_id, booking_id, overall_rating, 
                 cleanliness_rating, staff_rating, location_rating, value_rating, comfort_rating,
                 title, comment, pros, cons, is_verified, is_visible) 
                VALUES " . implode(', ', $values);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $this->pdo->commit();
    }

    private function generateRatings(): array
    {
        $overallWeights = [1 => 5, 2 => 10, 3 => 20, 4 => 35, 5 => 30];
        $overall = $this->weightedRandom($overallWeights);

        $generateRelated = function() use ($overall) {
            $variation = rand(-1, 1);
            return max(1, min(5, $overall + $variation));
        };

        return [
            'overall_rating' => $overall,
            'cleanliness_rating' => $generateRelated(),
            'staff_rating' => $generateRelated(),
            'location_rating' => $generateRelated(),
            'value_rating' => $generateRelated(),
            'comfort_rating' => $generateRelated()
        ];
    }

    private function generateComment(int $rating): string
    {
        $positiveComments = [
            "Had a wonderful stay at this hotel. Everything was perfect!",
            "Great location, clean rooms, and excellent service. Highly recommend!",
            "One of the best hotels I've stayed at. Will definitely come back.",
            "The staff was incredibly helpful and the room was spotless.",
            "Amazing experience from check-in to check-out. Thank you!"
        ];

        $neutralComments = [
            "Decent hotel for the price. Nothing special but got the job done.",
            "Average experience. The room was okay, location was convenient.",
            "It was fine for a short stay. Some things could be improved.",
            "Good location but the room was smaller than expected."
        ];

        $negativeComments = [
            "Disappointed with the stay. Room was not clean and service was poor.",
            "Not worth the money. Expected much better for this price.",
            "Had several issues during our stay. Would not recommend.",
            "The pictures online were misleading. Room was outdated."
        ];

        if ($rating >= 4) {
            return $positiveComments[array_rand($positiveComments)];
        } elseif ($rating == 3) {
            return $neutralComments[array_rand($neutralComments)];
        } else {
            return $negativeComments[array_rand($negativeComments)];
        }
    }

    private function updateHotelRatings(): void
    {
        echo "  Updating hotel ratings...\n";

        $sql = "
            UPDATE hotels h
            SET rating = (
                SELECT AVG(r.overall_rating)
                FROM reviews r
                WHERE r.hotel_id = h.id AND r.is_visible = 1
            )
            WHERE EXISTS (
                SELECT 1 FROM reviews r WHERE r.hotel_id = h.id
            )
        ";

        $this->pdo->exec($sql);

        echo "Hotel ratings updated\n";
    }

    private function weightedRandom(array $weights): int
    {
        $rand = rand(1, array_sum($weights));
        $cumulative = 0;

        foreach ($weights as $value => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $value;
            }
        }

        return array_key_first($weights);
    }
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    $config = require __DIR__ . '/../../src/config.php';
    Connection::init($config['database']);
    $pdo = Connection::getInstance();

    $seeder = new ReviewsSeeder($pdo);
    $seeder->run();
}