<?php
use function Pest\Faker\fake;
// covers(Scrawler\Arca\Model::class);
// covers(classesOrFunctions: Scrawler\Arca\Exception\InvalidIdException::class);
// covers(classesOrFunctions: Scrawler\Arca\Exception\KeyNotFoundException::class);
// covers(classesOrFunctions: Scrawler\Arca\Exception\InvalidModelException::class);

beforeEach(function () {
    db()->getConnection()->executeStatement("DROP TABLE IF EXISTS user; ");
    db()->getConnection()->executeStatement("DROP TABLE IF EXISTS parent; ");
    db()->getConnection()->executeStatement("DROP TABLE IF EXISTS parent_user; ");
});

it("checks if model is properly populated on retrive", function ($useUUID) {
    $id = createRandomUser($useUUID);
    $stmt = db($useUUID)->getConnection()->prepare("SELECT * FROM user WHERE id ='".$id."'");
    $user = db($useUUID)->getOne('user', $id);
    $result =$stmt->executeQuery()->fetchAssociative();
    $this->assertEquals($user->name, $result['name']);
    $this->assertEquals($user->getProperties(), $result);
})->with('useUUID');

it("tests for model equals",function($useUUID){
    $id = createRandomUser($useUUID);
    $user = db($useUUID)->getOne('user', $id);
    $user_two = db($useUUID)->getOne('user', $id);
    $this->assertTrue($user->equals($user_two));
})->with('useUUID');

it("tests for model not equals",function($useUUID){
    $id = createRandomUser($useUUID);
    $user = db($useUUID)->getOne('user', $id);
    $user_two = db($useUUID)->getOne('user', $id);
    $user_two->name = 'test';
    $this->assertFalse($user->equals($user_two));
})->with('useUUID');

it("checks if model can retrive one-to-one related models", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->email = fake()->email();
    $user->dob = fake()->date();
    $user->age = fake()->randomNumber(2, false);
    $user->address = fake()->streetAddress();
    //$id = $user->save();

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

it("checks if model can retrive one-to-many related models", function ($useUUID) {
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
    //$id = $user->save();

    $parent = db($useUUID)->create('parent');
    $parent->name = fake()->name();
    $parent->ownUserList = [$user,$user_two];
    $id = $parent->save();

    $parent_retrived = db($useUUID)->getOne('parent', $id);
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
    //$id = $user->save();

    $parent = db($useUUID)->create('parent');
    $parent->name = fake()->name();
    $parent->sharedUserList = [$user,$user_two];
    $id = $parent->save();

    $parent_retrived = db()->getOne('parent', $id);
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

it("checks if model can retrive many-to-many related models using with function", function ($useUUID) {
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
    //$id = $user->save();

    $parent = db($useUUID)->create('parent');
    $parent->name = fake()->name();
    $parent->sharedUserList = [$user,$user_two];
    $id = $parent->save();

    $parent_retrived = db()->getOne('parent', $id)->with(['sharedUserList']);
    $users_retrived = $parent_retrived->sharedUserList->apply(function ($user) {
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
    $user = db($useUUID)->getOne('user', $id);
    $user->somekey;
})->throws(\Scrawler\Arca\Exception\KeyNotFoundException::class,"Key you are trying to access does not exist")->with('useUUID');

it("checks isset() function of model", function ($useUUID) {
    $id = createRandomUser($useUUID);
    $user = db($useUUID)->getOne('user', $id);
    $truey = isset($user->name);
    $falsey = isset($user->somekey);
    $this->assertTrue($truey);
    $this->assertFalse($falsey);
})->with('useUUID');

it("checks exception is thrown when id is force set on a model", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->id = 1;
    $user->name = fake()->name();
    $user->email = fake()->email();
    $user->save();
   
})->with('useUUID')->throws(\Scrawler\Arca\Exception\InvalidIdException::class,'Force setting of id for model is not allowed');

it("checks exception is thrown if share list is not array of model",function($useUUID){
    $parent = db($useUUID)->create('parent');
    $parent->name = fake()->name();
    $parent->sharedUserList = ['test','test1'];
    $id = $parent->save();
})->with('useUUID')->throws(\Scrawler\Arca\Exception\InvalidModelException::class);

it("checks exception is thrown if own list is not array of model",function($useUUID){
    $parent = db($useUUID)->create('parent');
    $parent->name = fake()->name();
    $parent->ownUserList = ['test','test1'];
    $id = $parent->save();
})->with('useUUID')->throws(\Scrawler\Arca\Exception\InvalidModelException::class,'parameter passed to shared list or own list should be array of class \Arca\Model');



it("checks for setting array as property of model",function($useUUID){
$user = db($useUUID)->create('user');
$user->name = fake()->name();
$user->email = fake()->email();
$user->hobbies = ["swimming","cycling","running"];
$id = $user->save();

$user_retrived = db($useUUID)->getOne('user', $id);
$hobby = $user_retrived->hobbies;
expect($hobby)->toBeArray();
})->with('useUUID');

it('tests bulk property setting',function($useUUID){
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
    $this->assertEquals($user->name,$user_retrived->name);
    $this->assertEquals($user->email,$user_retrived->email);
})->with('useUUID');

it('tests for model delete() function',function($useUUID){
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->email = fake()->email();
    $id = $user->save();
    $user->delete();
    $this->assertNull(db()->getOne('user',$id));
})->with('useUUID');

it('tests for types when loaded ',function($useUUID){
    $id = createRandomUser($useUUID);
    $user_retrived = db($useUUID)->getOne('user', $id);
    $types = $user_retrived->getTypes();
    $this->assertIsArray($types);
    $this->assertEquals($types['name'],'text');
    $this->assertEquals($types['email'],'text');
})->with('useUUID');


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


it('tests boolean type',function(){
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
});