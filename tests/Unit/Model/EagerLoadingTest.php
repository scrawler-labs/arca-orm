<?php

covers(Scrawler\Arca\Model::class);

beforeAll(function (): void {
    db()->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
});

afterAll(function (): void {
    db()->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
});

beforeEach(function (): void {
    // Clean up both configurations before each test to ensure complete isolation
    foreach (['UUID', 'ID'] as $config) {
        try {
            cleanupTestTables($config);
        } catch (Exception) {
            // Ignore errors during cleanup
        }
    }
});

afterEach(function (): void {
    // Clean up both UUID and ID configurations to prevent schema conflicts
    foreach (['UUID', 'ID'] as $config) {
        try {
            cleanupTestTables($config);
        } catch (Exception) {
            // Ignore errors during cleanup
        }
    }
});

// Note: Helper functions moved to TestHelpers.php to avoid redeclaration errors

function assertEagerLoadingWorks(object $modelWithRelations, object $modelWithoutRelations, string $relationProperty): void
{
    // Before accessing relation: models should be different
    expect($modelWithRelations->toString())->not->toEqual($modelWithoutRelations->toString());

    // After accessing relation: models should be the same
    $modelWithoutRelations->$relationProperty; // This triggers lazy loading
    expect($modelWithRelations->toString())->toEqual($modelWithoutRelations->toString());
}

describe('Eager Loading Tests', function (): void {
    describe('One-to-One Relationships', function (): void {
        it('loads one-to-one relation eagerly with with() method', function (string $useUUID): void {
            // Arrange: Create user and parent with one-to-one relationship
            $user = createTestUser($useUUID);
            $parent = createTestParent($useUUID);
            $parent->user = $user;

            $id = $parent->save();

            // Act: Retrieve parent with and without eager loading
            $parentWithEagerLoading = db($useUUID)->getOne('parent', $id)->with(['user']);
            $parentWithoutEagerLoading = db($useUUID)->getOne('parent', $id);

            // Assert: Eager loading should work correctly
            assertEagerLoadingWorks($parentWithEagerLoading, $parentWithoutEagerLoading, 'user');
        })->with([
            'UUID',
            'ID',
        ]);
    });

    describe('One-to-Many Relationships', function (): void {
        it('loads one-to-many relation eagerly with with() method', function (string $useUUID): void {
            // Arrange: Create parent with multiple users (one-to-many)
            $user1 = createTestUser($useUUID);
            $user2 = createTestUser($useUUID);

            $parent = createTestParent($useUUID);
            $parent->ownUserList = [$user1, $user2];

            $id = $parent->save();

            // Act: Retrieve parent with and without eager loading
            $parentWithEagerLoading = db($useUUID)->getOne('parent', $id)->with(['ownUserList']);
            $parentWithoutEagerLoading = db($useUUID)->getOne('parent', $id);

            // Assert: Eager loading should work correctly
            assertEagerLoadingWorks($parentWithEagerLoading, $parentWithoutEagerLoading, 'ownUserList');
        })->with([
            'UUID',
            'ID',
        ]);
    });

    describe('Many-to-Many Relationships', function (): void {
        it('loads many-to-many relation eagerly with with() method', function (string $useUUID): void {
            // Arrange: Create multiple parents sharing users (many-to-many)
            $user1 = createTestUser($useUUID);
            $user2 = createTestUser($useUUID);

            // Save users first to ensure they exist in the database
            $user1Id = $user1->save();
            $user2Id = $user2->save();

            // Retrieve saved users to ensure they have proper IDs
            $savedUser1 = db($useUUID)->getOne('user', $user1Id);
            $savedUser2 = db($useUUID)->getOne('user', $user2Id);

            $parent1 = createTestParent($useUUID);
            $parent1->sharedUserList = [$savedUser1, $savedUser2];

            $parent2 = createTestParent($useUUID);
            $parent2->sharedUserList = [$savedUser1];

            $id1 = $parent1->save();
            $id2 = $parent2->save();

            // Act & Assert: Test first parent
            $parent1WithEagerLoading = db($useUUID)->getOne('parent', $id1)->with(['sharedUserList']);
            $parent1WithoutEagerLoading = db($useUUID)->getOne('parent', $id1);

            assertEagerLoadingWorks($parent1WithEagerLoading, $parent1WithoutEagerLoading, 'sharedUserList');

            // Act & Assert: Test second parent
            $parent2WithEagerLoading = db($useUUID)->getOne('parent', $id2)->with(['sharedUserList']);
            $parent2WithoutEagerLoading = db($useUUID)->getOne('parent', $id2);

            assertEagerLoadingWorks($parent2WithEagerLoading, $parent2WithoutEagerLoading, 'sharedUserList');
        })->with([
            'UUID',
            'ID',
        ]);
    });
});
