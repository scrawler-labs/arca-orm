<?php
use function Pest\Faker\fake;
covers(\Scrawler\Arca\Facade\Database::class);
covers(\Scrawler\Arca\Database::class);

 beforeEach(function () {
    db()->getConnection()->executeStatement("DROP TABLE IF EXISTS user; ");
    db()->getConnection()->executeStatement("DROP TABLE IF EXISTS parent; ");
    db()->getConnection()->executeStatement("DROP TABLE IF EXISTS parent_user; ");
});

it('tests DB::connect()',function(){
    $connectionParams = [
        'dbname' => 'test_database',
        'user' => 'admin',
        'password' => 'rootpass',
        'host' => '127.0.0.1',
        'driver' => 'pdo_mysql',
        'useUUID' => false,
    ];
    $db = \Scrawler\Arca\Facade\Database::connect($connectionParams);
    $this->assertInstanceOf(\Scrawler\Arca\Database::class, $db);

});

it('tests DB::create()',function(){
    $user = \Scrawler\Arca\Facade\Database::create('user');
   
    $this->assertInstanceOf(\Scrawler\Arca\Model::class, $user);
});

it('tests DB::get()',function(){
    populateRandomUser();
    $users = \Scrawler\Arca\Facade\Database::get('user');
    $stmt = db()->getConnection()->prepare("SELECT * FROM user");
    $result = json_encode($stmt->executeQuery()->fetchAllAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $users->toString()
    );
    $this->assertInstanceOf(\Scrawler\Arca\Collection::class, $users);
});

it('tests DB::getOne()',function(){
    db();
    $id = createRandomUser();
    $user = \Scrawler\Arca\Facade\Database::getOne('user', $id);

    $stmt = db()->getConnection()->prepare("SELECT * FROM user WHERE id = '".$id."'");
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

    $stmt = db()->getConnection()->prepare("SELECT * FROM user where id = '".$id."'");
    $result = $stmt->executeQuery()->fetchAssociative();
    $this->assertEquals($result['name'], "john");
});

it('test DB::delete()',function(){
    $user = \Scrawler\Arca\Facade\Database::create('user');
    $user->name = fake()->name();
    $id = $user->save();
    \Scrawler\Arca\Facade\Database::delete($user);
    $stmt = db()->getConnection()->prepare("SELECT * FROM user where id = '".$id."'");
    $result = $stmt->executeQuery()->fetchAssociative();
    $this->assertEmpty($result);
});

it('test DB::find()',function(){
    populateRandomUser();
    $users = \Scrawler\Arca\Facade\Database::find('user')->where('active = 1')->get();
    $stmt = db()->getConnection()->prepare("SELECT * FROM user WHERE active = 1");
    $result = json_encode($stmt->executeQuery()->fetchAllAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $users->toString()
    );
    $this->assertInstanceOf(\Scrawler\Arca\Collection::class, $users);
});