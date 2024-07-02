<?php
use function Pest\Faker\fake;
 
function db($uuid='ID')
{
    $useUUID = $uuid == 'UUID' ? true : false;
    static $dbUUID = Scrawler\Arca\Facade\Database::connect([
        'dbname' => 'test_database',
        'user' => 'admin',
        'password' => 'rootpass',
        'host' => '127.0.0.1',
        'driver' => 'pdo_mysql',
        'useUUID' => true,
    ]);

    static $dbID = Scrawler\Arca\Facade\Database::connect([
        'dbname' => 'test_database',
        'user' => 'admin',
        'password' => 'rootpass',
        'host' => '127.0.0.1',
        'driver' => 'pdo_mysql',
        'useUUID' => false,
    ]);

    if ($useUUID) {
        return $dbUUID;
    } else {
        return $dbID;
    }
}


function populateRandomUser($uuid = 'ID')
{
    for ($i = 0; $i < 5; $i++) {
        $user = db($uuid)->create('user');
        $user->name = fake()->name();
        $user->email = fake()->email();
        $user->dob = fake()->date();
        $user->age = fake()->randomNumber(2, false);
        $user->active = $i % 2 == 0 ? true : false;
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
    $user->active = fake()->randomNumber(2, false) % 2 == 0 ? true : false;
    $user->address = fake()->streetAddress();
    $id = $user->save();
    return $id;
}
