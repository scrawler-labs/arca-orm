<?php

use function Pest\Faker\fake;

// Include shared test helpers
require_once __DIR__.'/TestHelpers.php';

// Include datasets
require_once __DIR__.'/Datasets/DatabaseTest.php';
require_once __DIR__.'/TestDB.php';


function db($uuid = 'ID')
{
    $useUUID = 'UUID' == $uuid;
    

    if ($useUUID) {
        return TestDB::getUuidInstance();
    } else {
        return TestDB::getIdInstance();
    }
}

 function getConnectionParams($useUUID = 'ID'): array
    {
        $dbConnection = $_ENV['DB_CONNECTION'] ?? 'sqlite';

        $config = match ($dbConnection) {
            'mysql' => [
                'dbname' => $_ENV['DB_DATABASE'] ?? 'test',
                'user' => $_ENV['DB_USERNAME'] ?? 'arca_user',
                'password' => $_ENV['DB_PASSWORD'] ?? 'arca_pass',
                'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
                'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
                'driver' => 'pdo_mysql',
            ],
            'pgsql' => [
                'dbname' => $_ENV['DB_DATABASE'] ?? 'test',
                'user' => $_ENV['DB_USERNAME'] ?? 'arca_user',
                'password' => $_ENV['DB_PASSWORD'] ?? 'arca_pass',
                'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
                'port' => (int) ($_ENV['DB_PORT'] ?? 5432),
                'driver' => 'pdo_pgsql',
            ],
            'sqlite' => [
                'path' => $_ENV['DB_DATABASE'] ?? ':memory:',
                'driver' => 'pdo_sqlite',
            ],
            default => throw new InvalidArgumentException("Unsupported database connection: {$dbConnection}")
        };

        $config['useUUID'] = 'UUID' === $useUUID;

        return $config;
    }


function populateRandomUser($uuid = 'ID'): void
{
    for ($i = 0; $i < 5; ++$i) {
        $user = db($uuid)->create('user');
        $user->name = fake()->name();
        $user->email = fake()->email();
        $user->dob = fake()->date();
        $user->age = fake()->randomNumber(2, false);
        $user->active = $i % 2;
        $user->address = fake()->streetAddress();
        $user->save();
    }
}

function createRandomUser($uuid = 'ID')
{
    $user = db($uuid)->create('user');
    $user->name = fake()->name();
    $user->email = fake()->email();
    $user->dob = fake()->date();
    $user->age = fake()->randomNumber(2, false);
    $user->active = fake()->randomNumber(2, false) % 2;
    $user->address = fake()->streetAddress();

    return $user->save();
}
