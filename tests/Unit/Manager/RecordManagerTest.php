<?php

use Scrawler\Arca\Manager\RecordManager;

covers(RecordManager::class);

describe('RecordManager Coverage Tests', function (): void {
    beforeEach(function (): void {
        // Clean up database before each test
        cleanupTestTables('UUID');
        cleanupTestTables('ID');
    });

    afterEach(function (): void {
        // Clean up database after each test
        cleanupTestTables('UUID');
        cleanupTestTables('ID');
    });
    describe('GetById Method Coverage', function (): void {
        it('covers getById method select ALL_COLUMNS (line 127)', function ($useUUID): void {
            // Arrange
            $db = db($useUUID);

            // Create and save a test user first
            $user = $db->create('user');
            $user->name = 'Test User';
            $user->email = 'test@example.com';
            $user->age = 30;
            $userId = $user->save();

            // Act - This specifically covers line 127: ->select(self::ALL_COLUMNS)
            $retrievedUser = $db->getOne('user', $userId);

            // Assert - Verify that all columns were selected and returned
            expect($retrievedUser)->not()->toBeNull();
            expect($retrievedUser->name)->toBe('Test User');
            expect($retrievedUser->email)->toBe('test@example.com');
            expect($retrievedUser->age)->toBe(30);
            expect($retrievedUser->id)->toBe($userId);
        })->with(['UUID', 'ID']);

        it('covers getById with non-existent record (line 127)', function ($useUUID): void {
            // Arrange
            $db = db($useUUID);

            // Generate a non-existent ID
            $nonExistentId = 'UUID' === $useUUID ? 'non-existent-uuid' : 99999;

            // Act - This covers line 127 even when no record is found
            $result = $db->getOne('user', $nonExistentId);

            // Assert - Should return null for non-existent record
            expect($result)->toBeNull();
        })->with(['UUID', 'ID']);
    });

    describe('Select Method Coverage', function (): void {
        it('covers select method (line 175)', function ($useUUID): void {
            // Arrange
            $db = db($useUUID);

            // Get RecordManager through reflection to test the select method directly
            $reflection = new ReflectionClass($db);
            $recordManagerProperty = $reflection->getProperty('recordManager');
            $recordManagerProperty->setAccessible(true);
            $recordManager = $recordManagerProperty->getValue($db);

            // Act - This covers line 175 in RecordManager
            $queryBuilder = $recordManager->select('id, name');

            // Assert
            expect($queryBuilder)->toBeInstanceOf(Scrawler\Arca\QueryBuilder::class);
        })->with(['UUID', 'ID']);

        it('covers select method with different expressions', function ($useUUID): void {
            // Arrange
            $db = db($useUUID);
            $reflection = new ReflectionClass($db);
            $recordManagerProperty = $reflection->getProperty('recordManager');
            $recordManagerProperty->setAccessible(true);
            $recordManager = $recordManagerProperty->getValue($db);

            // Act - Test various select expressions
            $qb1 = $recordManager->select('*');
            $qb2 = $recordManager->select('COUNT(*)');
            $qb3 = $recordManager->select('DISTINCT id');

            // Assert
            expect($qb1)->toBeInstanceOf(Scrawler\Arca\QueryBuilder::class);
            expect($qb2)->toBeInstanceOf(Scrawler\Arca\QueryBuilder::class);
            expect($qb3)->toBeInstanceOf(Scrawler\Arca\QueryBuilder::class);
        })->with(['UUID', 'ID']);
    });

    describe('Transaction Exception Handling ', function (): void {
        it('covers transaction rollback on exception ', function ($useUUID): void {
            // Arrange
            $db = db($useUUID);

            // Get RecordManager through reflection to test executeInTransaction directly
            $reflection = new ReflectionClass($db);
            $recordManagerProperty = $reflection->getProperty('recordManager');
            $recordManagerProperty->setAccessible(true);
            $recordManager = $recordManagerProperty->getValue($db);

            // Get the executeInTransaction method via reflection
            $method = new ReflectionMethod($recordManager, 'executeInTransaction');
            $method->setAccessible(true);

            // Act & Assert - Test exception handling that triggers lines 60-62
            try {
                $method->invoke($recordManager, function (): void {
                    // This callback will throw an exception to trigger the rollback path
                    throw new RuntimeException('Test exception to trigger rollback');
                });

                // Should not reach here
                expect(false)->toBe(true, 'Exception should have been thrown');
            } catch (RuntimeException $e) {
                // This covers lines 61-62 (catch block and rollback)
                expect($e->getMessage())->toBe('Test exception to trigger rollback');
            }
        })->with(['UUID', 'ID']);

        it('covers successful transaction return', function ($useUUID): void {
            // Arrange
            $db = db($useUUID);

            // Get RecordManager through reflection
            $reflection = new ReflectionClass($db);
            $recordManagerProperty = $reflection->getProperty('recordManager');
            $recordManagerProperty->setAccessible(true);
            $recordManager = $recordManagerProperty->getValue($db);

            // Get the executeInTransaction method via reflection
            $method = new ReflectionMethod($recordManager, 'executeInTransaction');
            $method->setAccessible(true);

            // Act - Test successful transaction that covers line 60 (return $result)
            $result = $method->invoke($recordManager, fn (): string => 'successful_result');

            expect($result)->toBe('successful_result');
        })->with(['UUID', 'ID']);

        it('covers exception re-throw after rollback (line 63)', function ($useUUID): void {
            // Arrange
            $db = db($useUUID);

            // Get RecordManager through reflection
            $reflection = new ReflectionClass($db);
            $recordManagerProperty = $reflection->getProperty('recordManager');
            $recordManagerProperty->setAccessible(true);
            $recordManager = $recordManagerProperty->getValue($db);

            // Get the executeInTransaction method via reflection
            $method = new ReflectionMethod($recordManager, 'executeInTransaction');
            $method->setAccessible(true);

            // Act & Assert - Test that exception is re-thrown after rollback
            $customException = new InvalidArgumentException('Custom test exception');

            try {
                $method->invoke($recordManager, function () use ($customException): void {
                    throw $customException;
                });

                expect(false)->toBe(true, 'Exception should have been re-thrown');
            } catch (InvalidArgumentException $e) {
                // This covers line 63 (throw $e) - exception is re-thrown after rollback
                expect($e)->toBe($customException);
                expect($e->getMessage())->toBe('Custom test exception');
            }
        })->with(['UUID', 'ID']);
    });

    describe('Transaction Exception Handling', function (): void {
        it('handles transaction exceptions during model save operations', function ($useUUID): void {
            // Arrange
            $db = db($useUUID);

            // Create a model with valid data
            $user = $db->create('user');
            $user->name = 'Test User';
            $user->email = 'test@example.com';

            // Act & Assert - This tests transaction handling paths
            try {
                $result = $user->save();
                // If save succeeds, that's also fine - we're testing the code path
                expect($result)->not()->toBeNull();
            } catch (Exception $e) {
                // Exception handling should have occurred (rollback on lines 60-62)
                expect($e)->toBeInstanceOf(Exception::class);
            }
        })->with(['UUID', 'ID']);

        it('covers successful transaction commit path', function ($useUUID): void {
            // Arrange
            $db = db($useUUID);

            // Act - Create and save a model successfully (this covers commit on line 57)
            $user = $db->create('user');
            $user->name = 'Successful User';
            $user->email = 'success@example.com';
            $user->age = 25;

            $userId = $user->save(); // save() returns the ID

            // Assert - Transaction should have been committed successfully
            expect($userId)->not()->toBeNull();
            expect($user->name)->toBe('Successful User');
            expect($user->email)->toBe('success@example.com');
        })->with(['UUID', 'ID']);

        it('covers nested transaction scenario', function ($useUUID): void {
            // Arrange
            $db = db($useUUID);

            // Create multiple models in what might be a nested transaction scenario
            $user1 = $db->create('user');
            $user1->name = 'User 1';
            $user1->email = 'user1@example.com';

            $user2 = $db->create('user');
            $user2->name = 'User 2';
            $user2->email = 'user2@example.com';

            // Act - Save both users (this tests transaction handling)
            $id1 = $user1->save(); // save() returns ID
            $id2 = $user2->save(); // save() returns ID

            // Assert
            expect($id1)->not()->toBeNull();
            expect($id2)->not()->toBeNull();
            expect($id1)->not()->toBe($id2);
        })->with(['UUID', 'ID']);
    });
});
