<?php

use function Pest\Faker\fake;

covers(Scrawler\Arca\Model::class);
covers(Scrawler\Arca\Database::class);
covers(Scrawler\Arca\Factory\DatabaseFactory::class);
covers(Scrawler\Arca\Manager\TableManager::class);
covers(Scrawler\Arca\Manager\RecordManager::class);

beforeAll(function (): void {
    db()->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
});
afterAll(function (): void {
    db()->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
});

afterEach(function (): void {
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS grandparent CASCADE; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS parent_user CASCADE; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS parent CASCADE; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS user CASCADE; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS child CASCADE; ');
});

it('tests model properties with multiple realtions', function ($useUUID): void {
    $child1 = db($useUUID)->create('child');
    $child1->name = fake()->name();
    $child1->email = fake()->email();
    $child1->dob = fake()->date();
    $child1->age = fake()->randomNumber(2, false);

    $child2 = db($useUUID)->create('child');
    $child2->name = fake()->name();
    $child2->email = fake()->email();
    $child2->dob = fake()->date();
    $child2->age = fake()->randomNumber(2, false);

    $child3 = db($useUUID)->create('user');
    $child3->name = fake()->name();
    $child3->email = fake()->email();
    $child3->dob = fake()->date();
    $child3->age = fake()->randomNumber(2, false);

    $grandfater = db($useUUID)->create('grandparent');
    $grandfater->name = fake()->name();
    $grandfater->email = fake()->email();
    $grandfater->dob = fake()->date();
    $grandfater->age = fake()->randomNumber(2, false);

    $parent = db($useUUID)->create('parent');
    $parent->name = fake()->name();
    $parent->grandparent = $grandfater;
    $parent->ownChildList = [$child1, $child2];
    $parent->sharedUserList = [$child3];
    $id = $parent->save();

    $this->assertTrue($child1->isLoaded());
    $this->assertTrue($child2->isLoaded());
    $this->assertTrue($child3->isLoaded());
    $this->assertTrue($grandfater->isLoaded());

    $parent_retrived = db($useUUID)->getOne('parent', $id);
    $this->assertEquals($parent->name, $parent_retrived->name);
    $this->assertTrue(isset($parent_retrived->grandparent_id));
    $this->assertFalse(isset($parent_retrived->grandparent));
    $this->assertEquals($grandfater->name, $parent_retrived->grandparent->name);
    $this->assertFalse(isset($parent_retrived->grandparent_id));
    $this->assertTrue(isset($parent_retrived->grandparent));
    $this->assertEquals(count($parent_retrived->ownChildList), 2);

    $this->assertTrue($parent_retrived->ownChildList->first()->name == $child1->name || $parent_retrived->ownChildList->first()->name == $child2->name);
    $this->assertEquals(count($parent_retrived->sharedUserList), 1);
    $this->assertEquals($parent_retrived->sharedUserList->first()->name, $child3->name);
})->with('useUUID');
