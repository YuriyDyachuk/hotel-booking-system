<?php

require_once __DIR__ . '/../../src/Database/Connection.php';
require_once __DIR__ . '/../../src/Helpers/Timer.php';

use App\Database\Connection;
use App\Helpers\Timer;

class UsersSeeder
{
    private PDO $pdo;
    private const BATCH_SIZE = 1000;
    private const TOTAL_USERS = 100000;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function run(): void
    {
        echo "\n Seeding users (this may take a while)...\n";
        echo str_repeat('-', 80) . "\n";

        Timer::start('users_seeder');

        $this->seedUsers();

        Timer::stop('users_seeder');
        echo "Users seeded successfully!\n\n";
    }

    private function seedUsers(): void
    {
        $totalBatches = ceil(self::TOTAL_USERS / self::BATCH_SIZE);
        $inserted = 0;

        $firstNames = ['John', 'Jane', 'Michael', 'Emily', 'David', 'Sarah', 'Robert', 'Emma',
            'William', 'Olivia', 'James', 'Ava', 'Daniel', 'Sophia', 'Andrew'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller',
            'Davis', 'Rodriguez', 'Martinez', 'Wilson', 'Anderson', 'Taylor'];
        $genders = ['male', 'female', 'other', null];

        for ($batch = 0; $batch < $totalBatches; $batch++) {
            $this->pdo->beginTransaction();

            $values = [];
            $params = [];

            for ($i = 0; $i < self::BATCH_SIZE && $inserted < self::TOTAL_USERS; $i++) {
                $userId = $inserted + 1;

                $firstName = $firstNames[array_rand($firstNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                $email = strtolower("user{$userId}@example.com");
                $phone = '+' . rand(1, 999) . rand(1000000000, 9999999999);
                $dateOfBirth = date('Y-m-d', strtotime('-' . rand(18, 70) . ' years'));
                $gender = $genders[array_rand($genders)];

                $values[] = "(?, ?, ?, ?, ?, ?, ?)";
                array_push($params,
                    $email,
                    password_hash("password{$userId}", PASSWORD_BCRYPT),
                    $firstName,
                    $lastName,
                    $phone,
                    $dateOfBirth,
                    $gender
                );

                $inserted++;
            }

            if (!empty($values)) {
                $sql = "INSERT INTO users 
                        (email, password_hash, first_name, last_name, phone, date_of_birth, gender) 
                        VALUES " . implode(', ', $values);

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            }

            $this->pdo->commit();

            $progress = round(($inserted / self::TOTAL_USERS) * 100, 1);
            echo sprintf("\r  Progress: %d/%d users (%s%%)", $inserted, self::TOTAL_USERS, $progress);
        }

        echo "\n  Inserted {$inserted} users\n";
    }
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    $config = require __DIR__ . '/../../src/config.php';
    Connection::init($config['database']);
    $pdo = Connection::getInstance();

    $seeder = new UsersSeeder($pdo);
    $seeder->run();
}