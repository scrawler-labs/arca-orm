<?php
use function Pest\Faker\faker;


beforeEach(function () {
    db()->connection->query("DROP TABLE IF EXISTS user; ");
    db()->connection->query("DROP TABLE IF EXISTS parent; ");
    db()->connection->query("DROP TABLE IF EXISTS parent_user; ");
});

it("checks if model is properly populated on retrive", function ($useUUID) {
    $id = createRandomUser($useUUID);
    $stmt = db($useUUID)->connection->prepare("SELECT * FROM user WHERE id ='".$id."'");
    $user = db($useUUID)->get('user', $id);
    $result =$stmt->executeQuery()->fetchAssociative();
    $this->assertEquals($user->name, $result['name']);
    $this->assertEquals($user->getProperties(), $result);
})->with('useUUID');

it("checks if model can retrive one-to-one related models", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = faker()->name;
    $user->email = faker()->email;
    $user->dob = faker()->date;
    $user->age = faker()->randomNumber(2, false);
    $user->address = faker()->streetAddress();
    //$id = $user->save();

    $parent = db($useUUID)->create('parent');
    $parent->name = faker()->name;
    $parent->user = $user;
    $id = $parent->save();

    $parent_retrived = db()->get('parent', $id);
    $user_retrived = $parent->user;
    if (!db($useUUID)->isUsingUUID()) {
        unset($user_retrived->id);
    }
    $this->assertJsonStringEqualsJsonString(
        $user_retrived->toString(),
        $user->toString()
    );
})->with('useUUID');

it("checks if model can retrive one-to-many related models", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = faker()->name;
    $user->email = faker()->email;
    $user->dob = faker()->date;
    $user->age = faker()->randomNumber(2, false);
    $user->address = faker()->streetAddress();

    $user_two = db($useUUID)->create('user');
    $user_two->name = faker()->name;
    $user_two->email = faker()->email;
    $user_two->dob = faker()->date;
    $user_two->age = faker()->randomNumber(2, false);
    $user_two->address = faker()->streetAddress();
    //$id = $user->save();

    $parent = db($useUUID)->create('parent');
    $parent->name = faker()->name;
    $parent->ownUserList = [$user,$user_two];
    $id = $parent->save();

    $parent_retrived = db($useUUID)->get('parent', $id);
    $users_retrived = $parent->ownUserList->apply(function ($user) {
        unset($user->id);
    });
    if (db($useUUID)->isUsingUUID()) {
        unset($user->id);
        unset($user_two->id);
    }

    $test_collection = \Scrawler\Arca\Collection::fromIterable([$user,$user_two])
    ->map(static fn ($model): \Scrawler\Arca\Model => $model->setLoaded());
    $test_collection_two = \Scrawler\Arca\Collection::fromIterable([$user_two,$user])
    ->map(static fn ($model): \Scrawler\Arca\Model => $model->setLoaded());

    $this->assertTrue(
        ($users_retrived->toString() == $test_collection->toString() || $users_retrived->toString() == $test_collection_two->toString())
    );
})->with('useUUID');

it("checks if model can retrive many-to-many related models", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = faker()->name;
    $user->email = faker()->email;
    $user->dob = faker()->date;
    $user->age = faker()->randomNumber(2, false);
    $user->address = faker()->streetAddress();

    $user_two = db($useUUID)->create('user');
    $user_two->name = faker()->name;
    $user_two->email = faker()->email;
    $user_two->dob = faker()->date;
    $user_two->age = faker()->randomNumber(2, false);
    $user_two->address = faker()->streetAddress();
    //$id = $user->save();

    $parent = db($useUUID)->create('parent');
    $parent->name = faker()->name;
    $parent->sharedUserList = [$user,$user_two];
    $id = $parent->save();

    $parent_retrived = db()->get('parent', $id);
    $users_retrived = $parent->sharedUserList->apply(function ($user) {
        unset($user->id);
    });

    if (db($useUUID)->isUsingUUID()) {
        unset($user->id);
        unset($user_two->id);
    }

    $test_collection = \Scrawler\Arca\Collection::fromIterable([$user,$user_two])
    ->map(static fn ($model): \Scrawler\Arca\Model => $model->setLoaded());
  
    $test_collection_two = \Scrawler\Arca\Collection::fromIterable([$user_two,$user])
    ->map(static fn ($model): \Scrawler\Arca\Model => $model->setLoaded());
    $this->assertTrue(
        ($users_retrived->toString() == $test_collection->toString() || $users_retrived->toString() == $test_collection_two->toString())
    );
})->with('useUUID');


it("check exception is thrown when non existent key is accessed", function ($useUUID) {
    $id = createRandomUser($useUUID);
    $user = db($useUUID)->get('user', $id);
    $user->somekey;
})->throws(\Scrawler\Arca\Exception\KeyNotFoundException::class)->with('useUUID');

it("checks isset() function of model", function ($useUUID) {
    $id = createRandomUser($useUUID);
    $user = db($useUUID)->get('user', $id);
    $truey = isset($user->name);
    $falsey = isset($user->somekey);
    $this->assertTrue($truey);
    $this->assertFalse($falsey);
})->with('useUUID');
