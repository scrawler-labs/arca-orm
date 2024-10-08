<?php

use function Pest\Faker\fake;

covers(\Scrawler\Arca\Model::class); 

beforeEach(function () {
    db()->getConnection()->executeStatement("DROP TABLE IF EXISTS user; ");
    db()->getConnection()->executeStatement("DROP TABLE IF EXISTS parent; ");
    db()->getConnection()->executeStatement("DROP TABLE IF EXISTS parent_user; ");
    db()->getConnection()->executeStatement("DROP TABLE IF EXISTS grandparent; ");

});

it('tests model properties with multiple realtions',function ($useUUID){
    $child1 = db($useUUID)->create('user');
    $child1->name = fake()->name();
    $child1->email = fake()->email();
    $child1->dob = fake()->date();
    $child1->age = fake()->randomNumber(2, false);
    
    $child2 = db($useUUID)->create('user');
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
    $parent->grandparent= $grandfater;
    $parent->ownUserList = [$child1,$child2];
    $parent->sharedUserList = [$child3];
    $id = $parent->save();

    $parent_retrived = db($useUUID)->getOne('parent', $id);
    $this->assertEquals($parent->name, $parent_retrived->name);
    $this->assertTrue(isset($parent_retrived->grandparent_id));
    $this->assertFalse(isset($parent_retrived->grandparent));
    $this->assertEquals($grandfater->name, $parent_retrived->grandparent->name);
    $this->assertFalse(isset($parent_retrived->grandparent_id));
    $this->assertTrue(isset($parent_retrived->grandparent));
    $this->assertEquals(count($parent_retrived->ownUserList),2);


    $this->assertEquals($parent_retrived->ownUserList->first()->name,$child1->name);
    $this->assertEquals(count($parent_retrived->sharedUserList),1);
    $this->assertEquals($parent_retrived->sharedUserList->first()->name,$child3->name);



})->with('useUUID');