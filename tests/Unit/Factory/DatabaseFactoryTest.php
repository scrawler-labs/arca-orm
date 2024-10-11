<?php

covers(Scrawler\Arca\Factory\DatabaseFactory::class);
covers(Scrawler\Arca\Manager\ModelManager::class);
covers(Scrawler\Arca\Manager\RecordManager::class);
covers(Scrawler\Arca\Manager\TableManager::class);
covers(Scrawler\Arca\Manager\WriteManager::class);
covers(Scrawler\Arca\Database::class);
covers(Scrawler\Arca\Config::class);

it('tests proper initialization of database', function ($useUUID): void {
    $factory = new Scrawler\Arca\Factory\DatabaseFactory();

    $db = $factory->build(getConnectionParams($useUUID));
    $this->assertInstanceOf(Scrawler\Arca\Database::class, $db);
    $this->assertEquals('UUID' == $useUUID, $db->isUsingUUID());

    $db = $factory->build(getConnectionParams($useUUID, false));
    $this->assertInstanceOf(Scrawler\Arca\Database::class, $db);
    $this->assertEquals(false, $db->isUsingUUID());
})->with('useUUID');

it('tests proper initialization of database with container provided', function ($useUUID): void {
    $factory = new Scrawler\Arca\Factory\DatabaseFactory(container: new DI\Container());

    $db = $factory->build(getConnectionParams($useUUID));
    $this->assertInstanceOf(Scrawler\Arca\Database::class, $db);
    $this->assertEquals('UUID' == $useUUID, $db->isUsingUUID());

    $db = $factory->build(getConnectionParams($useUUID, false));
    $this->assertInstanceOf(Scrawler\Arca\Database::class, $db);
    $this->assertEquals(false, $db->isUsingUUID());
})->with('useUUID');
