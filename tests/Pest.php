<?php

use function Pest\Faker\fake;

// Include shared test helpers
require_once __DIR__.'/TestHelpers.php';

// Include datasets
require_once __DIR__.'/Datasets/DatabaseTest.php';

function db($uuid = 'ID')
{
    $useUUID = 'UUID' == $uuid;
    static $dbUUID = Scrawler\Arca\Facade\Database::connect(getConnectionParams('UUID'));

    static $dbID = Scrawler\Arca\Facade\Database::connect(getConnectionParams('ID'));

    if ($useUUID) {
        return $dbUUID;
    } else {
        return $dbID;
    }
}

function getConnectionParams($uuid = 'ID', $withUUID = true): array
{
    $config = [
        'dbname' => 'test_database',
        'user' => 'admin',
        'password' => 'rootpass',
        'host' => '127.0.0.1',
        'driver' => 'pdo_mysql',
    ];
    if ($withUUID) {
        $config['useUUID'] = 'UUID' == $uuid;
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
