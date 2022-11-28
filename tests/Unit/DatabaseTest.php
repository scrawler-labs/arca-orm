<?php
 use function Pest\Faker\faker;


 beforeEach(function () {
     db()->connection->query("DROP TABLE IF EXISTS user; ");
     db()->connection->query("DROP TABLE IF EXISTS parent; ");
     db()->connection->query("DROP TABLE IF EXISTS parent_user; ");
 });

it(" checks db()->create() function ", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $model =  new \Scrawler\Arca\Model('user',db($useUUID));
    $this->assertObjectEquals($model, $user);
})->with('useUUID');

it("checks if db()->save() function creates table", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = faker()->name;
    $user->email = faker()->email;
    $user->dob = faker()->date;
    $user->age = faker()->randomNumber(2, false);
    $user->active = false;

    $user->save();

    $table= db($useUUID)->manager->listTableDetails('user');
    $requiredTable = new \Doctrine\DBAL\Schema\Table('user');
    if (db($useUUID)->isUsingUUID()) {
        $requiredTable->addColumn('id', 'string', array('length' => 36,'notnull' => true));
    } else {
        $requiredTable->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
    }
    $requiredTable->addColumn('name', "string", ['notnull' => false, 'comment' => 'name']);
    $requiredTable->addColumn('email', "string", ['notnull' => false, 'comment' => 'email']);
    $requiredTable->addColumn('dob', "string", ['notnull' => false, 'comment' => 'dob']);
    $requiredTable->addColumn('age', "integer", ['notnull' => false, 'comment' => 'age']);
    $requiredTable->addColumn('active', "integer", ['notnull' => false, 'comment' => 'active']);

    $requiredTable->setPrimaryKey(array("id"));

    $actual = new \Doctrine\DBAL\Schema\Schema([$table]);
    $required = new \Doctrine\DBAL\Schema\Schema([$requiredTable]);
    $comparator = new \Doctrine\DBAL\Schema\Comparator();
    $diff = $comparator->compare($actual, $required);

    $this->assertEmpty($diff->toSql(db()->platform));
})->with('useUUID');

it("checks if db()->save() function modifies table", function ($useUUID) {
    $id = createRandomUser($useUUID);
  

    $table= db($useUUID)->manager->listTableDetails('user');
    $requiredTable = new \Doctrine\DBAL\Schema\Table('user');
    if (db($useUUID)->isUsingUUID()) {
        $requiredTable->addColumn('id', 'string', array('length' => 36,'notnull' => true));
    } else {
        $requiredTable->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
    }
    $requiredTable->addColumn('name', "string", ['notnull' => false, 'comment' => 'name']);
    $requiredTable->addColumn('email', "string", ['notnull' => false, 'comment' => 'email']);
    $requiredTable->addColumn('dob', "string", ['notnull' => false, 'comment' => 'dob']);
    $requiredTable->addColumn('age', "integer", ['notnull' => false, 'comment' => 'age']);
    $requiredTable->addColumn('active', "integer", ['notnull' => false, 'comment' => 'active']);
    $requiredTable->addColumn('address', "string", ['notnull' => false, 'comment' => 'address']);

    $requiredTable->setPrimaryKey(array("id"));

    $actual = new \Doctrine\DBAL\Schema\Schema([$table]);
    $required = new \Doctrine\DBAL\Schema\Schema([$requiredTable]);
    $comparator = new \Doctrine\DBAL\Schema\Comparator();
    $diff = $comparator->compare($actual, $required);
    //print_r($diff->toSql(db()->platform));

    $this->assertEmpty($diff->toSql(db($useUUID)->platform));
})->with('useUUID');

it("checks if db()->save() function saves record", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = faker()->name;
    $user->email = faker()->email;
    $user->dob = faker()->date;
    $user->age = faker()->randomNumber(2, false);
    $user->address = faker()->streetAddress();
    $id = $user->save();
    //db()->connection->getNativeConnection()->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);


    $stmt = db($useUUID)->connection->prepare("SELECT * FROM user WHERE id = '".$id."'");
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $user->toString()
    );
})->with('useUUID');

it("checks if db()->save() function saves record with one-to-one relation", function ($useUUID) {
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

    $stmt = db($useUUID)->connection->prepare("SELECT * FROM parent WHERE id = '".$id."'");
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $parent->toString()
    );
})->with('useUUID');

