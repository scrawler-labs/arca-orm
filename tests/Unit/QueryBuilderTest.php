<?php
use function Pest\Faker\fake;

 beforeEach(function () {
     db()->connection->executeStatement("DROP TABLE IF EXISTS user; ");
     db()->connection->executeStatement("DROP TABLE IF EXISTS parent; ");
     db()->connection->executeStatement("DROP TABLE IF EXISTS parent_user; ");
 });

it("checks if db()->find()->first() returns first record", function ($useUUID) {
    $id = createRandomUser($useUUID);
    $user = db($useUUID)->find('user')->first();
    $stmt = db($useUUID)->connection->prepare("SELECT * FROM user WHERE id = '".$id."'");
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $user->toString()
    );
    $this->assertInstanceOf(\Scrawler\Arca\Model::class, $user);
})->with('useUUID');

it("checks if db()->find()->with() eager loads relation", function ($useUUID) {

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
    $parent->save();

    $parent_retrived = db($useUUID)->find('parent')->with('user')->first();
    $user = db($useUUID)->find('user')->first();

    $this->assertJsonStringEqualsJsonString(
        $parent_retrived->user->toString(),
        $user->toString()
    );

})->with('useUUID');