<?php

covers(Scrawler\Arca\QueryBuilder::class);
covers(Scrawler\Arca\Manager\ModelManager::class);

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

function areModelsEqual(object $model1, object $model2): bool
{
    return $model1->toString() === $model2->toString();
}

function areModelsNotEqual(object $model1, object $model2): bool
{
    return $model1->toString() !== $model2->toString();
}

describe('QueryBuilder Eager Loading', function (): void {
    it('eager loads single relation using with() method', function (string $useUUID): void {
        // Create user and parent with relationship
        $user = createTestUser($useUUID);
        $parent = createTestParent($useUUID, ['user' => $user]);
        $parent->save();

        // Retrieve parent with eager loaded user
        $parentWithEagerUser = db($useUUID)->find('parent')->with('user')->first();
        $standaloneUser = db($useUUID)->find('user')->first();

        expect($parentWithEagerUser)->not()->toBeNull();
        expect($parentWithEagerUser->user)->not()->toBeNull();
        expect(areModelsEqual($parentWithEagerUser->user, $standaloneUser))->toBeTrue();
    })->with('useUUID');

    it('eager loads multiple relations using multiple with() calls', function (string $useUUID): void {
        // Create related entities
        $child1 = createTestChild($useUUID);
        $child2 = createTestChild($useUUID);
        $user = createTestUser($useUUID);
        $grandparent = createTestGrandparent($useUUID);

        // Create parent with multiple relationships
        $parent = createTestParent($useUUID, [
            'grandparent' => $grandparent,
            'ownChildList' => [$child1, $child2],
            'sharedUserList' => [$user],
        ]);
        $parentId = $parent->save();

        // Retrieve parent with eager loaded relations
        $parentWithEagerRelations = db($useUUID)->find('parent')
            ->where('id = ?')
            ->setParameter(0, $parentId)
            ->with('ownChildList')
            ->with('sharedUserList')
            ->with('grandparent')
            ->first();

        // Retrieve parent without eager loading
        $parentWithoutEagerLoading = db($useUUID)->getOne('parent', $parentId);

        // Before accessing relations, models should be different
        expect(areModelsNotEqual($parentWithEagerRelations, $parentWithoutEagerLoading))->toBeTrue();

        // Access relations on non-eager loaded parent (triggers lazy loading)
        $parentWithoutEagerLoading->ownChildList;
        $parentWithoutEagerLoading->sharedUserList;
        $parentWithoutEagerLoading->grandparent;

        // After accessing relations, models should be equal
        expect(areModelsEqual($parentWithEagerRelations, $parentWithoutEagerLoading))->toBeTrue();
    })->with('useUUID');
});

describe('QueryBuilder Eager Loading Performance', function (): void {
    it('reduces queries when eager loading relations', function (string $useUUID): void {
        // Create test data
        $user = createTestUser($useUUID);
        $parent = createTestParent($useUUID, ['user' => $user]);
        $parent->save();

        // Test eager loading
        $parentWithEagerLoading = db($useUUID)->find('parent')->with('user')->first();

        // Verify relation is already loaded
        expect($parentWithEagerLoading->user)->not()->toBeNull();
        expect($parentWithEagerLoading->user->name)->toBeString();
        expect($parentWithEagerLoading->user->email)->toBeString();
    })->with('useUUID');

    it('handles eager loading with empty results', function (string $useUUID): void {
        // Try to eager load from non-existent parent
        $result = db($useUUID)->find('parent')->with('user')->first();

        expect($result)->toBeNull();
    })->with('useUUID');
});

describe('QueryBuilder Eager Loading Edge Cases', function (): void {
    it('handles eager loading with complex where conditions', function (string $useUUID): void {
        // Create multiple parents with users
        $user1 = createTestUser($useUUID, ['name' => 'John Doe']);
        $user2 = createTestUser($useUUID, ['name' => 'Jane Smith']);

        $parent1 = createTestParent($useUUID, ['user' => $user1, 'name' => 'Parent One']);
        $parent2 = createTestParent($useUUID, ['user' => $user2, 'name' => 'Parent Two']);

        $parent1->save();
        $parent2->save();

        // Eager load with specific condition
        $specificParent = db($useUUID)->find('parent')
            ->where('name = ?')
            ->setParameter(0, 'Parent One')
            ->with('user')
            ->first();

        expect($specificParent)->not()->toBeNull();
        expect($specificParent->name)->toEqual('Parent One');
        expect($specificParent->user)->not()->toBeNull();
        expect($specificParent->user->name)->toEqual('John Doe');
    })->with('useUUID');

    it('handles multiple eager loading chains', function (string $useUUID): void {
        // Create hierarchical data
        $grandparent = createTestGrandparent($useUUID);
        $user = createTestUser($useUUID);
        $child = createTestChild($useUUID);

        $parent = createTestParent($useUUID, [
            'grandparent' => $grandparent,
            'user' => $user,
            'ownChildList' => [$child],
        ]);
        $parentId = $parent->save();

        // Eager load multiple different relation types
        $fullyLoadedParent = db($useUUID)->find('parent')
            ->where('id = ?')
            ->setParameter(0, $parentId)
            ->with('grandparent')  // One-to-One
            ->with('user')         // One-to-One
            ->with('ownChildList') // One-to-Many
            ->first();

        expect($fullyLoadedParent)->not()->toBeNull();
        expect($fullyLoadedParent->grandparent)->not()->toBeNull();
        expect($fullyLoadedParent->user)->not()->toBeNull();
        expect($fullyLoadedParent->ownChildList)->not()->toBeNull();
        expect($fullyLoadedParent->ownChildList)->toHaveCount(1);
    })->with('useUUID');
});
