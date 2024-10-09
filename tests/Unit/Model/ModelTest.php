<?php
use function Pest\Faker\fake;

covers(\Scrawler\Arca\Model::class); 
covers(\Scrawler\Arca\Database::class); 
covers(\Scrawler\Arca\Connection::class);
covers(\Scrawler\Arca\Manager\RecordManager::class);


beforeEach(function () {
    db()->getConnection()->executeStatement("DROP TABLE IF EXISTS user; ");
    db()->getConnection()->executeStatement("DROP TABLE IF EXISTS parent; ");
    db()->getConnection()->executeStatement("DROP TABLE IF EXISTS parent_user; ");
});

it("checks if model is properly populated on retrive", function ($useUUID) {
    $id = createRandomUser($useUUID);
    $stmt = db($useUUID)->getConnection()->prepare("SELECT * FROM user WHERE id ='" . $id . "'");
    $user = db($useUUID)->getOne('user', $id);
    $result = $stmt->executeQuery()->fetchAssociative();
    $this->assertEquals($user->name, $result['name']);
    $this->assertEquals($user->getProperties(), $result);
})->with('useUUID');

it("tests for model equals", function ($useUUID) {
    $id = createRandomUser($useUUID);
    $user = db($useUUID)->getOne('user', $id);
    $user_two = db($useUUID)->getOne('user', $id);
    $this->assertTrue($user->equals($user_two));
})->with('useUUID');

it("tests for model not equals", function ($useUUID) {
    $id = createRandomUser($useUUID);
    $user = db($useUUID)->getOne('user', $id);
    $user_two = db($useUUID)->getOne('user', $id);
    $user_two->name = 'test';
    $this->assertFalse($user->equals($user_two));
})->with('useUUID');


it("checks isset() function of model", function ($useUUID) {
    $id = createRandomUser($useUUID);
    $user = db($useUUID)->getOne('user', $id);
    $truey = isset($user->name);
    $falsey = isset($user->somekey);
    $this->assertTrue($truey);
    $this->assertFalse($falsey);
})->with('useUUID');


it('tests bulk property setting', function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->setProperties([
        'name' => fake()->name(),
        'email' => fake()->email(),
        'dob' => fake()->date(),
        'age' => fake()->randomNumber(2, false),
        'address' => fake()->streetAddress()
    ]);
    $id = $user->save();
    $user_retrived = db($useUUID)->getOne('user', $id);
    $this->assertEquals($user->name, $user_retrived->name);
    $this->assertEquals($user->email, $user_retrived->email);
})->with('useUUID');

it('tests for model delete() function', function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->email = fake()->email();
    $id = $user->save();
    $user->delete();
    $this->assertNull(db()->getOne('user', $id));
})->with('useUUID');

it('tests for model clean on load function', function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->email = fake()->email();
    $id = $user->save();

    $user = db($useUUID)->getOne('user', $id);
    $this->assertFalse($user->hasIdError());
    $this->assertFalse($user->hasForeign('oto'));
    $this->assertFalse($user->hasForeign('otm'));
    $this->assertFalse($user->hasForeign('mtm'));


})->with('useUUID');

it('tests id is always 0 before save on a new model', function ($useUUID) {
    $user = db($useUUID)->create('user');
    $this->assertEquals($user->getId(), 0);
    $user->name = fake()->name();
    $user->email = fake()->email();
    $id = $user->save();
    $this->assertNotEquals($user->getId(), 0);
    $this->assertEquals($user->getId(), $id);

})->with('useUUID');


