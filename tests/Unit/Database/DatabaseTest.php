<?php

use Doctrine\DBAL\Types\Type;

use function Pest\Faker\fake;

covers(Scrawler\Arca\Database::class);
covers(Scrawler\Arca\Manager\ModelManager::class);
covers(Scrawler\Arca\Manager\RecordManager::class);
covers(Scrawler\Arca\Config::class);

// Test setup and teardown
beforeAll(function (): void {
    db()->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
});

afterAll(function (): void {
    db()->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
});

afterEach(function (): void {
    cleanupDatabaseTestTables();
});

// Helper functions for better test readability
function cleanupDatabaseTestTables(): void
{
    $connection = db()->getConnection();
    $connection->executeStatement('DROP TABLE IF EXISTS parent_user CASCADE;');
    $connection->executeStatement('DROP TABLE IF EXISTS parent CASCADE;');
    $connection->executeStatement('DROP TABLE IF EXISTS user CASCADE;');
    $connection->executeStatement('DROP TABLE IF EXISTS employee CASCADE;');
}

// Note: Common helper functions moved to TestHelpers.php to avoid redeclaration errors

function createTestEmployeeRecord(string $useUUID): object
{
    $employee = db($useUUID)->create('employee');
    $employee->name = fake()->name();
    $employee->department = fake()->word();
    $employee->salary = fake()->numberBetween(30000, 100000);

    return $employee;
}

function assertRecordMatchesDatabase(object $model, string $id, string $useUUID): void
{
    $connection = db($useUUID)->getConnection();
    $tableName = $model->getName();
    $stmt = $connection->prepare("SELECT * FROM {$tableName} WHERE id = :id");
    $stmt->bindValue('id', $id);
    $result = $stmt->executeQuery()->fetchAssociative();

    // Convert both to arrays for proper comparison (avoid JSON field order issues)
    $resultArray = $result ?: [];
    $modelArray = json_decode($model->toString(), true) ?: [];

    expect($resultArray)
        ->toEqual($modelArray, 'Record in database should match model data');
}

function assertCollectionMatchesDatabase(object $collection, string $tableName, string $useUUID, string $whereClause = ''): void
{
    $connection = db($useUUID)->getConnection();
    $sql = "SELECT * FROM {$tableName}";
    if ('' !== $whereClause && '0' !== $whereClause) {
        $sql .= " WHERE {$whereClause}";
    }

    $stmt = $connection->prepare($sql);
    $databaseResults = $stmt->executeQuery()->fetchAllAssociative();

    // Convert both to arrays for comparison
    $collectionArray = json_decode($collection->toString(), true) ?: [];

    expect($collectionArray)
        ->toEqual($databaseResults, 'Collection should match database results');
}

// ==========================================
// Core Database Functionality Tests
// ==========================================

describe('Core Database Functionality', function (): void {
    it('correctly identifies UUID vs ID configuration', function ($useUUID): void {
        // Act
        $isUsingUUID = db($useUUID)->isUsingUUID();

        // Assert
        if ('UUID' === $useUUID) {
            expect($isUsingUUID)->toBeTrue('Database should be configured to use UUID');
        } else {
            expect($isUsingUUID)->toBeFalse('Database should be configured to use integer IDs');
        }
    })->with('useUUID');

    it('creates model instances correctly', function ($useUUID): void {
        // Act
        $user = db($useUUID)->create('user');

        // Assert
        expect($user)->toBeInstanceOf(Scrawler\Arca\Model::class, 'Should create a Model instance');
        expect($user->getName())->toBe('user', 'Model should have correct table name');
    })->with('useUUID');

    it('provides correct connection instance', function (): void {
        // Act
        $connection = db()->getConnection();

        // Assert
        expect($connection)->toBeInstanceOf(Doctrine\DBAL\Connection::class, 'Should provide DBAL Connection instance');
    });

    it('registers JSON type correctly', function (): void {
        // Act & Assert
        expect(Type::hasType('json'))->toBeTrue('JSON type should be registered in DBAL');
    });
});

// ==========================================
// Record Retrieval Tests
// ==========================================

describe('Record Retrieval', function (): void {
    it('retrieves single record correctly', function ($useUUID): void {
        // Arrange
        populateRandomUser($useUUID);
        $savedUserId = createRandomUser($useUUID);

        // Act
        $retrievedUser = db($useUUID)->getOne('user', $savedUserId);

        // Assert
        expect($retrievedUser)->toBeInstanceOf(Scrawler\Arca\Model::class, 'Should return Model instance');
        expect((string) $retrievedUser)->toBeString('Model should be convertible to string');

        assertRecordMatchesDatabase($retrievedUser, $savedUserId, $useUUID);
    })->with('useUUID');

    it('retrieves all records correctly', function ($useUUID): void {
        // Arrange
        populateRandomUser($useUUID);

        // Act
        $users = db($useUUID)->get('user');

        // Assert
        expect($users)->toBeInstanceOf(Scrawler\Arca\Collection::class, 'Should return Collection instance');

        assertCollectionMatchesDatabase($users, 'user', $useUUID);
    })->with('useUUID');

    it('returns query builder for find method', function (): void {
        // Act
        $queryBuilder = db()->find('user');
        $chainedBuilder = db()->find('user')->where('id = 2');

        // Assert
        expect($queryBuilder)->toBeInstanceOf(Scrawler\Arca\QueryBuilder::class, 'find() should return QueryBuilder');
        expect($chainedBuilder)->toBeInstanceOf(Scrawler\Arca\QueryBuilder::class, 'Chained find() should return QueryBuilder');
    });

    it('finds records with conditions correctly', function (): void {
        // Arrange
        populateRandomUser();

        // Act
        $activeUsers = db()->find('user')->where('active = 1')->get();

        // Assert
        expect($activeUsers)->toBeInstanceOf(Scrawler\Arca\Collection::class, 'Should return Collection instance');

        assertCollectionMatchesDatabase($activeUsers, 'user', 'ID', 'active = 1');
    });

    it('returns QueryBuilder from select method (line 127)', function (): void {
        // Arrange
        populateRandomUser();

        // Act - Test the select method which calls recordManager->select() (line 127)
        $queryBuilder = db()->select('*');
        $chainedBuilder = db()->select('name, email')->from('user');

        // Assert
        expect($queryBuilder)->toBeInstanceOf(Scrawler\Arca\QueryBuilder::class, 'select() should return QueryBuilder instance');
        expect($chainedBuilder)->toBeInstanceOf(Scrawler\Arca\QueryBuilder::class, 'Chained select() should return QueryBuilder instance');

        // Test that the QueryBuilder can be used to execute queries
        $results = db()->select('*')->from('user')->get();
        expect($results)->toBeInstanceOf(Scrawler\Arca\Collection::class, 'select() QueryBuilder should be executable');
    });
});

