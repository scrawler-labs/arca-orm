<?php

use function Pest\Faker\fake;

covers(Scrawler\Arca\Model::class);
covers(Scrawler\Arca\Database::class);

beforeAll(function (): void {
    db()->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
});
AfterAll(function (): void {
    db()->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
});

afterEach(function (): void {
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS parent_user CASCADE; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS parent CASCADE; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS user CASCADE; ');
});

it('checks if model can retrive one-to-one related models', function ($useUUID): void {
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->email = fake()->email();
    $user->dob = fake()->date();
    $user->age = fake()->randomNumber(2, false);
    $user->address = fake()->streetAddress();
    // $id = $user->save();

    $parent = db($useUUID)->create('parent');
    $parent->name = fake()->name();
    $parent->user = $user;
    $id = $parent->save();

    $user_retrived = $parent->user;
    if (!db($useUUID)->isUsingUUID()) {
        unset($user_retrived->id);
    }
    $this->assertJsonStringEqualsJsonString(
        $user_retrived->toString(),
        $user->toString()
    );
})->with('useUUID');

it('checks if model can retrive one-to-many related models', function ($useUUID): void {
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->email = fake()->email();
    $user->dob = fake()->date();
    $user->age = fake()->randomNumber(2, false);
    $user->address = fake()->streetAddress();

    $user_two = db($useUUID)->create('user');
    $user_two->name = fake()->name();
    $user_two->email = fake()->email();
    $user_two->dob = fake()->date();
    $user_two->age = fake()->randomNumber(2, false);
    $user_two->address = fake()->streetAddress();
    // $id = $user->save();

    $parent = db($useUUID)->create('parent');
    $parent->name = fake()->name();
    $parent->ownUserList = [$user, $user_two];
    $id = $parent->save();

    $parent_retrived = db($useUUID)->getOne('parent', $id);
    $users_retrived = $parent->ownUserList->apply(function ($user): void {
        unset($user->id);
    });
    if (db($useUUID)->isUsingUUID()) {
        unset($user->id);
        unset($user_two->id);
    }

    $test_collection = Scrawler\Arca\Collection::fromIterable([$user, $user_two])
    ->map(static fn ($model): Scrawler\Arca\Model => $model->setLoaded());
    $test_collection_two = Scrawler\Arca\Collection::fromIterable([$user_two, $user])
    ->map(static fn ($model): Scrawler\Arca\Model => $model->setLoaded());

    $this->assertTrue(
        $users_retrived->toString() == $test_collection->toString() || $users_retrived->toString() == $test_collection_two->toString()
    );
})->with('useUUID');

it('checks if model can retrive many-to-many related models', function ($useUUID): void {
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->email = fake()->email();
    $user->dob = fake()->date();
    $user->age = fake()->randomNumber(2, false);
    $user->address = fake()->streetAddress();

    $user_two = db($useUUID)->create('user');
    $user_two->name = fake()->name();
    $user_two->email = fake()->email();
    $user_two->dob = fake()->date();
    $user_two->age = fake()->randomNumber(2, false);
    $user_two->address = fake()->streetAddress();
    // $id = $user->save();

    $parent = db($useUUID)->create('parent');
    $parent->name = fake()->name();
    $parent->sharedUserList = [$user, $user_two];
    $id = $parent->save();

    $parent_retrived = db()->getOne('parent', $id);
    $users_retrived = $parent->sharedUserList->apply(function ($user): void {
        unset($user->id);
    });

    if (db($useUUID)->isUsingUUID()) {
        unset($user->id);
        unset($user_two->id);
    }

    $test_collection = Scrawler\Arca\Collection::fromIterable([$user, $user_two])
    ->map(static fn ($model): Scrawler\Arca\Model => $model->setLoaded());

    $test_collection_two = Scrawler\Arca\Collection::fromIterable([$user_two, $user])
    ->map(static fn ($model): Scrawler\Arca\Model => $model->setLoaded());
    $this->assertTrue(
        $users_retrived->toString() == $test_collection->toString() || $users_retrived->toString() == $test_collection_two->toString()
    );
})->with('useUUID');
