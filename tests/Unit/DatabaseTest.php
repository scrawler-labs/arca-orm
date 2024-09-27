<?php
use function Pest\Faker\fake;
use Doctrine\DBAL\Exception\DriverException;

 beforeEach(function () {
     db()->getConnection()->executeStatement("DROP TABLE IF EXISTS user; ");
     db()->getConnection()->executeStatement("DROP TABLE IF EXISTS parent; ");
     db()->getConnection()->executeStatement("DROP TABLE IF EXISTS parent_user; ");
 });

it(" checks db()->isUsingUUID() function ", function ($useUUID) {
    if($useUUID == 'UUID'){
        $this->assertTrue(db($useUUID)->isUsingUUID());
    }else{
        $this->assertFalse(db($useUUID)->isUsingUUID());
    }
   
})->with('useUUID');

it(" checks db()->create() function ", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $model =  new \Scrawler\Arca\Model('user',db($useUUID)->getConnection());
    $this->assertObjectEquals($model, $user);
})->with('useUUID');

it("checks if db()->save() function creates table", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->email = fake()->email();
    $user->dob = fake()->date();
    $user->age = fake()->randomNumber(2, false);
    $user->active = false;

    $user->save();

    $table= db($useUUID)->getConnection()->getSchemaManager()->introspectTable('user');
    $requiredTable = new \Doctrine\DBAL\Schema\Table('user');
    if (db($useUUID)->isUsingUUID()) {
        $requiredTable->addColumn('id', 'string', array('length' => 36,'notnull' => true));
    } else {
        $requiredTable->addColumn('id', 'integer', array("unsigned" => true, "autoincrement" => true));
    }
    $requiredTable->addColumn('name', "text", ['notnull' => false, 'comment' => 'name']);
    $requiredTable->addColumn('email', "text", ['notnull' => false, 'comment' => 'email']);
    $requiredTable->addColumn('dob', "text", ['notnull' => false, 'comment' => 'dob']);
    $requiredTable->addColumn('age', "integer", ['notnull' => false, 'comment' => 'age']);
    $requiredTable->addColumn('active', "integer", ['notnull' => false, 'comment' => 'active']);

    $requiredTable->setPrimaryKey(array("id"));

    $actual = new \Doctrine\DBAL\Schema\Schema([$table]);
    $required = new \Doctrine\DBAL\Schema\Schema([$requiredTable]);
    $comparator = db($useUUID)->getConnection()->getSchemaManager()->createComparator();
    $diff = $comparator->compareSchemas($actual, $required);

    $this->assertEmpty(db($useUUID)->getConnection()->getPlatform()->getAlterSchemaSQL($diff));
})->with('useUUID');


it("checks if db()->save() function saves record", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->email = fake()->email();
    $user->dob = fake()->date();
    $user->age = fake()->randomNumber(2, false);
    $user->address = fake()->streetAddress();
    $id = $user->save();


    $stmt = db($useUUID)->getConnection()->prepare("SELECT * FROM user WHERE id = '".$id."'");
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $user->toString()
    );
})->with('useUUID');

it("checks if db()->save() function saves record with one-to-one relation", function ($useUUID) {
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

    $stmt = db($useUUID)->getConnection()->prepare("SELECT * FROM parent WHERE id = '".$id."'");
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $parent->toString()
    );
})->with('useUUID');

it("checks if db()->save() function saves record with one-to-many relation", function ($useUUID) {
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

    $stmt = db($useUUID)->getConnection()->prepare("SELECT * FROM parent WHERE id = '".$id."'");
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $stmt = db($useUUID)->getConnection()->prepare("SELECT * FROM user WHERE parent_id = '".$id."'");
    $result_child = $stmt->executeQuery()->fetchAllAssociative();
    if (!db($useUUID)->isUsingUUID()) {
        unset($result_child[0]['id']);
        unset($result_child[1]['id']);
    }
    $this->assertJsonStringEqualsJsonString(
        $result,
        $parent->toString()
    );
    $this->assertTrue(
        ($result_child[0] == $user->toArray() || $result_child[0] == $user_two->toArray())
    );
})->with('useUUID');

it("checks for exception in database save()",function($useUUID){
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->email = fake()->email();
    $user->dob = fake()->date();
    $user->age = fake()->randomNumber(2, false);
    $user->address = fake()->streetAddress();
    $user->save();

    $user->name = fake()->name();
    $user->email = fake()->email();
    $user->dob = fake()->date();
    $user->age = 'error';
    $user->address = fake()->streetAddress();
    $user->save();
})->with('useUUID')->throws(DriverException::class);

it("checks for exception in database saveOto()",function($useUUID){
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->email = fake()->email();
    $user->dob = fake()->date();
    $user->age = fake()->randomNumber(2, false);
    $user->address = fake()->streetAddress();
    $user->save();

    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->email = fake()->email();
    $user->dob = fake()->date();
    $user->age = 'error';
    $user->address = fake()->streetAddress();
    #$user->save();

    $parent = db($useUUID)->create('parent');
    $parent->name = fake()->name();
    $parent->user = $user;
    $id = $parent->save();
})->with('useUUID')->throws(DriverException::class);

