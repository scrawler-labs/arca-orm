<?php

use function Pest\Faker\fake;

covers(Scrawler\Arca\QueryBuilder::class);
covers(Scrawler\Arca\Manager\ModelManager::class);

beforeEach(function () {
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS user; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS parent; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS parent_user; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS grandparent; ');
});

it('checks if db()->find()->with() eager loads relation', function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->email = fake()->email();
    $user->dob = fake()->date();
    $user->age = fake()->randomNumber(2, false);
    $user->address = fake()->streetAddress();
    // $user->save();

    $parent = db($useUUID)->create('parent');
    $parent->name = fake()->name();
    $parent->user = $user;
    $parent->save();

    $parent_retrived = db($useUUID)->find('parent')->with('user')->first();
    $user = db($useUUID)->find('user')->first();

    $this->assertJsonStringEqualsJsonString(
        $parent_retrived->user->toString(),
        $user->toString()
    );
})->with('useUUID');

it('checks if db()->find()->with() eager loads multiple realtions', function ($useUUID) {
    $child1 = db($useUUID)->create('user');
    $child1->name = fake()->name();
    $child1->email = fake()->email();
    $child1->dob = fake()->date();
    $child1->age = fake()->randomNumber(2, false);

    $child2 = db($useUUID)->create('user');
    $child2->name = fake()->name();
    $child2->email = fake()->email();
    $child2->dob = fake()->date();
    $child2->age = fake()->randomNumber(2, false);

    $child3 = db($useUUID)->create('user');
    $child3->name = fake()->name();
    $child3->email = fake()->email();
    $child3->dob = fake()->date();
    $child3->age = fake()->randomNumber(2, false);

    $grandfater = db($useUUID)->create('grandparent');
    $grandfater->name = fake()->name();
    $grandfater->email = fake()->email();
    $grandfater->dob = fake()->date();
    $grandfater->age = fake()->randomNumber(2, false);

    $parent = db($useUUID)->create('parent');
    $parent->name = fake()->name();
    $parent->grandparent = $grandfater;
    $parent->ownUserList = [$child1, $child2];
    $parent->sharedUserList = [$child3];
    $id = $parent->save();

    $parent_retrived = db($useUUID)->find('parent')->where('id = ?')->setParameter(0, $id)->with('ownUserList')->with('sharedUserList')->with('grandparent')->first();
    $parent_simple = db($useUUID)->getOne('parent', $id);

    $this->assertJsonStringNotEqualsJsonString(
        $parent_retrived->toString(),
        $parent_simple->toString()
    );

    $parent_simple->ownUserList;
    $parent_simple->sharedUserList;
    $parent_simple->grandparent;

    $this->assertJsonStringEqualsJsonString(
        $parent_retrived->toString(),
        $parent_simple->toString()
    );
})->with('useUUID');
