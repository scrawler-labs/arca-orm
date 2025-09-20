<?php

use function Pest\Faker\fake;

// Include shared test helpers
require_once __DIR__.'/TestHelpers.php';

// Include datasets
require_once __DIR__.'/Datasets/DatabaseTest.php';

function db($uuid = 'ID')
{
    $useUUID = 'UUID' == $uuid;
    static $dbUUID = null;
    static $dbID = null;

    if ($useUUID) {
        if ($dbUUID === null) {
            $dbUUID = Scrawler\Arca\Facade\Database::connect(getConnectionParams('UUID'));
        }
        return $dbUUID;
    } else {
        if ($dbID === null) {
            $dbID = Scrawler\Arca\Facade\Database::connect(getConnectionParams('ID'));
        }
        return $dbID;
    }
}

function getConnectionParams($uuid = 'ID', $withUUID = true): array
{
    $dbConnection = $_ENV['DB_CONNECTION'] ?? 'mysql';
    
    $config = match($dbConnection) {
        'mysql' => [
            'dbname' => $_ENV['DB_DATABASE'] ?? 'test',
            'user' => $_ENV['DB_USERNAME'] ?? 'arca_user',
            'password' => $_ENV['DB_PASSWORD'] ?? 'arca_pass',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port' => (int)($_ENV['DB_PORT'] ?? 3306),
            'driver' => 'pdo_mysql',
        ],
        'pgsql' => [
            'dbname' => $_ENV['DB_DATABASE'] ?? 'test',
            'user' => $_ENV['DB_USERNAME'] ?? 'arca_user',
            'password' => $_ENV['DB_PASSWORD'] ?? 'arca_pass',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port' => (int)($_ENV['DB_PORT'] ?? 5432),
            'driver' => 'pdo_pgsql',
        ],
        'sqlite' => [
            'path' => $_ENV['DB_DATABASE'] ?? ':memory:',
            'driver' => 'pdo_sqlite',
        ],
        default => throw new InvalidArgumentException("Unsupported database connection: {$dbConnection}")
    };
    
    if ($withUUID) {
        // Handle different ways of specifying UUID mode
        $useUUID = match($uuid) {
            'UUID', true => true,
            'ID', 'false', false => false,
            default => false
        };
        $config['useUUID'] = $useUUID;
    }

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
