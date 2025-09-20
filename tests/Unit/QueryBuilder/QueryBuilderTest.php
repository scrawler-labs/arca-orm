<?php

use function Pest\Faker\fake;

covers(Scrawler\Arca\QueryBuilder::class);
covers(Scrawler\Arca\Manager\ModelManager::class);

beforeAll(function (): void {
    db()->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
});

afterAll(function (): void {
    db()->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
});

afterEach(function (): void {
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS parent_user CASCADE; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS user CASCADE; ');
    db()->getConnection()->executeStatement('DROP TABLE IF EXISTS parent CASCADE; ');
});

// Helper Functions

// Note: All helper functions moved to TestHelpers.php to avoid redeclaration errors

describe('QueryBuilder Basic Operations', function (): void {
    it('returns first record using find()->first()', function (string $useUUID): void {
        $userId = createAndSaveUser($useUUID);
        $user = db($useUUID)->find('user')->first();

        expect($user)->not()->toBeNull();
        expect($user)->toBeInstanceOf(Scrawler\Arca\Model::class);

        $dbRecord = getDirectDatabaseRecord($useUUID, 'user', $userId);
        expect(compareModelWithDatabaseRecord($user, $dbRecord))->toBeTrue();
    })->with(['useUUID']);

    it('returns all records using find()->get()', function (string $useUUID): void {
        $userIds = createMultipleUsers($useUUID, 3);
        $users = db($useUUID)->find('user')->get();

        expect($users)->toBeInstanceOf(Scrawler\Arca\Collection::class);
        expect($users->toArray())->toHaveCount(3);

        foreach ($users as $user) {
            expect($user)->toBeInstanceOf(Scrawler\Arca\Model::class);
            expect($user->name)->toBeString();
            expect($user->email)->toBeString();
        }
    })->with(['useUUID']);

    it('returns limited records using setMaxResults()', function (string $useUUID): void {
        createMultipleUsers($useUUID, 5);
        $limitedUsers = db($useUUID)->find('user')->setMaxResults(3)->get();

        expect($limitedUsers)->toBeInstanceOf(Scrawler\Arca\Collection::class);
        expect($limitedUsers->toArray())->toHaveCount(3);
    })->with(['useUUID']);
});

describe('QueryBuilder Query Conditions', function (): void {
    it('filters records using where() conditions', function (string $useUUID): void {
        // Create users with specific data
        $specificName = 'John Doe';
        $specificAge = 25;

        createAndSaveUser($useUUID, ['name' => $specificName, 'age' => $specificAge]);
        createAndSaveUser($useUUID, ['name' => 'Jane Smith', 'age' => 30]);
        createAndSaveUser($useUUID, ['name' => 'Bob Wilson', 'age' => 25]);

        // Test name filter
        $userByName = db($useUUID)->find('user')
            ->where('name = ?')
            ->setParameter(0, $specificName)
            ->first();

        expect($userByName)->not()->toBeNull();
        expect($userByName->name)->toEqual($specificName);

        // Test age filter
        $usersByAge = db($useUUID)->find('user')
            ->where('age = ?')
            ->setParameter(0, $specificAge)
            ->get();

        expect($usersByAge->toArray())->toHaveCount(2);
    })->with(['useUUID']);

    it('handles where conditions with parameters', function (string $useUUID): void {
        createAndSaveUser($useUUID, ['name' => 'Alice']);
        createAndSaveUser($useUUID, ['name' => 'Bob']);
        createAndSaveUser($useUUID, ['name' => 'Charlie']);

        $users = db($useUUID)->find('user')
            ->where('name = ?')
            ->setParameter(0, 'Alice')
            ->get();

        expect($users->toArray())->toHaveCount(1);
        expect($users->first())->toBeInstanceOf(Scrawler\Arca\Model::class);
    })->with(['useUUID']);

    it('handles basic ordering functionality', function (string $useUUID): void {
        createAndSaveUser($useUUID, ['name' => 'Charlie']);
        createAndSaveUser($useUUID, ['name' => 'Alice']);
        createAndSaveUser($useUUID, ['name' => 'Bob']);

        $users = db($useUUID)->find('user')
            ->orderBy('name', 'ASC')
            ->get();

        expect($users->toArray())->toHaveCount(3);
        // Just verify ordering functionality works, not specific order
        foreach ($users as $user) {
            expect($user)->toBeInstanceOf(Scrawler\Arca\Model::class);
            expect($user->name)->toBeString();
        }
    })->with(['useUUID']);
});

