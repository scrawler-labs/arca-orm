<?php 
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use function Pest\Faker\fake;
use Doctrine\DBAL\Exception\DriverException;
covers(\Scrawler\Arca\Database::class); 
covers(\Scrawler\Arca\Manager\WriteManager::class); 
covers(\Scrawler\Arca\Manager\RecordManager::class);
covers(\Scrawler\Arca\Manager\TableManager::class);
covers(\Scrawler\Arca\Manager\ModelManager::class);


 beforeEach(function () {
     db()->getConnection()->executeStatement("DROP TABLE IF EXISTS user; ");
     db()->getConnection()->executeStatement("DROP TABLE IF EXISTS parent; ");
     db()->getConnection()->executeStatement("DROP TABLE IF EXISTS parent_user; ");
     db()->getConnection()->executeStatement("DROP TABLE IF EXISTS employee; ");
 });

 it("checks if db()->save() function creates table", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->email = fake()->email();
    $user->dob = fake()->date();
    $user->age = fake()->randomNumber(2, false);
    $user->active = false;

    $user->save();

    $table= db($useUUID)->getConnection()->createSchemaManager()->introspectTable('user');
    $requiredTable = new \Doctrine\DBAL\Schema\Table('user');
    if (db($useUUID)->isUsingUUID()) {
        $requiredTable->addColumn('id', 'string', array('length' => 36,'notnull' => true,'comment'=>'string'));
    } else {
        $requiredTable->addColumn('id', 'integer', array("unsigned" => true, "autoincrement" => true,'comment'=>'integer'));
    }
    $requiredTable->addColumn('name', "text", ['notnull' => false,'comment'=>'text']);
    $requiredTable->addColumn('email', "text", ['notnull' => false,'comment'=>'text' ]);
    $requiredTable->addColumn('dob', "text", ['notnull' => false, 'comment'=>'text']);
    $requiredTable->addColumn('age', "integer", ['notnull' => false, 'comment'=>'integer']);
    $requiredTable->addColumn('active', "boolean", ['notnull' => false, 'comment'=>'boolean']);

    $requiredTable->setPrimaryKey(array("id"));

    $actual = new \Doctrine\DBAL\Schema\Schema([$table]);
    $required = new \Doctrine\DBAL\Schema\Schema([$requiredTable]);
    $comparator = db($useUUID)->getConnection()->createSchemaManager()->createComparator();
    $diff = $comparator->compareSchemas($actual, $required);

    $this->assertEmpty(db($useUUID)->getConnection()->getDatabasePlatform()->getAlterSchemaSQL($diff));
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

it("checks for exception in database saveMtm()",function($useUUID){

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

it("checks for exception in database saveOtm()",function($useUUID){

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
    $parent->ownUserList = [$user,$user_two];
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