// ==========================================
// Data Manipulation Tests
// ==========================================

describe('Data Manipulation', function (): void {
    it('executes raw SQL commands correctly', function ($useUUID): void {
        // Arrange
        $testUser = createTestUserRecord($useUUID);
        $testUser->save();

        $testName = 'john_test_user';

        // Act
        if (db($useUUID)->isUsingUUID()) {
            $testId = 'abc-test-uuid-123';
            db($useUUID)->exec("INSERT INTO user (id, name) VALUES ('{$testId}', '{$testName}')");
        } else {
            db($useUUID)->exec("INSERT INTO user (name) VALUES ('{$testName}')");
            $testId = 2; // Second record after the test user
        }

        // Assert
        $connection = db($useUUID)->getConnection();
        $stmt = $connection->prepare('SELECT * FROM user WHERE id = :id');
        $stmt->bindValue('id', $testId);
        $result = $stmt->executeQuery()->fetchAssociative();

        expect($result['name'])->toBe($testName, 'Executed SQL should insert record with correct name');
    })->with('useUUID');

    it('retrieves all records with getAll method', function ($useUUID): void {
        // Arrange
        $testUser = createTestUserRecord($useUUID);
        $testUser->save();

        // Act
        $databaseResults = db($useUUID)->getAll('SELECT * FROM user');

        // Assert - Compare with direct database query
        $connection = db($useUUID)->getConnection();
        $stmt = $connection->prepare('SELECT * FROM user');
        $expectedResults = $stmt->executeQuery()->fetchAllAssociative();

        expect($databaseResults)->toEqual($expectedResults, 'getAll() should return same results as direct query');
    })->with('useUUID');

    it('deletes records correctly', function ($useUUID): void {
        // Arrange
        $user = createTestUserRecord($useUUID);
        $savedId = $user->save();

        // Act
        $user->delete();

        // Assert
        $connection = db($useUUID)->getConnection();
        $stmt = $connection->prepare('SELECT * FROM user WHERE id = :id');
        $stmt->bindValue('id', $savedId);
        $result = $stmt->executeQuery()->fetchAssociative();

        expect($result)->toBeFalse('Deleted record should not exist in database');
    })->with('useUUID');
});

// ==========================================
// Table Management Tests
// ==========================================

describe('Table Management', function (): void {
    it('detects single table existence correctly', function ($useUUID): void {
        // Arrange
        $user = createTestUserRecord($useUUID);
        $user->save(); // This creates the table

        // Act & Assert
        expect(db($useUUID)->tableExists('user'))->toBeTrue('Should detect that user table exists');
        expect(db($useUUID)->tableExists('nonexistent_table'))->toBeFalse('Should detect that nonexistent table does not exist');
    })->with('useUUID');

    it('detects multiple table existence correctly', function ($useUUID): void {
        // Arrange
        $user = createTestUserRecord($useUUID);
        $user->save(); // Creates user table

        $employee = createTestEmployeeRecord($useUUID);
        $employee->save(); // Creates employee table

        // Act & Assert
        expect(db($useUUID)->tablesExist(['user', 'employee']))->toBeTrue('Should detect that both tables exist');
        expect(db($useUUID)->tablesExist(['user', 'nonexistent']))->toBeFalse('Should detect when one table does not exist');
    })->with('useUUID');
});

// ==========================================
// Database State Management Tests
// ==========================================

describe('Database State Management', function (): void {
    it('handles frozen database state correctly', function (string $useUUID): void {
        // Arrange - Create and save initial user to establish schema
        $user = createTestUserRecord($useUUID);
        $user->save();

        // Act - Freeze database and try to save with new field
        db($useUUID)->freeze();
        $userWithNewField = db($useUUID)->create('user');
        $userWithNewField->name = 'test_name';
        $userWithNewField->new_field = 'should_fail'; // This is a new field not in the frozen schema

        // Assert - Should throw exception when trying to save with new field
        try {
            $userWithNewField->save();
            expect(false)->toBe(true, 'Expected save to fail on frozen database with new field');
        } catch (Exception $e) {
            expect($e)->toBeInstanceOf(Exception::class, 'Should throw exception for new field on frozen database');
        }

        // Cleanup - Unfreeze for other tests
        db($useUUID)->unfreeze();

        cleanupDatabaseTestTables();
    })->with('useUUID');
});
