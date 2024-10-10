<?php

use function Pest\Faker\fake;

function db($uuid = 'ID')
{
    $useUUID = 'UUID' == $uuid ? true : false;
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
        $config['useUUID'] = 'UUID' == $uuid ? true : false;
    }
    return $config;
}

function populateRandomUser($uuid = 'ID')
{
    for ($i = 0; $i < 5; ++$i) {
        $user = db($uuid)->create('user');
        $user->name = fake()->name();
        $user->email = fake()->email();
        $user->dob = fake()->date();
        $user->age = fake()->randomNumber(2, false);
        $user->active = 0 == $i % 2 ? true : false;
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
    $user->active = 0 == fake()->randomNumber(2, false) % 2 ? true : false;
    $user->address = fake()->streetAddress();
    $id = $user->save();

    return $id;
}
