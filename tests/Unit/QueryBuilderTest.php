<?php
 beforeAll(function () {
     $connectionParams = array(
        'dbname' => 'test_database',
         'user' => 'admin',
         'password' => 'rootpass',
         'host' => '127.0.0.1',        
         'driver' => 'pdo_mysql',
   );
   
     $db = Scrawler\Arca\Facade\Database::connect($connectionParams);
     
 });

 beforeEach(function () {
     db()->connection->query("DROP TABLE IF EXISTS user; ");
     db()->connection->query("DROP TABLE IF EXISTS parent; ");
     db()->connection->query("DROP TABLE IF EXISTS parent_user; ");
 });

it("checks if db()->find()->first() returns first record", function ($useUUID) {
    $id = createRandomUser($useUUID);
    $user = db($useUUID)->find('user')->first();
    $stmt = db($useUUID)->connection->prepare("SELECT * FROM user WHERE id = '".$id."'");
    $result = json_encode($stmt->executeQuery()->fetchAssociative());
    $this->assertJsonStringEqualsJsonString(
        $result,
        $user->toString()
    );
    $this->assertInstanceOf(\Scrawler\Arca\Model::class, $user);
})->with('useUUID');
