<?php
use function Pest\Faker\faker;

function db()
{
    $connectionParams = array(
        'dbname' => 'test_database',
        'user' => 'admin',
        'password' => 'rootpass',
        'host' => '127.0.0.1',
        'driver' => 'pdo_mysql',
    );

    return new \Scrawler\Arca\Database($connectionParams);
}

function populateRandomUser()
{
    for ($i=0;$i<5;$i++) {
        $user = db()->create('user');
        $user->name = faker()->name;
        $user->email = faker()->email;
        $user->dob = faker()->date;
        $user->age = faker()->randomNumber(2, false);
        $user->active = $i%2==0 ? true : false;
        $user->address = faker()->streetAddress();
        $user->save();
    }
}
