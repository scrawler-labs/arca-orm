<?php

use function Pest\Faker\fake;

covers(Scrawler\Arca\Model::class);
covers(Scrawler\Arca\Database::class);

beforeEach(function () {
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS user; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS parent; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS parent_user; ');
});

it('tests for types when loaded ', function ($useUUID) {
    $id = createRandomUser($useUUID);
    $user_retrived = db($useUUID)->getOne('user', $id);
    $types = $user_retrived->getTypes();
    $this->assertIsArray($types);
    $this->assertEquals($types['name'], 'text');
    $this->assertEquals($types['email'], 'text');
})->with('useUUID');

it('tests boolean type bug', function ($useUUID) {
    $user = db()->create('user');
    $user->name = fake()->name();
    $user->email = fake()->email();
    $user->active = true;
    $id = $user->save();
    $user_retrived = db()->getOne('user', $id);
    $this->assertTrue($user_retrived->active);

    $user->name = fake()->name();
    $user->email = fake()->email();
    $user->active = false;
    $id = $user->save();
    $user_retrived = db()->getOne('user', $id);
    $this->assertFalse($user_retrived->active);
})->with('useUUID');

it('checks for storing array in db', function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->email = fake()->email();
    $user->hobbies = ['swimming', 'cycling', 'running'];
    $id = $user->save();

    $user_retrived = db($useUUID)->getOne('user', $id);
    $hobby = $user_retrived->hobbies;
    expect($hobby)->toBeArray();
})->with('useUUID');
