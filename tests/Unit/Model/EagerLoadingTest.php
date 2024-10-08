<?php
use function Pest\Faker\fake;

beforeEach(function () {
    db()->getConnection()->executeStatement("DROP TABLE IF EXISTS user; ");
    db()->getConnection()->executeStatement("DROP TABLE IF EXISTS parent; ");
    db()->getConnection()->executeStatement("DROP TABLE IF EXISTS parent_user; ");
});

it('tests for model with() one-to-one relation',function($useUUID){
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->email = fake()->email();
    $user->dob = fake()->date();
    $user->age = fake()->randomNumber(2, false);
    $user->address = fake()->streetAddress();
    //$user->save();

    $parent = db($useUUID)->create('parent');
    $parent->name = fake()->name();
    $parent->user = $user;

    $id = $parent->save();

    $parent_retrived_with = db($useUUID)->getOne('parent', $id)->with(['user']);
    $parent_retrived = db($useUUID)->getOne('parent', $id);

    $user = db($useUUID)->find('user')->first();

    $this->assertJsonStringNotEqualsJsonString(
        $parent_retrived_with->toString(),
        $parent_retrived->toString()
    );
    $parent_retrived->user;
    $this->assertJsonStringEqualsJsonString(
        $parent_retrived_with->toString(),
        $parent_retrived->toString()
    );
})->with('useUUID');

it('tests for model with() one-to-many relation',function($useUUID){
    $user1 = db($useUUID)->create('user');
    $user1->name = fake()->name();
    $user1->email = fake()->email();
    $user1->dob = fake()->date();
    $user1->age = fake()->randomNumber(2, false);
    $user1->address = fake()->streetAddress();

    $user2 = db($useUUID)->create('user');
    $user2->name = fake()->name();
    $user2->email = fake()->email();
    $user2->dob = fake()->date();
    $user2->age = fake()->randomNumber(2, false);
    $user2->address = fake()->streetAddress();
    //$user->save();

    $parent = db($useUUID)->create('parent');
    $parent->name = fake()->name();
    $parent->ownUserList = [$user1,$user2];

    $id = $parent->save();

    $parent_retrived_with = db($useUUID)->getOne('parent', $id)->with(['ownUserList']);
    $parent_retrived = db($useUUID)->getOne('parent', $id);

    $user = db($useUUID)->find('user')->first();

    $this->assertJsonStringNotEqualsJsonString(
        $parent_retrived_with->toString(),
        $parent_retrived->toString()
    );
    $parent_retrived->ownUserList;
    $this->assertJsonStringEqualsJsonString(
        $parent_retrived_with->toString(),
        $parent_retrived->toString()
    );
})->with('useUUID');

it('tests for model with() many-to-many relation',function($useUUID){
    $user1 = db($useUUID)->create('user');
    $user1->name = fake()->name();
    $user1->email = fake()->email();
    $user1->dob = fake()->date();
    $user1->age = fake()->randomNumber(2, false);
    $user1->address = fake()->streetAddress();

    $user2 = db($useUUID)->create('user');
    $user2->name = fake()->name();
    $user2->email = fake()->email();
    $user2->dob = fake()->date();
    $user2->age = fake()->randomNumber(2, false);
    $user2->address = fake()->streetAddress();
    //$user->save();

    $parent1 = db($useUUID)->create('parent');
    $parent1->name = fake()->name();
    $parent1->sharedUserList = [$user1,$user2];

    $parent2 = db($useUUID)->create('parent');
    $parent2->name = fake()->name();
    $parent2->sharedUserList = [$user1];

    $id1 = $parent1->save();
    $id2 = $parent2->save();

    $parent_retrived_with = db($useUUID)->getOne('parent', $id1)->with(['sharedUserList']);
    $parent_retrived = db($useUUID)->getOne('parent', $id1);

    $this->assertJsonStringNotEqualsJsonString(
        $parent_retrived_with->toString(),
        $parent_retrived->toString()
    );
    $parent_retrived->sharedUserList;
    $this->assertJsonStringEqualsJsonString(
        $parent_retrived_with->toString(),
        $parent_retrived->toString()
    );

    $parent_retrived_with = db($useUUID)->getOne('parent', $id2)->with(['sharedUserList']);
    $parent_retrived = db($useUUID)->getOne('parent', $id2);

    $this->assertJsonStringNotEqualsJsonString(
        $parent_retrived_with->toString(),
        $parent_retrived->toString()
    );
    $parent_retrived->sharedUserList;
    $this->assertJsonStringEqualsJsonString(
        $parent_retrived_with->toString(),
        $parent_retrived->toString()
    );
})->with('useUUID');
