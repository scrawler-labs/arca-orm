<?php
use function Pest\Faker\faker;

// it("check performance", function () {
//     db()->connection->query("DROP TABLE IF EXISTS user; ");
//     db()->connection->query("DROP TABLE IF EXISTS parent; ");
//     $time_start = microtime(true);
//     for ($i=1;$i<100;$i++) {
//         create();
//     }
//     $time_end = microtime(true);
//     $execution_time = ($time_end - $time_start)/60;
//     print('time taken:'. (string) $execution_time);
// });

function create()
{
    $user = db()->create('user');
    $user->name = faker()->name;
    $user->email = faker()->email;
    $user->dob = faker()->date;
    $user->age = faker()->randomNumber(2, false);
    $user->address = faker()->streetAddress();

    $user_two = db()->create('user');
    $user_two->name = faker()->name;
    $user_two->email = faker()->email;
    $user_two->dob = faker()->date;
    $user_two->age = faker()->randomNumber(2, false);
    $user_two->address = faker()->streetAddress();
    //$id = $user->save();

    $parent = db()->create('parent');
    $parent->name = faker()->name;
    $parent->ownUserList = [$user,$user_two];
    $id = $parent->save();
}
