<?php

require_once __DIR__ . '/../../src/Database/Connection.php';
require_once __DIR__ . '/../../src/Database/QueryLogger.php';
require_once __DIR__ . '/../../src/Helpers/Timer.php';

use App\Database\Connection;
use App\Database\QueryLogger;
use App\Helpers\Timer;

class CountriesCitiesSeeder
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function run(): void
    {
        echo "\n Seeding countries and cities...\n";
        echo str_repeat('-', 80) . "\n";

        Timer::start('countries_cities_seeder');

        $this->seedCountries();
        $this->seedCities();

        Timer::stop('countries_cities_seeder');
        echo "Countries and cities seeded successfully!\n\n";
    }

    private function seedCountries(): void
    {
        $countries = [
            ['UA', 'Ukraine'],
            ['US', 'United States'],
            ['GB', 'United Kingdom'],
            ['FR', 'France'],
            ['DE', 'Germany'],
            ['IT', 'Italy'],
            ['ES', 'Spain'],
            ['PL', 'Poland'],
            ['TR', 'Turkey'],
            ['GR', 'Greece'],
            ['PT', 'Portugal'],
            ['NL', 'Netherlands'],
            ['BE', 'Belgium'],
            ['AT', 'Austria'],
            ['CH', 'Switzerland'],
            ['CZ', 'Czech Republic'],
            ['HU', 'Hungary'],
            ['RO', 'Romania'],
            ['BG', 'Bulgaria'],
            ['HR', 'Croatia'],
        ];

        $sql = "INSERT INTO countries (code, name) VALUES (?, ?)";
        $stmt = $this->pdo->prepare($sql);

        $count = 0;
        foreach ($countries as $country) {
            $stmt->execute($country);
            $count++;
        }

        echo " Inserted {$count} countries\n";
    }

    private function seedCities(): void
    {
        $countriesMap = [];
        $stmt = $this->pdo->query("SELECT id, code FROM countries");
        while ($row = $stmt->fetch()) {
            $countriesMap[$row['code']] = $row['id'];
        }

        $cities = [
            ['UA', 'Kyiv', 2900000, true],
            ['UA', 'Lviv', 720000, true],
            ['UA', 'Odesa', 1015000, true],
            ['UA', 'Kharkiv', 1430000, true],
            ['UA', 'Dnipro', 980000, false],

            ['US', 'New York', 8336000, true],
            ['US', 'Los Angeles', 3980000, true],
            ['US', 'Miami', 470000, true],
            ['US', 'Las Vegas', 641000, true],
            ['US', 'San Francisco', 873000, true],

            ['FR', 'Paris', 2165000, true],
            ['FR', 'Nice', 340000, true],
            ['FR', 'Lyon', 516000, true],
            ['FR', 'Marseille', 870000, false],

            ['IT', 'Rome', 2873000, true],
            ['IT', 'Venice', 260000, true],
            ['IT', 'Milan', 1352000, true],
            ['IT', 'Florence', 383000, true],

            ['ES', 'Barcelona', 1620000, true],
            ['ES', 'Madrid', 3223000, true],
            ['ES', 'Valencia', 792000, false],
            ['ES', 'Seville', 688000, false],

            ['DE', 'Berlin', 3645000, true],
            ['DE', 'Munich', 1472000, true],
            ['DE', 'Hamburg', 1841000, false],

            ['GB', 'London', 8982000, true],
            ['GB', 'Manchester', 547000, false],
            ['GB', 'Edinburgh', 488000, true],

            ['PL', 'Warsaw', 1794000, true],
            ['PL', 'Krakow', 779000, true],

            ['TR', 'Istanbul', 15460000, true],
            ['TR', 'Antalya', 1300000, true],

            ['GR', 'Athens', 664000, true],
            ['GR', 'Santorini', 15000, true],

            ['PT', 'Lisbon', 505000, true],
            ['PT', 'Porto', 237000, true],
        ];

        $sql = "INSERT INTO cities (country_id, name, population, is_popular) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);

        $count = 0;
        foreach ($cities as $city) {
            $countryId = $countriesMap[$city[0]] ?? null;
            if ($countryId) {
                $stmt->execute([
                    $countryId,
                    $city[1],
                    $city[2],
                    $city[3]
                ]);
                $count++;
            }
        }

        echo " Inserted {$count} cities\n";
    }
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    $config = require __DIR__ . '/../../src/config.php';
    Connection::init($config['database']);
    $pdo = Connection::getInstance();

    $seeder = new CountriesCitiesSeeder($pdo);
    $seeder->run();
}