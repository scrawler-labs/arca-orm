<?php

use function Pest\Faker\fake;

covers(Scrawler\Arca\Model::class);
covers(Scrawler\Arca\Database::class);
covers(Scrawler\Arca\Manager\WriteManager::class);
covers(Scrawler\Arca\Exception\InvalidIdException::class);
covers(Scrawler\Arca\Exception\InvalidModelException::class);
covers(Scrawler\Arca\Exception\KeyNotFoundException::class);

beforeAll(function () {
    db()->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
});
afterAll(function () {
    db()->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
});

afterEach(function () {
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS parent_user CASCADE; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS parent CASCADE; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS user CASCADE; ');
});

it('check exception is thrown when non existent key is accessed', function ($useUUID) {
    $id = createRandomUser($useUUID);
    $user = db($useUUID)->getOne('user', $id);
    $user->somekey;
})->throws(Scrawler\Arca\Exception\KeyNotFoundException::class, 'Key you are trying to access does not exist')->with('useUUID');

it('checks exception is thrown when id is force set on a model', function ($useUUID) {
    $user = db($useUUID)->create('user');
    $user->id = 1;
    $user->name = fake()->name();
    $user->email = fake()->email();
    $user->save();
})->with('useUUID')->throws(Scrawler\Arca\Exception\InvalidIdException::class, 'Force setting of id for model is not allowed');

it('checks exception is thrown if share list is not array of model', function ($useUUID) {
    $parent = db($useUUID)->create('parent');
    $parent->name = fake()->name();
    $parent->sharedUserList = ['test', 'test1'];
    $id = $parent->save();
})->with('useUUID')->throws(Scrawler\Arca\Exception\InvalidModelException::class);

it('checks exception is thrown if own list is not array of model', function ($useUUID) {
    $parent = db($useUUID)->create('parent');
    $parent->name = fake()->name();
    $parent->ownUserList = ['test', 'test1'];
    $id = $parent->save();
})->with('useUUID')->throws(Scrawler\Arca\Exception\InvalidModelException::class, 'parameter passed to shared list or own list should be array of class \Arca\Model');
