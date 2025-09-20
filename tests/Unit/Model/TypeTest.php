<?php

covers(Scrawler\Arca\Model::class);
covers(Scrawler\Arca\Database::class);
covers(Scrawler\Arca\Factory\DatabaseFactory::class);

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

// Helper Functions for Type Tests

// Note: Helper functions moved to TestHelpers.php to avoid redeclaration errors

describe('Model Type System', function (): void {
    it('correctly identifies field types when model is loaded', function (string $useUUID): void {
        $id = createAndSaveTestUserReturnId($useUUID);
        $retrievedUser = retrieveUserById($useUUID, $id);

        $types = $retrievedUser->getTypes();

        expect($types)->toBeArray();
        expect($types)->toHaveKey('name');
        expect($types)->toHaveKey('email');
        expect($types['name'])->toEqual('text');
        expect($types['email'])->toEqual('text');
    })->with(['useUUID']);
});

describe('Boolean Type Handling', function (): void {
    it('correctly handles boolean true and false values', function (string $useUUID): void {
        // Test boolean true
        $trueUserId = createAndSaveTestUserReturnId($useUUID, ['active' => true]);
        $retrievedTrueUser = retrieveUserById($useUUID, $trueUserId);

        expect($retrievedTrueUser->active)->toBeTrue();

        // Test boolean false
        $falseUserId = createAndSaveTestUserReturnId($useUUID, ['active' => false]);
        $retrievedFalseUser = retrieveUserById($useUUID, $falseUserId);

        expect($retrievedFalseUser->active)->toBeFalse();
    })->with(['useUUID']);

    it('maintains boolean type consistency across save and retrieve operations', function (string $useUUID): void {
        $testCases = [
            ['value' => true, 'expected' => true],
            ['value' => false, 'expected' => false],
            ['value' => 1, 'expected' => true],
            ['value' => 0, 'expected' => false],
        ];

        foreach ($testCases as $testCase) {
            $userId = createAndSaveTestUserReturnId($useUUID, ['active' => $testCase['value']]);
            $retrievedUser = retrieveUserById($useUUID, $userId);

            if ($testCase['expected']) {
                expect($retrievedUser->active)->toBeTrue();
            } else {
                expect($retrievedUser->active)->toBeFalse();
            }
        }
    })->with(['useUUID']);
});

describe('Array Type Storage', function (): void {
    it('correctly stores and retrieves array data', function (string $useUUID): void {
        $hobbies = ['swimming', 'cycling', 'running'];
        $userId = createAndSaveTestUserReturnId($useUUID, ['hobbies' => $hobbies]);

        $retrievedUser = retrieveUserById($useUUID, $userId);

        expect($retrievedUser->hobbies)->toBeArray();
        expect($retrievedUser->hobbies)->toEqual($hobbies);
        expect($retrievedUser->hobbies)->toHaveCount(3);
    })->with(['useUUID']);

    it('handles complex array structures', function (string $useUUID): void {
        $complexData = [
            'preferences' => [
                'theme' => 'dark',
                'language' => 'en',
                'notifications' => ['email', 'sms'],
            ],
            'metadata' => [
                'created_by' => 'system',
                'tags' => ['important', 'user'],
            ],
        ];

        $userId = createAndSaveTestUserReturnId($useUUID, $complexData);
        $retrievedUser = retrieveUserById($useUUID, $userId);

        expect($retrievedUser->preferences)->toBeArray();
        expect($retrievedUser->metadata)->toBeArray();
        expect($retrievedUser->preferences)->toEqual($complexData['preferences']);
        expect($retrievedUser->metadata)->toEqual($complexData['metadata']);
        expect($retrievedUser->preferences['notifications'])->toContain('email');
        expect($retrievedUser->metadata['tags'])->toContain('important');
    })->with(['useUUID']);

    it('handles empty arrays correctly', function (string $useUUID): void {
        $userId = createAndSaveTestUserReturnId($useUUID, ['hobbies' => []]);
        $retrievedUser = retrieveUserById($useUUID, $userId);

        expect($retrievedUser->hobbies)->toBeArray();
        expect($retrievedUser->hobbies)->toBeEmpty();
    })->with(['useUUID']);
});

describe('Numeric Type Handling', function (): void {
    it('correctly handles integer and float values', function (string $useUUID): void {
        $numericData = [
            'age' => 25,
            'score' => 95.5,
            'count' => 0,
            'rating' => 4.8,
        ];

        $userId = createAndSaveTestUserReturnId($useUUID, $numericData);
        $retrievedUser = retrieveUserById($useUUID, $userId);

        expect($retrievedUser->age)->toBeInt();
        expect($retrievedUser->age)->toEqual(25);
        expect($retrievedUser->score)->toBeFloat();
        expect($retrievedUser->score)->toEqual(95.5);
        expect($retrievedUser->count)->toEqual(0);
        expect($retrievedUser->rating)->toEqual(4.8);
    })->with(['useUUID']);

    it('maintains numeric precision', function (string $useUUID): void {
        $precisionData = [
            'price' => 999.99,
            'percentage' => 15.125,
            'temperature' => -5.5,
        ];

        $userId = createAndSaveTestUserReturnId($useUUID, $precisionData);
        $retrievedUser = retrieveUserById($useUUID, $userId);

        expect($retrievedUser->price)->toEqual(999.99);
        expect($retrievedUser->percentage)->toEqual(15.125);
        expect($retrievedUser->temperature)->toEqual(-5.5);
    })->with(['useUUID']);
});
