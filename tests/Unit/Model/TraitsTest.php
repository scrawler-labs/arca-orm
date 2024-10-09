<?php

use function Pest\Faker\fake;
covers(\Scrawler\Arca\Model::class); 

beforeEach(function () {
    db()->getConnection()->executeStatement("DROP TABLE IF EXISTS user; ");
    db()->getConnection()->executeStatement("DROP TABLE IF EXISTS parent; ");
    db()->getConnection()->executeStatement("DROP TABLE IF EXISTS parent_user; ");
});

it('tests model can be treated as iterable',function(){

    $model = db()->create('user');
    $model->name = fake()->name();
    $model->email = fake()->email();
    $model->dob = fake()->date();
    $model->age = fake()->randomNumber(2, false);
    $model->address = fake()->streetAddress();
    $model->save();

    $model = db()->find('user')->first();
    foreach($model as $key=>$value){
        $this->assertNotNull($key);
        $this->assertNotNull($value);
    }
});

it('tests model can be treated as Array',function(){

    $model = db()->create('user');
    $model->name = fake()->name();
    $model->email = fake()->email();
    $model->dob = fake()->date();
    $model->age = fake()->randomNumber(2, false);
    $model->address = fake()->streetAddress();
    $model->save();

    $model = db()->find('user')->first();
    $this->assertIsArray($model->toArray());
    $this->assertEquals($model['name'],$model->name);
    $this->assertTrue(isset($model['email']));
    unset($model['age']);
    $this->assertFalse($model->isset('age'));

    $model['age'] = 10;
    $this->assertEquals($model['age'],$model->age);

    expect(fn() => $model[] = 10)->toThrow(Exception::class);

});

it('tests if class is stringable',function(){
    $model = db()->create('user');
    $model->name = fake()->name();
    $model->email = fake()->email();
    $model->save();

    ob_start();
    echo $model;
    $data =  ob_get_clean();

    $this->assertEquals($data,$model->toString());
});