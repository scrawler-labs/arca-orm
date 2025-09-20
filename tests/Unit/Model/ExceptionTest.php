<?php

covers(Scrawler\Arca\Model::class);
covers(Scrawler\Arca\Database::class);
covers(Scrawler\Arca\Manager\WriteManager::class);
covers(Scrawler\Arca\Exception\InvalidIdException::class);
covers(Scrawler\Arca\Exception\InvalidModelException::class);
covers(Scrawler\Arca\Exception\KeyNotFoundException::class);

beforeAll(function (): void {
    db()->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
});

afterAll(function (): void {
    db()->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
});

afterEach(function (): void {
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS parent_user CASCADE; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS parent CASCADE; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS user CASCADE; ');
});

// Note: Helper functions moved to TestHelpers.php to avoid redeclaration errors

describe('Model Exception Tests', function (): void {
    describe('Key Access Exceptions', function (): void {
        it('throws KeyNotFoundException when accessing non-existent key', function (string $useUUID): void {
            // Arrange: Create and save a user
            $id = createRandomUser($useUUID);
            $user = db($useUUID)->getOne('user', $id);

            // Act & Assert: Accessing non-existent key should throw exception
            $user->somekey; // This should trigger the exception
        })->with([
            'UUID',
            'ID',
        ])->throws(Scrawler\Arca\Exception\KeyNotFoundException::class, 'Key you are trying to access does not exist');
    });

    describe('ID Setting Exceptions', function (): void {
        it('throws InvalidIdException when force setting id on model', function (string $useUUID): void {
            // Arrange: Create a new user model
            $user = createTestUser($useUUID);

            // Act & Assert: Force setting ID should throw exception
            $user->id = 1; // This should trigger the exception
            $user->save();
        })->with([
            'UUID',
            'ID',
        ])->throws(Scrawler\Arca\Exception\InvalidIdException::class, 'Force setting of id for model is not allowed');
    });

    describe('Invalid Model Exceptions', function (): void {
        it('throws InvalidModelException when sharedUserList contains non-model values', function (string $useUUID): void {
            // Arrange: Create a parent model
            $parent = createTestParent($useUUID);

            // Act & Assert: Setting shared list with non-model values should throw exception
            $parent->sharedUserList = ['test', 'test1']; // Should be array of models
            $parent->save();
        })->with([
            'UUID',
            'ID',
        ])->throws(Scrawler\Arca\Exception\InvalidModelException::class);

        it('throws InvalidModelException when ownUserList contains non-model values', function (string $useUUID): void {
            // Arrange: Create a parent model
            $parent = createTestParent($useUUID);

            // Act & Assert: Setting own list with non-model values should throw exception
            $parent->ownUserList = ['test', 'test1']; // Should be array of models
            $parent->save();
        })->with([
            'UUID',
            'ID',
        ])->throws(Scrawler\Arca\Exception\InvalidModelException::class, 'parameter passed to shared list or own list should be array of class \Arca\Model');
    });
});
