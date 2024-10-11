<?php

use function Pest\Faker\fake;

covers(Scrawler\Arca\QueryBuilder::class);
covers(Scrawler\Arca\Manager\ModelManager::class);

beforeAll(function (): void {
    db()->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
});
afterAll(function (): void {
    db()->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
});

afterEach(function (): void {
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS parent_user CASCADE; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS user CASCADE; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS parent CASCADE; ');
});

it('checks if db()->find()->first() returns first record', function ($useUUID): void {
    $id = createRandomUser($useUUID);
    $user = db($useUUID)->find('user')->first();
    $stmt = db($useUUID)->getConnection()->prepare("SELECT * FROM user WHERE id = '".$id."'");
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $user->toString()
    );
    $this->assertInstanceOf(Scrawler\Arca\Model::class, $user);
})->with('useUUID');

it('checks if null is returned if table does not exist', function (): void {
    $this->assertNull(db()->find('non_existent_table')->first());
    $this->assertInstanceOf(Scrawler\Arca\Collection::class, db()->find('non_existent_table')->get());
    $this->assertEmpty(db()->find('non_existent_table')->get()->toArray());
});

it('checks if null is returned if table empty', function (): void {
    $user = db()->create('user');
    $user->name = fake()->name();
    $user->email = fake()->email();
    $user->save();
    $user->delete();

    $this->assertNull(db()->find('user')->first());
    $this->assertInstanceOf(Scrawler\Arca\Collection::class, db()->find('user')->get());
    $this->assertEmpty(db()->find('user')->get()->toArray());
});
