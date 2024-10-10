<?php

use function Pest\Faker\fake;

covers(Scrawler\Arca\Manager\TableManager::class);

beforeEach(function () {
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS user; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS parent; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS parent_user; ');
});

it('tests table manger update functionality', function ($useUUID) {
    createRandomUser($useUUID);

    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->email = fake()->email();
    $user->dob = fake()->date();
    $user->age = fake()->randomNumber(2, false);
    $user->active = 0 == fake()->randomNumber(2, false) % 2 ? true : false;
    $user->address = fake()->streetAddress();
    $user->rand = 'abc';
    $id = $user->save();

    $table = db($useUUID)->getConnection()->createSchemaManager()->introspectTable('user');
    $requiredTable = new Doctrine\DBAL\Schema\Table('user');
    if (db($useUUID)->isUsingUUID()) {
        $requiredTable->addColumn('id', 'string', ['length' => 36, 'notnull' => true, 'comment' => 'string']);
    } else {
        $requiredTable->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true, 'comment' => 'integer']);
    }
    $requiredTable->addColumn('name', 'text', ['notnull' => false, 'comment' => 'text']);
    $requiredTable->addColumn('email', 'text', ['notnull' => false, 'comment' => 'text']);
    $requiredTable->addColumn('dob', 'text', ['notnull' => false, 'comment' => 'text']);
    $requiredTable->addColumn('age', 'integer', ['notnull' => false, 'comment' => 'integer']);
    $requiredTable->addColumn('active', 'boolean', ['notnull' => false, 'comment' => 'boolean']);
    $requiredTable->addColumn('address', 'text', ['notnull' => false, 'comment' => 'text']);
    $requiredTable->addColumn('rand', 'text', ['notnull' => false, 'comment' => 'text']);

    $requiredTable->setPrimaryKey(['id']);

    $actual = new Doctrine\DBAL\Schema\Schema([$table]);
    $required = new Doctrine\DBAL\Schema\Schema([$requiredTable]);
    $comparator = db()->getConnection()->createSchemaManager()->createComparator();
    $diff = $comparator->compareSchemas($actual, $required);
    // print_r($diff->toSql(db()->platform));

    $this->assertEmpty(db($useUUID)->getConnection()->getDatabasePlatform()->getAlterSchemaSQL($diff));
})->with('useUUID');
