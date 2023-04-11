<?php
use function Pest\Faker\fake;

 beforeEach(function () {
    db()->connection->query("DROP TABLE IF EXISTS user; ");
    db()->connection->query("DROP TABLE IF EXISTS parent; ");
    db()->connection->query("DROP TABLE IF EXISTS parent_user; ");
});

it('tests DB::create()',function(){
    db();
    $user = \Scrawler\Arca\Facade\Database::create('user');
    $model =  new \Scrawler\Arca\Model('user',db());
    $this->assertObjectEquals($model, $user);
});

it('tests DB::get()',function(){
    db();
    populateRandomUser();
    $id = createRandomUser();
    $user = \Scrawler\Arca\Facade\Database::get('user', $id);

    $stmt = db()->connection->prepare("SELECT * FROM user WHERE id = '".$id."'");
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $user->toString()
    );
    $this->assertIsString((string) $user);
    $this->assertInstanceOf(\Scrawler\Arca\Model::class, $user);
});

it('tests DB::getOne()',function(){
    db();
    $id = createRandomUser();
    $user = \Scrawler\Arca\Facade\Database::getOne('user', $id);

    $stmt = db()->connection->prepare("SELECT * FROM user WHERE id = '".$id."'");
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $user->toString()
    );
    $this->assertIsString((string) $user);
    $this->assertInstanceOf(\Scrawler\Arca\Model::class, $user);
});

it('test DB::exec()',function(){
    $user = \Scrawler\Arca\Facade\Database::create('user');
    $user->name = fake()->name();
    $user->save();

    \Scrawler\Arca\Facade\Database::exec("insert into user (name) values ('john')");
    $id = 2;

    $stmt = db()->connection->prepare("SELECT * FROM user where id = '".$id."'");
    $result = $stmt->executeQuery()->fetchAssociative();
    $this->assertEquals($result['name'], "john");
});

it('test DB::delete()',function(){
    $user = \Scrawler\Arca\Facade\Database::create('user');
    $user->name = fake()->name();
    $id = $user->save();
    \Scrawler\Arca\Facade\Database::delete($user);
    $stmt = db()->connection->prepare("SELECT * FROM user where id = '".$id."'");
    $result = $stmt->executeQuery()->fetchAssociative();
    $this->assertEmpty($result);
});

it('test DB::find()',function(){
    populateRandomUser();
    $users = \Scrawler\Arca\Facade\Database::find('user')->where('active = 1')->get();
    $stmt = db()->connection->prepare("SELECT * FROM user WHERE active = 1");
    $result = json_encode($stmt->executeQuery()->fetchAllAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $users->toString()
    );
    $this->assertInstanceOf(\Scrawler\Arca\Collection::class, $users);
});