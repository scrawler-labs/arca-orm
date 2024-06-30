<?php
use function Pest\Faker\fake;

 beforeEach(function () {
    db()->connection->executeStatement("DROP TABLE IF EXISTS user; ");
    db()->connection->executeStatement("DROP TABLE IF EXISTS parent; ");
    db()->connection->executeStatement("DROP TABLE IF EXISTS parent_user; ");
});

it('tests table manger update functionality',function($useUUID){
    createRandomUser($useUUID);

    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->email = fake()->email();
    $user->dob = fake()->date();
    $user->age = fake()->randomNumber(2, false);
    $user->active = fake()->randomNumber(2, false) % 2 == 0 ? true : false;
    $user->address = fake()->streetAddress();
    $user->rand = 'abc';
    $id = $user->save();

    $table= db($useUUID)->connection->getSchemaManager()->introspectTable('user');
    $requiredTable = new \Doctrine\DBAL\Schema\Table('user');
    if (db($useUUID)->isUsingUUID()) {
        $requiredTable->addColumn('id', 'string', array('length' => 36,'notnull' => true));
    } else {
        $requiredTable->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
    }
    $requiredTable->addColumn('name', "text", ['notnull' => false, 'comment' => 'name']);
    $requiredTable->addColumn('email', "text", ['notnull' => false, 'comment' => 'email']);
    $requiredTable->addColumn('dob', "text", ['notnull' => false, 'comment' => 'dob']);
    $requiredTable->addColumn('age', "integer", ['notnull' => false, 'comment' => 'age']);
    $requiredTable->addColumn('active', "integer", ['notnull' => false, 'comment' => 'active']);
    $requiredTable->addColumn('address', "text", ['notnull' => false, 'comment' => 'address']);
    $requiredTable->addColumn('rand', "text", ['notnull' => false, 'comment' => 'rand']);

    $requiredTable->setPrimaryKey(array("id"));

    $actual = new \Doctrine\DBAL\Schema\Schema([$table]);
    $required = new \Doctrine\DBAL\Schema\Schema([$requiredTable]);
    $comparator = db()->connection->getSchemaManager()->createComparator();
    $diff = $comparator->compareSchemas($actual, $required);
    //print_r($diff->toSql(db()->platform));

    $this->assertEmpty(db($useUUID)->connection->getPlatform()->getAlterSchemaSQL($diff));
})->with('useUUID');