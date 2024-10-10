<?php
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use function Pest\Faker\fake;
use Doctrine\DBAL\Exception\DriverException;
covers(\Scrawler\Arca\Database::class); 
covers(\Scrawler\Arca\Manager\ModelManager::class);
covers(\Scrawler\Arca\Manager\RecordManager::class);
covers(\Scrawler\Arca\Config::class);


 beforeEach(function () {
     db()->getConnection()->executeStatement("DROP TABLE IF EXISTS user; ");
     db()->getConnection()->executeStatement("DROP TABLE IF EXISTS parent; ");
     db()->getConnection()->executeStatement("DROP TABLE IF EXISTS parent_user; ");
     db()->getConnection()->executeStatement("DROP TABLE IF EXISTS employee; ");
     db()->unfreeze();


 });

it(" checks db()->isUsingUUID() function ", function ($useUUID) {
    if($useUUID == 'UUID'){
        $this->assertTrue(db($useUUID)->isUsingUUID());
    }else{
        $this->assertFalse(db($useUUID)->isUsingUUID());
    }
   
})->with('useUUID');

it(" checks db()->create() function ", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $this->assertInstanceOf(\Scrawler\Arca\Model::class, $user);
})->with('useUUID');


it("checks if db()->getOne() gets single record", function ($useUUID) {
    populateRandomUser($useUUID);
    $id = createRandomUser($useUUID);
    $user = db($useUUID)->getOne('user', $id);

    $stmt = db($useUUID)->getConnection()->prepare("SELECT * FROM user WHERE id = '".$id."'");
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $user->toString()
    );
    $this->assertIsString((string) $user);
    $this->assertInstanceOf(\Scrawler\Arca\Model::class, $user);
})->with('useUUID');

it("checks if db()->get() gets all record", function ($useUUID) {
    populateRandomUser($useUUID);
    $users = db($useUUID)->get('user');
    $stmt = db($useUUID)->getConnection()->prepare("SELECT * FROM user");
    $result = json_encode($stmt->executeQuery()->fetchAllAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $users->toString()
    );
    $this->assertInstanceOf(\Scrawler\Arca\Collection::class, $users);
})->with('useUUID');

it("checks if db()->find() returns Query Builder", function () {
    $this->assertInstanceOf(\Scrawler\Arca\QueryBuilder::class, db()->find('user'));
    $this->assertInstanceOf(\Scrawler\Arca\QueryBuilder::class, db()->find('user')->where('id = 2'));
});

it("checks if db()->find() returns correct records", function () {
    populateRandomUser();
    $users = db()->find('user')->where('active = 1')->get();
    $stmt = db()->getConnection()->prepare("SELECT * FROM user WHERE active = 1");
    $result = json_encode($stmt->executeQuery()->fetchAllAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $users->toString()
    );
    $this->assertInstanceOf(\Scrawler\Arca\Collection::class, $users);
});


it("checks if all public instance of database files are correct", function () {
    $this->assertInstanceOf(\Doctrine\DBAL\Connection::class, db()->getConnection());
});

it("checks  db()->exec() function", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->save();
    if (db($useUUID)->isUsingUUID()) {
        db($useUUID)->exec("insert into user (id,name) values ('abc-jfke-dmsk','john')");
        $id = 'abc-jfke-dmsk';
    } else {
        db($useUUID)->exec("insert into user (name) values ('john')");
        $id = 2;
    }

    $stmt = db($useUUID)->getConnection()->prepare("SELECT * FROM user where id = '".$id."'");
    $result = $stmt->executeQuery()->fetchAssociative();
    $this->assertEquals($result['name'], "john");
})->with('useUUID');

it("checks  db()->getAll() function", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->save();

    $stmt = db($useUUID)->getConnection()->prepare("SELECT * FROM user");
    $result = $stmt->executeQuery()->fetchAllAssociative();

    $actual = db($useUUID)->getAll("SELECT * FROM user");

    $this->assertEquals($result, $actual);
})->with('useUUID');

it("checks db()->delete() function", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $id = $user->save();
    $user->delete();
    $stmt = db($useUUID)->getConnection()->prepare("SELECT * FROM user where id = '".$id."'");
    $result = $stmt->executeQuery()->fetchAssociative();
    $this->assertEmpty($result);
})->with('useUUID');

it("checks db()->tableExists() function", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->save();

    $this->assertTrue(db($useUUID)->tableExists('user'));
   
})->with('useUUID');

it("checks db()->tabelsExist() function", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->save();

    $emp = db($useUUID)->create('employee');
    $emp->name = fake()->name();
    $emp->save();


    $this->assertTrue(db($useUUID)->tablesExist(['user','employee']));
   
})->with('useUUID');

it("checks frozen database", function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->save();

    db($useUUID)->freeze();
    $user = db($useUUID)->create('user');
    $user->name = fake()->name();
    $user->email = fake()->email();
   
    expect(fn() =>  $user->save())->toThrow(InvalidFieldNameException::class);

    db($useUUID)->unfreeze();
   
})->with('useUUID');

it("checks for is UUID",function($uuid){
    $val = db($uuid)->isUsingUUID();
    $this->assertEquals($val,$uuid == 'UUID');

})->with('useUUID');



