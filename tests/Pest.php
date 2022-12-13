<?php
use function Pest\Faker\faker;

function db($uuid = "ID")
{
    $connectionParams = array(
        'dbname' => 'test_database',
        'user' => 'admin',
        'password' => 'rootpass',
        'host' => '127.0.0.1',
        'driver' => 'pdo_mysql',
    );

    $db = Scrawler\Arca\Facade\Database::connect($connectionParams);

    if ($uuid == 'UUID') {
        $db->useUUID();
        return $db;
    }
    $db->useID();
    return $db;
}


function populateRandomUser($uuid = 'ID')
{
    for ($i = 0; $i < 5; $i++) {
        $user = db($uuid)->create('user');
        $user->name = faker()->name;
        $user->email = faker()->email;
        $user->dob = faker()->date;
        $user->age = faker()->randomNumber(2, false);
        $user->active = $i % 2 == 0 ? true : false;
        $user->address = faker()->streetAddress();
        $user->save();
    }
}

function createRandomUser($uuid = 'ID')
{
    $user = db($uuid)->create('user');
    $user->name = faker()->name;
    $user->email = faker()->email;
    $user->dob = faker()->date;
    $user->age = faker()->randomNumber(2, false);
    $user->active = faker()->randomNumber(2, false) % 2 == 0 ? true : false;
    $user->address = faker()->streetAddress();
    $id = $user->save();
    return $id;
}