it("checks if db()->save() function saves record with one-to-many relation", function ($useUUID) {
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

    $stmt = db($useUUID)->connection->prepare("SELECT * FROM parent WHERE id = '".$id."'");
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $stmt = db($useUUID)->connection->prepare("SELECT * FROM user WHERE parent_id = '".$id."'");
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

it("checks if db()->save() function saves record with many-to-many relation", function ($useUUID) {
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

    $stmt = db($useUUID)->connection->prepare("SELECT * FROM parent WHERE id = '".$id."'");
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $stmt = db($useUUID)->connection->prepare("SELECT * FROM parent_user WHERE parent_id = '".$id."'");
    $results_rel = $stmt->executeQuery()->fetchAllAssociative();
    $rel_ids ='';
    foreach ($results_rel as $relation) {
        $key = 'user_id';
        $rel_ids .= "'".$relation[$key] . "',";
    }

    $rel_ids = substr($rel_ids, 0, -1);
    $stmt = db($useUUID)->connection->prepare("SELECT * FROM user WHERE id IN (" . $rel_ids . ")");
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
    $user = db($useUUID)->get('user', $id);
    $user->age = 44;
    $id = $user->save();

    $stmt = db($useUUID)->connection->prepare("SELECT * FROM user WHERE id = '".$id."'");
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $user->toString()
    );
})->with('useUUID');

it("checks if db()->get() gets single record", function ($useUUID) {
    populateRandomUser($useUUID);
    $id = createRandomUser($useUUID);
    $user = db($useUUID)->get('user', $id);

    $stmt = db($useUUID)->connection->prepare("SELECT * FROM user WHERE id = '".$id."'");
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $user->toString()
    );
    $this->assertIsString((string) $user);
    $this->assertInstanceOf(\Scrawler\Arca\Model::class, $user);
})->with('useUUID');

it("checks if db()->getOne() gets single record", function ($useUUID) {
    populateRandomUser($useUUID);
    $id = createRandomUser($useUUID);
    $user = db($useUUID)->getOne('user', $id);

    $stmt = db($useUUID)->connection->prepare("SELECT * FROM user WHERE id = '".$id."'");
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
    $stmt = db($useUUID)->connection->prepare("SELECT * FROM user");
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
    $stmt = db()->connection->prepare("SELECT * FROM user WHERE active = 1");
    $result = json_encode($stmt->executeQuery()->fetchAllAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $users->toString()
    );
    $this->assertInstanceOf(\Scrawler\Arca\Collection::class, $users);
});


it("checks if all public instance of database files are correct", function () {
    $this->assertInstanceOf(\Doctrine\DBAL\Connection::class, db()->connection);
    $this->assertInstanceOf(\Doctrine\DBAL\Platforms\AbstractPlatform::class, db()->platform);
    $this->assertInstanceOf(\Doctrine\DBAL\Schema\AbstractSchemaManager::class, db()->manager);
});

it("checks  db()->exec() function", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = faker()->name;
    $user->save();
    if (db($useUUID)->isUsingUUID()) {
        db($useUUID)->exec("insert into user (id,name) values ('abc-jfke-dmsk','john')");
        $id = 'abc-jfke-dmsk';
    } else {
        db($useUUID)->exec("insert into user (name) values ('john')");
        $id = 2;
    }

    $stmt = db($useUUID)->connection->prepare("SELECT * FROM user where id = '".$id."'");
    $result = $stmt->executeQuery()->fetchAssociative();
    $this->assertEquals($result['name'], "john");
})->with('useUUID');

it("checks  db()->getAll() function", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = faker()->name;
    $user->save();

    $stmt = db($useUUID)->connection->prepare("SELECT * FROM user");
    $result = $stmt->executeQuery()->fetchAllAssociative();

    $actual = db($useUUID)->getAll("SELECT * FROM user");

    $this->assertEquals($result, $actual);
})->with('useUUID');

it("checks db()->delete() function", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = faker()->name;
    $id = $user->save();
    $user->delete();
    $stmt = db($useUUID)->connection->prepare("SELECT * FROM user where id = '".$id."'");
    $result = $stmt->executeQuery()->fetchAssociative();
    $this->assertEmpty($result);
})->with('useUUID');
