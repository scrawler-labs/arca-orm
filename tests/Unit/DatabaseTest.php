<?php
 use function Pest\Faker\faker;

it(" checks db()->create() function ", function () {
    $user = db()->create('user');
    $model =  new \Scrawler\Arca\Model('user');
    $this->assertObjectEquals($model, $user);
});

it("checks if db()->save() function creates table", function () {
    db()->connection->query("DROP TABLE IF EXISTS user; ");

    $user = db()->create('user');
    $user->name = faker()->name;
    $user->email = faker()->email;
    $user->dob = faker()->date;
    $user->age = faker()->randomNumber(2, false);
    $user->active = false;

    $user->save();

    $table= db()->manager->listTableDetails('user');
    $requiredTable = new \Doctrine\DBAL\Schema\Table('user');
    $requiredTable->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
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
});

it("checks if db()->save() function modifies table", function () {
    $user = db()->create('user');
    $user->name = faker()->name;
    $user->email = faker()->email;
    $user->dob = faker()->date;
    $user->age = faker()->randomNumber(2, false);
    $user->address = faker()->streetAddress();

    $user->save();

    $table= db()->manager->listTableDetails('user');
    $requiredTable = new \Doctrine\DBAL\Schema\Table('user');
    $requiredTable->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
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

    $this->assertEmpty($diff->toSql(db()->platform));
});

it("checks if db()->save() function saves record", function () {
    db()->connection->query("DROP TABLE IF EXISTS user; ");

    $user = db()->create('user');
    $user->name = faker()->name;
    $user->email = faker()->email;
    $user->dob = faker()->date;
    $user->age = faker()->randomNumber(2, false);
    $user->address = faker()->streetAddress();
    $id = $user->save();
    //db()->connection->getNativeConnection()->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);


    $stmt = db()->connection->prepare("SELECT * FROM user WHERE id = ".$id);
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $user->toString()
    );
});

it("checks if db()->save() function saves record with one-to-one relation", function () {
    db()->connection->query("DROP TABLE IF EXISTS user; ");

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

    $stmt = db()->connection->prepare("SELECT * FROM parent WHERE id = ".$id);
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $parent->toString()
    );
});

it("checks if db()->save() function saves record with one-to-many relation", function () {
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

    $stmt = db()->connection->prepare("SELECT * FROM parent WHERE id = ".$id);
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $stmt = db()->connection->prepare("SELECT * FROM user WHERE parent_id = ".$id);
    $result_child = $stmt->executeQuery()->fetchAssociative();
    unset($result_child['id']);
    $result_child = json_encode($result_child);
    $this->assertJsonStringEqualsJsonString(
        $result,
        $parent->toString()
    );
    $this->assertJsonStringEqualsJsonString(
        $result_child,
        $user->toString()
    );
});

it("checks if db()->save() function saves record with many-to-many relation", function () {
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

    $stmt = db()->connection->prepare("SELECT * FROM parent WHERE id = ".$id);
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $stmt = db()->connection->prepare("SELECT * FROM parent_user WHERE parent_id = ".$id);
    $results_rel = $stmt->executeQuery()->fetchAssociative();
    $stmt = db()->connection->prepare("SELECT * FROM user WHERE id = ".$results_rel['user_id']);
    $result_user = $stmt->executeQuery()->fetchAssociative();
    unset($result_user['id']);
    $result_user = json_encode($result_user);
    $this->assertJsonStringEqualsJsonString(
        $result,
        $parent->toString()
    );

    $this->assertJsonStringEqualsJsonString(
        $result_user,
        $user->toString()
    );
});

it("checks if db()->save() function updates record", function () {
    $user = db()->get('user', 1);
    $user->age = 44;
    $id = $user->save();

    $stmt = db()->connection->prepare("SELECT * FROM user WHERE id = 1");
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $user->toString()
    );
});

it("checks if db()->get() gets single record", function () {
    db()->connection->query("DROP TABLE IF EXISTS user; ");
    populateRandomUser();
    $user = db()->get('user', 2);

    $stmt = db()->connection->prepare("SELECT * FROM user WHERE id = 2");
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $user->toString()
    );
    $this->assertIsString((string) $user);
    $this->assertInstanceOf(\Scrawler\Arca\Model::class, $user);
});

it("checks if db()->get() gets all record", function () {
    $users = db()->get('user');

    $stmt = db()->connection->prepare("SELECT * FROM user");
    $result = json_encode($stmt->executeQuery()->fetchAllAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $users->toString()
    );
    $this->assertInstanceOf(\Scrawler\Arca\Collection::class, $users);
});

it("checks if db()->find() returns Query Builder", function () {
    $this->assertInstanceOf(\Scrawler\Arca\QueryBuilder::class, db()->find('user'));
    $this->assertInstanceOf(\Scrawler\Arca\QueryBuilder::class, db()->find('user')->where('id = 2'));
});

it("checks if db()->find() returns currect records", function () {
    $users = db()->find('user')->where('active = 1')->get();
    $stmt = db()->connection->prepare("SELECT * FROM user WHERE active = 1");
    $result = json_encode($stmt->executeQuery()->fetchAllAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $users->toString()
    );
    $this->assertInstanceOf(\Scrawler\Arca\Collection::class, $users);
});

it("checks if Database::getInstance() provide currect instance", function () {
    $db =  \Scrawler\Arca\Database::getInstance();
    $this->assertInstanceOf(\Scrawler\Arca\Database::class, $db);
});

it("checks if all public instance of database files are correct", function () {
    $this->assertInstanceOf(\Doctrine\DBAL\Connection::class, db()->connection);
    $this->assertInstanceOf(\Doctrine\DBAL\Platforms\AbstractPlatform::class, db()->platform);
    $this->assertInstanceOf(\Doctrine\DBAL\Schema\AbstractSchemaManager::class, db()->manager);
});

it("checks  db()->exec() function", function () {
    db()->connection->query("DROP TABLE IF EXISTS user; ");
    $user = db()->create('user');
    $user->name = faker()->name;
    $user->save();
    db()->exec("insert into user (name) values ('john')");
    $stmt = db()->connection->prepare("SELECT * FROM user where id = 2");
    $result = $stmt->executeQuery()->fetchAssociative();
    $this->assertEquals($result['name'], "john");
});

it("checks  db()->getAll() function", function () {
    $user = db()->create('user');
    $user->name = faker()->name;
    $user->save();

    $stmt = db()->connection->prepare("SELECT * FROM user");
    $result = $stmt->executeQuery()->fetchAllAssociative();

    $actual = db()->getAll("SELECT * FROM user");

    $this->assertEquals($result, $actual);
});

it("checks db()->delete() function", function () {
    $user = db()->create('user');
    $user->name = faker()->name;
    $id = $user->save();
    $user->delete();
    $stmt = db()->connection->prepare("SELECT * FROM user where id = ".$id);
    $result = $stmt->executeQuery()->fetchAssociative();
    $this->assertEmpty($result);
});
