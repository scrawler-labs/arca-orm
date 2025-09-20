<?php

use function Pest\Faker\fake;

covers(Scrawler\Arca\Model::class);
covers(Scrawler\Arca\Database::class);
covers(Scrawler\Arca\Manager\RecordManager::class);

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

function assertUserPropertiesMatch(object $user1, object $user2): void
{
    expect($user1->name)->toEqual($user2->name);
    expect($user1->email)->toEqual($user2->email);
}

describe('Model Tests', function (): void {
    describe('Model Data Population', function (): void {
        it('properly populates model data on retrieval', function (string $useUUID): void {
            // Arrange: Create and save a user
            $id = createRandomUser($useUUID);

            // Act: Retrieve user model and database record
            $user = db($useUUID)->getOne('user', $id);
            $stmt = db($useUUID)->getConnection()->prepare("SELECT * FROM user WHERE id = '".$id."'");
            $result = $stmt->executeQuery()->fetchAssociative();

            // Assert: Model properties should match database record
            expect($user->name)->toEqual($result['name']);
            expect($user->getProperties())->toEqual($result);
        })->with([
            'UUID',
            'ID',
        ]);
    });

    describe('Model Equality', function (): void {
        it('returns true when comparing equal models', function (string $useUUID): void {
            // Arrange: Create and retrieve same user twice
            $id = createRandomUser($useUUID);
            $user1 = db($useUUID)->getOne('user', $id);
            $user2 = db($useUUID)->getOne('user', $id);

            // Assert: Models should be equal
            expect($user1->equals($user2))->toBeTrue();
        })->with([
            'UUID',
            'ID',
        ]);

        it('returns false when comparing different models', function (string $useUUID): void {
            // Arrange: Create and modify one model
            $id = createRandomUser($useUUID);
            $user1 = db($useUUID)->getOne('user', $id);
            $user2 = db($useUUID)->getOne('user', $id);
            $user2->name = 'test';

            // Assert: Models should not be equal
            expect($user1->equals($user2))->toBeFalse();
        })->with([
            'UUID',
            'ID',
        ]);
    });

    describe('Model Property Access', function (): void {
        it('correctly implements isset() for model properties', function (string $useUUID): void {
            // Arrange: Create and retrieve a user
            $id = createRandomUser($useUUID);
            $user = db($useUUID)->getOne('user', $id);

            // Assert: isset should work correctly
            expect(isset($user->name))->toBeTrue();
            expect(isset($user->somekey))->toBeFalse();
        })->with([
            'UUID',
            'ID',
        ]);
    });

    describe('Bulk Property Operations', function (): void {
        it('allows bulk property setting via setProperties()', function (string $useUUID): void {
            // Arrange: Create a new user model
            $user = db($useUUID)->create('user');
            $properties = [
                'name' => fake()->name(),
                'email' => fake()->email(),
                'dob' => fake()->date(),
                'age' => fake()->randomNumber(2, false),
                'address' => fake()->streetAddress(),
            ];

            // Act: Set properties in bulk and save
            $user->setProperties($properties);
            $id = $user->save();

            // Assert: Retrieved model should have same properties
            $retrievedUser = db($useUUID)->getOne('user', $id);
            assertUserPropertiesMatch($user, $retrievedUser);
        })->with([
            'UUID',
            'ID',
        ]);
    });

    describe('Model Lifecycle Operations', function (): void {
        it('successfully deletes model records', function (string $useUUID): void {
            // Arrange: Create and save a user
            $user = createTestUser($useUUID);
            $id = $user->save();

            // Act: Delete the user
            $user->delete();

            // Assert: User should no longer exist
            expect(db($useUUID)->getOne('user', $id))->toBeNull();
        })->with([
            'UUID',
            'ID',
        ]);

        it('properly initializes clean state on model load', function (string $useUUID): void {
            // Arrange: Create, save, and reload a user
            $user = createTestUser($useUUID);
            $id = $user->save();
            $reloadedUser = db($useUUID)->getOne('user', $id);

            // Assert: Model should be in clean state
            expect($reloadedUser->hasIdError())->toBeFalse();
            expect($reloadedUser->hasForeign('oto'))->toBeFalse();
            expect($reloadedUser->hasForeign('otm'))->toBeFalse();
            expect($reloadedUser->hasForeign('mtm'))->toBeFalse();
        })->with([
            'UUID',
            'ID',
        ]);
    });

    describe('Model ID Management', function (): void {
        it('manages model ID correctly during lifecycle', function (string $useUUID): void {
            // Arrange: Create a new user model
            $user = createTestUser($useUUID);

            // Assert: ID should be 0 before save
            expect($user->getId())->toEqual(0);

            // Act: Save the user
            $id = $user->save();

            // Assert: ID should be set after save
            expect($user->getId())->not->toEqual(0);
            expect($user->getId())->toEqual($id);
        })->with([
            'UUID',
            'ID',
        ]);
    });

    describe('Model Refresh Operations', function (): void {
        it('refreshes model data from database', function (string $useUUID): void {
            // Arrange: Create parent with user relationship
            $user = createTestUser($useUUID);
            $parent = createTestParent($useUUID);
            $parent->ownUserList = [$user];
            $parentId = $parent->save();

            // Act: Retrieve parent and modify child user
            $retrievedParent = db($useUUID)->getOne('parent', $parentId);
            $originalName = $retrievedParent->ownUserList->first()->name;

            $childUser = $retrievedParent->ownUserList->first();
            $childUser->name = 'test';
            $childUser->save();

            // Assert: Parent should still have old data until refresh
            expect($retrievedParent->ownUserList->first()->name)->not->toEqual('test');

            // Act: Refresh parent
            $retrievedParent->refresh();

            // Assert: Parent should now have updated data
            expect($retrievedParent->ownUserList->first()->name)->toEqual('test');
        })->with([
            'UUID',
            'ID',
        ]);
    });
});
