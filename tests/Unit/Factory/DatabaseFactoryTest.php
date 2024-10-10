<?php
covers(Scrawler\Arca\Factory\DatabaseFactory::class);
covers(Scrawler\Arca\Config::class);
beforeEach(function () {
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS user; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS parent; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS parent_user; ');
});


it('tests proper initialization of database', function ($useUUID) {
    $factory = new Scrawler\Arca\Factory\DatabaseFactory();

    $db = $factory->build(getConnectionParams($useUUID));
    $this->assertInstanceOf(Scrawler\Arca\Database::class, $db);
    $this->assertEquals($useUUID == 'UUID', $db->isUsingUUID());

    $db = $factory->build(getConnectionParams($useUUID,false));
    $this->assertInstanceOf(Scrawler\Arca\Database::class, $db);
    $this->assertEquals(false, $db->isUsingUUID());

    
})->with('useUUID');

it('tests proper initialization of database with container provided', function ($useUUID) {
    $factory = new Scrawler\Arca\Factory\DatabaseFactory(container: new \DI\Container());

    $db = $factory->build(getConnectionParams($useUUID));
    $this->assertInstanceOf(Scrawler\Arca\Database::class, $db);
    $this->assertEquals($useUUID == 'UUID', $db->isUsingUUID());

    $db = $factory->build(getConnectionParams($useUUID,false));
    $this->assertInstanceOf(Scrawler\Arca\Database::class, $db);
    $this->assertEquals(false, $db->isUsingUUID());

    
})->with('useUUID');