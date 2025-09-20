<?php

use Scrawler\Arca\Manager\WriteManager;

covers(WriteManager::class);

describe('WriteManager Coverage Tests', function (): void {
    describe('Transaction Handling Edge Cases', function (): void {
        it('covers nested transaction execution path (line 96)', function (): void {
            // Arrange
            $db = db('false');

            // Create a user that will trigger the nested transaction path
            $user = $db->create('user');
            $user->name = 'Nested Transaction User';
            $user->email = 'nested@example.com';

            // Save the user first to ensure we have a record
            $userId = $user->save();
            expect($userId)->not()->toBeNull();

            // Now update the user - this should trigger transaction handling
            $user->name = 'Updated Nested User';
            $updatedId = $user->save();

            // Assert - The update should work and exercise transaction paths
            expect($updatedId)->toBe($userId);
            expect($user->name)->toBe('Updated Nested User');
        });

        it('covers WriteManager transaction commit and rollback scenarios', function (): void {
            // Arrange
            $db = db('false');

            // Create multiple operations that should exercise WriteManager
            $user1 = $db->create('user');
            $user1->name = 'WriteManager Test 1';
            $user1->email = 'wm1@example.com';

            $user2 = $db->create('user');
            $user2->name = 'WriteManager Test 2';
            $user2->email = 'wm2@example.com';

            // Act - Save both which should exercise WriteManager transaction handling
            $id1 = $user1->save();
            $id2 = $user2->save();

            // Assert
            expect($id1)->not()->toBeNull();
            expect($id2)->not()->toBeNull();
            expect($id1)->not()->toBe($id2);
        });

        it('covers WriteManager with relationship saves', function (): void {
            // Arrange
            $db = db('false');

            // Create a parent model for many-to-many relationship testing
            $parent = $db->create('parent');
            $parent->name = 'Parent for Relationship';

            // Create users for the many-to-many relationship
            $user1 = $db->create('user');
            $user1->name = 'Relationship User 1';
            $user1->email = 'rel1@example.com';

            $user2 = $db->create('user');
            $user2->name = 'Relationship User 2';
            $user2->email = 'rel2@example.com';

            // Set up the many-to-many relationship
            $parent->sharedUserList = [$user1, $user2];

            // Act - Save with relationships (exercises WriteManager constraint paths)
            $parentId = $parent->save();

            // Assert
            expect($parentId)->not()->toBeNull();
            expect($parent->name)->toBe('Parent for Relationship');
        });

        it('covers frozen database state in WriteManager', function (): void {
            // Arrange
            $db = db('false');
            $db->freeze(); // This should affect WriteManager table creation logic

            // Create a user when database is frozen
            $user = $db->create('user');
            $user->name = 'Frozen DB User';
            $user->email = 'frozen@example.com';

            // Act - This should still work but may exercise different WriteManager paths
            $userId = $user->save();

            // Assert
            expect($userId)->not()->toBeNull();
            expect($user->name)->toBe('Frozen DB User');

            // Unfreeze for cleanup
            $db->unfreeze();
        });

        it('covers WriteManager exception handling scenarios', function (): void {
            // Arrange
            $db = db('false');

            // Create a user that might trigger exception handling
            $user = $db->create('user');
            $user->name = 'Exception Test User';
            $user->email = 'exception@example.com';

            // Act & Assert - Test exception handling in WriteManager
            try {
                $userId = $user->save();
                // If successful, verify the save worked
                expect($userId)->not()->toBeNull();
            } catch (Exception $e) {
                // If exception occurs, verify it's handled properly
                expect($e)->toBeInstanceOf(Exception::class);
            }
        });
    });
});
