<?php

require_once __DIR__ . '/../../src/Database/Connection.php';
require_once __DIR__ . '/../../src/Helpers/Timer.php';

use App\Database\Connection;
use App\Helpers\Timer;

class BookingsSeeder
{
    private PDO $pdo;
    private const BATCH_SIZE = 1000;
    private const TOTAL_BOOKINGS = 2000000;

    private array $statuses = ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'];
    private array $paymentStatuses = ['unpaid', 'partial', 'paid', 'refunded'];

    private int $userCount;
    private int $roomCount;
    private int $currentYear;
    private int $bookingCounter = 1;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->currentYear = date('Y');
    }

    public function run(): void
    {
        echo "\n Seeding bookings (this will take several minutes)...\n";
        echo str_repeat('-', 80) . "\n";

        Timer::start('bookings_seeder');

        $this->userCount = (int)$this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $this->roomCount = (int)$this->pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();

        if ($this->userCount == 0 || $this->roomCount == 0) {
            echo "  No users or rooms found. Please run previous seeders first.\n";
            return;
        }

        echo "  Users available: {$this->userCount}\n";
        echo "  Rooms available: {$this->roomCount}\n";
        echo "  Target bookings: " . number_format(self::TOTAL_BOOKINGS) . "\n\n";

        $this->seedBookings();

        Timer::stop('bookings_seeder');
        echo "Bookings seeded successfully!\n\n";
    }

    private function seedBookings(): void
    {
        $totalBatches = ceil(self::TOTAL_BOOKINGS / self::BATCH_SIZE);
        $inserted = 0;

        $roomsInfo = $this->getRoomsInfo();

        for ($batch = 0; $batch < $totalBatches; $batch++) {
            $this->pdo->beginTransaction();

            $values = [];
            $params = [];

            for ($i = 0; $i < self::BATCH_SIZE && $inserted < self::TOTAL_BOOKINGS; $i++) {
                $userId = rand(1, $this->userCount);
                $roomId = rand(1, $this->roomCount);

                $basePrice = $roomsInfo[$roomId] ?? 100;

                $dates = $this->generateDates();
                $checkIn = $dates['check_in'];
                $checkOut = $dates['check_out'];
                $nights = $dates['nights'];

                $adultsCount = rand(1, 3);
                $childrenCount = rand(0, 2);
                $guestsCount = $adultsCount + $childrenCount;

                $pricing = $this->calculatePrice($basePrice, $nights, $checkIn);

                $statusInfo = $this->determineStatus($checkIn, $checkOut);

                $bookingNumber = $this->generateBookingNumber();

                $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                array_push($params,
                    $bookingNumber,
                    $userId,
                    $roomId,
                    $checkIn,
                    $checkOut,
                    $guestsCount,
                    $adultsCount,
                    $childrenCount,
                    $pricing['base_price'],
                    $pricing['discount'],
                    $pricing['taxes'],
                    $pricing['total_price'],
                    $statusInfo['status'],
                    $statusInfo['payment_status']
                );

                $inserted++;
            }

            if (!empty($values)) {
                $sql = "INSERT INTO bookings 
                        (booking_number, user_id, room_id, check_in, check_out, 
                         guests_count, adults_count, children_count,
                         base_price, discount, taxes, total_price, status, payment_status) 
                        VALUES " . implode(', ', $values);

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            }

            $this->pdo->commit();

            if ($inserted > 0 && $inserted % 10000 == 0) {
                $progress = round(($inserted / self::TOTAL_BOOKINGS) * 100, 1);
                $memoryUsage = round(memory_get_usage() / 1024 / 1024, 2);
                echo sprintf(
                    "\r  Progress: %s/%s bookings (%s%%) | Memory: %s MB",
                    number_format($inserted),
                    number_format(self::TOTAL_BOOKINGS),
                    $progress,
                    $memoryUsage
                );
            }
        }

        echo "\n Inserted " . number_format($inserted) . " bookings\n";
    }

    private function getRoomsInfo(): array
    {
        $stmt = $this->pdo->query("SELECT id, base_price FROM rooms");
        $rooms = [];

        while ($row = $stmt->fetch()) {
            $rooms[$row['id']] = (float)$row['base_price'];
        }

        return $rooms;
    }

    private function generateDates(): array
    {
        $rand = rand(1, 100);

        if ($rand <= 30) {
            $year = $this->currentYear - 1;
            $startDay = 1;
            $endDay = 365;
        } elseif ($rand <= 70) {
            $year = $this->currentYear;
            $startDay = 1;
            $endDay = date('z') + 180;
        } else {
            $year = $this->currentYear + 1;
            $startDay = 1;
            $endDay = 180;
        }

        $dayOfYear = rand($startDay, $endDay);
        $checkIn = date('Y-m-d', strtotime("{$year}-01-01 +{$dayOfYear} days"));

        $weights = [1 => 10, 2 => 25, 3 => 25, 4 => 15, 5 => 10, 7 => 8, 10 => 5, 14 => 2];
        $nights = $this->weightedRandom($weights);

        $checkOut = date('Y-m-d', strtotime("{$checkIn} +{$nights} days"));

        return [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'nights' => $nights
        ];
    }

    private function calculatePrice(float $basePrice, int $nights, string $checkIn): array
    {
        $totalBase = $basePrice * $nights;

        $month = (int)date('m', strtotime($checkIn));
        $seasonMultiplier = $this->getSeasonMultiplier($month);
        $totalBase *= $seasonMultiplier;

        $discount = 0;
        if (rand(1, 100) <= 10) {
            $discount = $totalBase * (rand(5, 25) / 100);
        }

        $taxes = ($totalBase - $discount) * 0.10;

        $totalPrice = $totalBase - $discount + $taxes;

        return [
            'base_price' => round($totalBase, 2),
            'discount' => round($discount, 2),
            'taxes' => round($taxes, 2),
            'total_price' => round($totalPrice, 2)
        ];
    }

    private function getSeasonMultiplier(int $month): float
    {
        if (in_array($month, [6, 7, 8, 12])) {
            return 1.3;
        }
        if (in_array($month, [5, 9, 11])) {
            return 1.1;
        }
        return 0.9;
    }

    private function determineStatus(string $checkIn, string $checkOut): array
    {
        $today = date('Y-m-d');
        $checkInDate = strtotime($checkIn);
        $checkOutDate = strtotime($checkOut);
        $todayTime = strtotime($today);

        if ($checkOutDate < $todayTime) {
            $statusWeights = [
                'completed' => 80,
                'cancelled' => 15,
                'no_show' => 5
            ];
            $status = $this->weightedRandomFromArray($statusWeights);
            $paymentStatus = $status === 'completed' ? 'paid' :
                ($status === 'cancelled' ? 'refunded' : 'unpaid');
        }
        elseif ($checkInDate <= $todayTime && $checkOutDate >= $todayTime) {
            $status = 'confirmed';
            $paymentStatus = 'paid';
        }
        else {
            $statusWeights = [
                'confirmed' => 70,
                'pending' => 25,
                'cancelled' => 5
            ];
            $status = $this->weightedRandomFromArray($statusWeights);

            $paymentStatusWeights = [
                'paid' => 50,
                'partial' => 30,
                'unpaid' => 20
            ];
            $paymentStatus = $status === 'cancelled' ? 'refunded' :
                $this->weightedRandomFromArray($paymentStatusWeights);
        }

        return [
            'status' => $status,
            'payment_status' => $paymentStatus
        ];
    }

    private function generateBookingNumber(): string
    {
        $number = str_pad($this->bookingCounter, 8, '0', STR_PAD_LEFT);
        $this->bookingCounter++;
        return "BK-{$this->currentYear}-{$number}";
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

    private function weightedRandomFromArray(array $weights): string
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

    $seeder = new BookingsSeeder($pdo);
    $seeder->run();
}