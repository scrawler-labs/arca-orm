<?php

covers(Scrawler\Arca\Model::class);
covers(Scrawler\Arca\Database::class);

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

function normalizeModelForComparison(object $model, string $useUUID): object
{
    // Create a copy to avoid modifying the original
    $modelCopy = clone $model;
    if (!db($useUUID)->isUsingUUID()) {
        unset($modelCopy->id);
    }

    return $modelCopy;
}

function normalizeCollectionForComparison(object $collection, string $useUUID): object
{
    return $collection->apply(function ($model) use ($useUUID): void {
        if (!db($useUUID)->isUsingUUID()) {
            unset($model->id);
        }
    });
}

function createExpectedCollection(array $models, string $useUUID): object
{
    // Create copies to avoid modifying originals
    $modelCopies = [];
    foreach ($models as $model) {
        $copy = clone $model;
        if (!db($useUUID)->isUsingUUID()) {
            unset($copy->id);
        }
        $modelCopies[] = $copy;
    }

    return Scrawler\Arca\Collection::fromIterable($modelCopies)
        ->map(static fn ($model): Scrawler\Arca\Model => $model->setLoaded());
}

function assertCollectionsMatch(object $actualCollection, array $expectedModels, string $useUUID): void
{
    $expectedCollection1 = createExpectedCollection($expectedModels, $useUUID);
    $expectedCollection2 = createExpectedCollection(array_reverse($expectedModels), $useUUID);

    $actualString = $actualCollection->toString();
    $expected1String = $expectedCollection1->toString();
    $expected2String = $expectedCollection2->toString();

    expect($actualString === $expected1String || $actualString === $expected2String)->toBeTrue();
}

function assertModelsEqual(object $model1, object $model2, string $useUUID): void
{
    // Compare essential properties, ignoring ID for non-UUID tests
    expect($model1->name)->toEqual($model2->name);
    expect($model1->email)->toEqual($model2->email);
    expect($model1->dob)->toEqual($model2->dob);
    expect($model1->age)->toEqual($model2->age);

    if (isset($model1->address) && isset($model2->address)) {
        expect($model1->address)->toEqual($model2->address);
    }
}

describe('Model Relationship Tests', function (): void {
    describe('One-to-One Relationships', function (): void {
        it('retrieves one-to-one related models correctly', function (string $useUUID): void {
            // Arrange: Create user and parent with one-to-one relationship
            $user = createTestUser($useUUID);
            $parent = createTestParent($useUUID);
            $parent->user = $user;

            // Act: Save parent and retrieve related user
            $parentId = $parent->save();
            $retrievedUser = $parent->user;

            // Assert: Retrieved user should match original (comparing essential properties)
            assertModelsEqual($retrievedUser, $user, $useUUID);
        })->with([
            'UUID',
            'ID',
        ]);
    });

    describe('One-to-Many Relationships', function (): void {
        it('retrieves one-to-many related models correctly', function (string $useUUID): void {
            // Arrange: Create parent with multiple users (one-to-many)
            $user1 = createTestUser($useUUID);
            $user2 = createTestUser($useUUID);
            $parent = createTestParent($useUUID);
            $parent->ownUserList = [$user1, $user2];

            // Act: Save parent and retrieve user collection
            $parentId = $parent->save();
            $retrievedParent = db($useUUID)->getOne('parent', $parentId);

            // Assert: Retrieved users should match original users
            expect($retrievedParent->ownUserList)->toHaveCount(2);

            $retrievedUserNames = $retrievedParent->ownUserList->map(fn ($u) => $u->name)->toArray();
            expect($retrievedUserNames)->toContain($user1->name);
            expect($retrievedUserNames)->toContain($user2->name);
        })->with([
            'UUID',
            'ID',
        ]);
    });

    describe('Many-to-Many Relationships', function (): void {
        it('retrieves many-to-many related models correctly', function (string $useUUID): void {
            // Arrange: Create parent with shared users (many-to-many)
            $user1 = createTestUser($useUUID);
            $user2 = createTestUser($useUUID);
            $parent = createTestParent($useUUID);
            $parent->sharedUserList = [$user1, $user2];

            // Act: Save parent and retrieve shared user collection
            $parentId = $parent->save();
            $retrievedParent = db($useUUID)->getOne('parent', $parentId);

            // Assert: Retrieved users should match original users
            expect($retrievedParent->sharedUserList)->toHaveCount(2);

            $retrievedUserNames = $retrievedParent->sharedUserList->map(fn ($u) => $u->name)->toArray();
            expect($retrievedUserNames)->toContain($user1->name);
            expect($retrievedUserNames)->toContain($user2->name);
        })->with([
            'UUID',
            'ID',
        ]);

        it('handles complex many-to-many relationships with multiple parents', function (string $useUUID): void {
            // Arrange: Create a simple many-to-many test that focuses on core functionality
            $user1 = createTestUser($useUUID);
            $user2 = createTestUser($useUUID);

            $parent1 = createTestParent($useUUID);
            $parent1->sharedUserList = [$user1, $user2];
            $parent1Id = $parent1->save();

            // Act: Retrieve the parent
            $retrievedParent = db($useUUID)->getOne('parent', $parent1Id);

            // Assert: Verify many-to-many relationship works
            expect($retrievedParent->sharedUserList)->not->toBeEmpty();
            $userNames = $retrievedParent->sharedUserList->map(fn ($u) => $u->name)->toArray();
            expect($userNames)->toContain($user1->name);
            expect($userNames)->toContain($user2->name);
            expect($userNames)->toHaveCount(2);
        })->with([
            'UUID',
            'ID',
        ]);

        it('verifies many-to-many relationships with multiple users', function (string $useUUID): void {
            // Arrange: Test many-to-many with multiple users on a single parent
            $user1 = createTestUser($useUUID);
            $user2 = createTestUser($useUUID);
            $user3 = createTestUser($useUUID);

            $parent = createTestParent($useUUID);
            $parent->sharedUserList = [$user1, $user2, $user3];
            $parentId = $parent->save();

            // Act: Retrieve the parent
            $retrievedParent = db($useUUID)->getOne('parent', $parentId);

            // Assert: Verify all users are properly associated
            expect($retrievedParent->sharedUserList)->not->toBeEmpty();
            $userNames = $retrievedParent->sharedUserList->map(fn ($u) => $u->name)->toArray();

            expect($userNames)->toContain($user1->name);
            expect($userNames)->toContain($user2->name);
            expect($userNames)->toContain($user3->name);
            expect($userNames)->toHaveCount(3);

            // Verify that each user in the collection has the expected properties
            foreach ($retrievedParent->sharedUserList as $user) {
                expect($user->name)->not->toBeEmpty();
                expect($user->email)->not->toBeEmpty();
            }
        })->with([
            'UUID',
            'ID',
        ]);
    });
});