it("checks for exception in database saveMto()",function($useUUID){

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
    $user_two->age = 'error';
    $user_two->address = fake()->streetAddress();
    //$id = $user->save();

    $parent = db($useUUID)->create('parent');
    $parent->name = fake()->name();
    $parent->sharedUserList = [$user,$user_two];
    $id = $parent->save();
})->with('useUUID')->throws(DriverException::class);



it("checks if db()->save() function saves record with many-to-many relation", function ($useUUID) {
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

    $stmt = db($useUUID)->getConnection()->prepare("SELECT * FROM parent WHERE id = '".$id."'");
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $stmt = db($useUUID)->getConnection()->prepare("SELECT * FROM parent_user WHERE parent_id = '".$id."'");
    $results_rel = $stmt->executeQuery()->fetchAllAssociative();
    $rel_ids ='';
    foreach ($results_rel as $relation) {
        $key = 'user_id';
        $rel_ids .= "'".$relation[$key] . "',";
    }

    $rel_ids = substr($rel_ids, 0, -1);
    $stmt = db($useUUID)->getConnection()->prepare("SELECT * FROM user WHERE id IN (" . $rel_ids . ")");
    $result_user = $stmt->executeQuery()->fetchAllAssociative();
    if (!db($useUUID)->isUsingUUID()) {
        unset($result_user[0]['id']);
        unset($result_user[1]['id']);
    }
    $this->assertJsonStringEqualsJsonString(
        $result,
        $parent->toString()
    );

    $this->assertTrue(
        ($result_user[0] == $user->toArray() || $result_user[0] == $user_two->toArray())
    );
})->with('useUUID');

it("checks if db()->save() function updates record", function ($useUUID) {
    $id = createRandomUser($useUUID);
    $user = db($useUUID)->getOne('user', $id);
    $user->age = 44;
    $id = $user->save();

    $stmt = db($useUUID)->getConnection()->prepare("SELECT * FROM user WHERE id = '".$id."'");
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $user->toString()
    );
})->with('useUUID');



it("checks if db()->getOne() gets single record", function ($useUUID) {
    populateRandomUser($useUUID);
    $id = createRandomUser($useUUID);
    $user = db($useUUID)->getOne('user', $id);

    $stmt = db($useUUID)->getConnection()->prepare("SELECT * FROM user WHERE id = '".$id."'");
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $user->toString()
    );
    $this->assertIsString((string) $user);
    $this->assertInstanceOf(\Scrawler\Arca\Model::class, $user);
})->with('useUUID');

it("checks if db()->get() gets all record", function ($useUUID) {
    populateRandomUser($useUUID);
    $users = db($useUUID)->get('user');
    $stmt = db($useUUID)->getConnection()->prepare("SELECT * FROM user");
    $result = json_encode($stmt->executeQuery()->fetchAllAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $users->toString()
    );
    $this->assertInstanceOf(\Scrawler\Arca\Collection::class, $users);
})->with('useUUID');

it("checks if db()->find() returns Query Builder", function () {
    $this->assertInstanceOf(\Scrawler\Arca\QueryBuilder::class, db()->find('user'));
    $this->assertInstanceOf(\Scrawler\Arca\QueryBuilder::class, db()->find('user')->where('id = 2'));
});

it("checks if db()->find() returns correct records", function () {
    populateRandomUser();
    $users = db()->find('user')->where('active = 1')->get();
    $stmt = db()->getConnection()->prepare("SELECT * FROM user WHERE active = 1");
    $result = json_encode($stmt->executeQuery()->fetchAllAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $users->toString()
    );
    $this->assertInstanceOf(\Scrawler\Arca\Collection::class, $users);
});


it("checks if all public instance of database files are correct", function () {
    $this->assertInstanceOf(\Scrawler\Arca\Connection::class, db()->getConnection());
    $this->assertInstanceOf(\Doctrine\DBAL\Platforms\AbstractPlatform::class, db()->getConnection()->getPlatform());
    $this->assertInstanceOf(\Doctrine\DBAL\Schema\AbstractSchemaManager::class, db()->getConnection()->getSchemaManager());
});

it("checks  db()->exec() function", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->save();
    if (db($useUUID)->isUsingUUID()) {
        db($useUUID)->exec("insert into user (id,name) values ('abc-jfke-dmsk','john')");
        $id = 'abc-jfke-dmsk';
    } else {
        db($useUUID)->exec("insert into user (name) values ('john')");
        $id = 2;
    }

    $stmt = db($useUUID)->getConnection()->prepare("SELECT * FROM user where id = '".$id."'");
    $result = $stmt->executeQuery()->fetchAssociative();
    $this->assertEquals($result['name'], "john");
})->with('useUUID');

it("checks  db()->getAll() function", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->save();

    $stmt = db($useUUID)->getConnection()->prepare("SELECT * FROM user");
    $result = $stmt->executeQuery()->fetchAllAssociative();

    $actual = db($useUUID)->getAll("SELECT * FROM user");

    $this->assertEquals($result, $actual);
})->with('useUUID');

it("checks db()->delete() function", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $id = $user->save();
    $user->delete();
    $stmt = db($useUUID)->getConnection()->prepare("SELECT * FROM user where id = '".$id."'");
    $result = $stmt->executeQuery()->fetchAssociative();
    $this->assertEmpty($result);
})->with('useUUID');
