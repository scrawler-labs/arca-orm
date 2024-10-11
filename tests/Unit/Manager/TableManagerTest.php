<?php

use function Pest\Faker\fake;

covers(Scrawler\Arca\Manager\TableManager::class);
covers(Scrawler\Arca\Manager\TableConstraint::class);

beforeAll(function (): void {
    db()->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
});
afterAll(function (): void {
    db()->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
});

afterEach(function (): void {
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS user CASCADE; ');
});

it('tests table manger update functionality', function ($useUUID): void {
    createRandomUser($useUUID);

    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->email = fake()->email();
    $user->dob = fake()->date();
    $user->age = fake()->randomNumber(2, false);
    $user->active = fake()->randomNumber(2, false) % 2;
    $user->address = fake()->streetAddress();
    $user->rand = 'abc';
    $user->randbo = 0 === fake()->randomNumber(2, false) % 2;
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
    $requiredTable->addColumn('active', 'integer', ['notnull' => false, 'comment' => 'integer']);
    $requiredTable->addColumn('address', 'text', ['notnull' => false, 'comment' => 'text']);
    $requiredTable->addColumn('rand', 'text', ['notnull' => false, 'comment' => 'text']);
    $requiredTable->addColumn('randbo', 'boolean', ['notnull' => false, 'comment' => 'boolean']);

    $requiredTable->setPrimaryKey(['id']);

    $actual = new Doctrine\DBAL\Schema\Schema([$table]);
    $required = new Doctrine\DBAL\Schema\Schema([$requiredTable]);
    $comparator = db()->getConnection()->createSchemaManager()->createComparator();
    $diff = $comparator->compareSchemas($actual, $required);
    // print_r($diff->toSql(db()->platform));

    $this->assertEmpty(db($useUUID)->getConnection()->getDatabasePlatform()->getAlterSchemaSQL($diff));
})->with('useUUID');