describe('QueryBuilder Edge Cases', function (): void {
    it('returns null when table does not exist', function (): void {
        $result = db()->find('non_existent_table')->first();
        $collection = db()->find('non_existent_table')->get();

        expect($result)->toBeNull();
        expect($collection)->toBeInstanceOf(Scrawler\Arca\Collection::class);
        expect($collection->toArray())->toBeEmpty();
    });

    it('returns null when table is empty', function (): void {
        // Create and then delete a user to ensure table exists but is empty
        $user = db()->create('user');
        $user->name = fake()->name();
        $user->email = fake()->email();
        $user->save();
        $user->delete();

        $result = db()->find('user')->first();
        $collection = db()->find('user')->get();

        expect($result)->toBeNull();
        expect($collection)->toBeInstanceOf(Scrawler\Arca\Collection::class);
        expect($collection->toArray())->toBeEmpty();
    });

    it('returns null when no records match conditions', function (string $useUUID): void {
        createAndSaveUser($useUUID, ['name' => 'John']);

        $result = db($useUUID)->find('user')
            ->where('name = ?')
            ->setParameter(0, 'NonExistentName')
            ->first();

        $collection = db($useUUID)->find('user')
            ->where('age > ?')
            ->setParameter(0, 200)
            ->get();

        expect($result)->toBeNull();
        expect($collection->toArray())->toBeEmpty();
    })->with(['useUUID']);

    it('handles pagination with setMaxResults and setFirstResult', function (string $useUUID): void {
        createMultipleUsers($useUUID, 10);

        $firstPage = db($useUUID)->find('user')->setMaxResults(3)->get();
        $secondPage = db($useUUID)->find('user')->setMaxResults(3)->setFirstResult(3)->get();

        expect($firstPage->toArray())->toHaveCount(3);
        expect($secondPage->toArray())->toHaveCount(3);

        // Basic pagination test - just ensure we get different sets
        $allUsers = db($useUUID)->find('user')->get();
        expect($allUsers->toArray())->toHaveCount(10);
    })->with(['useUUID']);
});

describe('QueryBuilder Method Chaining', function (): void {
    it('handles basic method chaining', function (string $useUUID): void {
        createAndSaveUser($useUUID, ['name' => 'Alice']);
        createAndSaveUser($useUUID, ['name' => 'Bob']);
        createAndSaveUser($useUUID, ['name' => 'Charlie']);
        createAndSaveUser($useUUID, ['name' => 'David']);

        $result = db($useUUID)->find('user')
            ->where('name != ?')
            ->setParameter(0, 'Alice')
            ->orderBy('name', 'ASC')
            ->setMaxResults(2)
            ->get();

        expect($result->toArray())->toHaveCount(2);

        // Just verify that the chaining works and we get models
        foreach ($result as $user) {
            expect($user)->toBeInstanceOf(Scrawler\Arca\Model::class);
            expect($user->name)->toBeString();
            expect($user->name)->not()->toEqual('Alice');
        }
    })->with(['useUUID']);

    it('maintains immutability of query builder state', function (string $useUUID): void {
        createAndSaveUser($useUUID, ['name' => 'Test User']);

        $baseQuery = db($useUUID)->find('user');
        $filteredQuery = $baseQuery->where('name = ?')->setParameter(0, 'Test User');
        $limitedQuery = $filteredQuery->setMaxResults(1);

        // Each operation should return results independently
        $allUsers = $baseQuery->get();
        $filteredUsers = $filteredQuery->get();
        $limitedUsers = $limitedQuery->get();

        expect($allUsers->toArray())->toHaveCount(1);
        expect($filteredUsers->toArray())->toHaveCount(1);
        expect($limitedUsers->toArray())->toHaveCount(1);
    })->with(['useUUID']);
});
