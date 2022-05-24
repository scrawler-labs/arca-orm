<?php
use function Pest\Faker\faker;

it("checks if model is properly populated on retrive", function () {
    $user = db()->get('user', 1);
    $stmt = db()->connection->prepare("SELECT * FROM user WHERE id = 1");
    $result =$stmt->executeQuery()->fetchAssociative();
    $this->assertEquals($user->name, $result['name']);
    $this->assertEquals($user->getProperties(), $result);
});

it("checks if model can retrive one-to-one related models", function () {
    db()->connection->query("DROP TABLE IF EXISTS user; ");
    db()->connection->query("DROP TABLE IF EXISTS parent; ");
    $user = db()->create('user');
    $user->name = faker()->name;
    $user->email = faker()->email;
    $user->dob = faker()->date;
    $user->age = faker()->randomNumber(2, false);
    $user->address = faker()->streetAddress();
    //$id = $user->save();

    $parent = db()->create('parent');
    $parent->name = faker()->name;
    $parent->user = $user;
    $id = $parent->save();

    $parent_retrived = db()->get('parent', $id);
    $user_retrived = $parent->user;
    unset($user_retrived->id);

    $this->assertJsonStringEqualsJsonString(
        $user_retrived->toString(),
        $user->toString()
    );
});

it("checks if model can retrive one-to-many related models", function () {
    db()->connection->query("DROP TABLE IF EXISTS user; ");
    db()->connection->query("DROP TABLE IF EXISTS parent; ");
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

    $parent_retrived = db()->get('parent', $id);
    $users_retrived = $parent->ownUserList->apply(function ($user) {
        unset($user->id);
    });
    $test_collection = \Scrawler\Arca\Collection::fromIterable([$user,$user_two])
    ->map(static fn ($model): \Scrawler\Arca\Model => $model->setLoaded());
    $this->assertJsonStringEqualsJsonString(
        $users_retrived->toString(),
        $test_collection->toString()
    );
});

it("checks if model can retrive many-to-many related models", function () {
    db()->connection->query("DROP TABLE IF EXISTS user; ");
    db()->connection->query("DROP TABLE IF EXISTS parent; ");
    db()->connection->query("DROP TABLE IF EXISTS parent_user; ");

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
    $parent->sharedUserList = [$user,$user_two];
    $id = $parent->save();

    $parent_retrived = db()->get('parent', $id);
    $users_retrived = $parent->sharedUserList->apply(function ($user) {
        unset($user->id);
    });
    $test_collection = \Scrawler\Arca\Collection::fromIterable([$user,$user_two])
    ->map(static fn ($model): \Scrawler\Arca\Model => $model->setLoaded());
    $this->assertJsonStringEqualsJsonString(
        $users_retrived->toString(),
        $test_collection->toString()
    );
});


it("check exception is thrown when non existent key is accessed", function () {
    $user = db()->get('user', 1);
    $user->somekey;
})->throws(\Scrawler\Arca\Exception\KeyNotFoundException::class);

it("checks isset() function of model", function () {
    $user = db()->get('user', 1);
    $truey = isset($user->name);
    $falsey = isset($user->somekey);
    $this->assertTrue($truey);
    $this->assertFalse($falsey);
});
