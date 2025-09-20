<?php

covers(Scrawler\Arca\Model::class);
covers(Scrawler\Arca\Database::class);
covers(Scrawler\Arca\Factory\DatabaseFactory::class);
covers(Scrawler\Arca\Manager\TableManager::class);
covers(Scrawler\Arca\Manager\RecordManager::class);

beforeAll(function (): void {
    db()->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
});

afterAll(function (): void {
    db()->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
});

afterEach(function (): void {
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS grandparent CASCADE; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS parent_user CASCADE; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS parent CASCADE; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS user CASCADE; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS child CASCADE; ');
});

// Note: Helper functions moved to TestHelpers.php to avoid redeclaration errors

function assertModelIsLoaded(object $model): void
{
    expect($model->isLoaded())->toBeTrue();
}

function assertRelationshipPropertiesWork(object $parent, object $grandparent, array $children, array $users): void
{
    // Assert grandparent relationship works correctly
    expect(isset($parent->grandparent_id))->toBeTrue();
    expect(isset($parent->grandparent))->toBeFalse();

    // Accessing grandparent should load it and change property availability
    expect($parent->grandparent->name)->toEqual($grandparent->name);
    expect(isset($parent->grandparent_id))->toBeFalse();
    expect(isset($parent->grandparent))->toBeTrue();

    // Assert one-to-many relationship (ownChildList)
    expect($parent->ownChildList)->toHaveCount(2);
    $firstChildName = $parent->ownChildList->first()->name;
    expect($firstChildName)->toBeIn([$children[0]->name, $children[1]->name]);

    // Assert many-to-many relationship (sharedUserList)
    expect($parent->sharedUserList)->toHaveCount(1);
    expect($parent->sharedUserList->first()->name)->toEqual($users[0]->name);
}

describe('Model Property Tests', function (): void {
    describe('Complex Relationship Properties', function (): void {
        it('handles multiple relationship types correctly', function (string $useUUID): void {
            // Arrange: Create models with different relationship types
            $child1 = createTestChild($useUUID);
            $child2 = createTestChild($useUUID);
            $user = createTestUser($useUUID);
            $grandparent = createTestGrandparent($useUUID);

            $parent = createTestParent($useUUID);
            $parent->grandparent = $grandparent;       // One-to-One relationship
            $parent->ownChildList = [$child1, $child2]; // One-to-Many relationship
            $parent->sharedUserList = [$user];         // Many-to-Many relationship

            // Act: Save the parent with all relationships
            $parentId = $parent->save();

            // Assert: All related models should be loaded
            assertModelIsLoaded($child1);
            assertModelIsLoaded($child2);
            assertModelIsLoaded($user);
            assertModelIsLoaded($grandparent);

            // Act: Retrieve the parent and test properties
            $retrievedParent = db($useUUID)->getOne('parent', $parentId);

            // Assert: Basic properties should match
            expect($retrievedParent->name)->toEqual($parent->name);

            // Assert: Complex relationship properties work correctly
            assertRelationshipPropertiesWork(
                $retrievedParent,
                $grandparent,
                [$child1, $child2],
                [$user]
            );
        })->with([
            'UUID',
            'ID',
        ]);
    });
});
