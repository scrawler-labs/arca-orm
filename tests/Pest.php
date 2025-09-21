<?php

use function Pest\Faker\fake;

// Include shared test helpers
require_once __DIR__.'/TestHelpers.php';

// Include datasets
require_once __DIR__.'/Datasets/DatabaseTest.php';
require_once __DIR__.'/IdDB.php';
require_once __DIR__.'/UuidDB.php';


function db($uuid = 'ID')
{
    $useUUID = 'UUID' == $uuid;
    

    if ($useUUID) {
        return UuidDB::getInstance();
    } else {
        return IdDB::getInstance();
    }
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
