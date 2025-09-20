<?php

covers(Scrawler\Arca\Manager\TableManager::class);
covers(Scrawler\Arca\Manager\TableConstraint::class);

// Helper Functions for TableManager Tests

// Note: All helper functions moved to TestHelpers.php to avoid redeclaration errors

beforeAll(function (): void {
    db()->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
});

afterAll(function (): void {
    db()->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
});

afterEach(function (): void {
    cleanupTableManagerTestTables();
});

describe('Table Schema Management', function (): void {
    it('correctly creates and validates table schema with all field types', function (string $useUUID): void {
        // Arrange - Create user with all field types to establish schema
        createRandomUser($useUUID);
        $userRecord = createTestUserWithAllFields($useUUID);

        // Act - Get actual table schema from database
        $expectedSchema = createExpectedUserTableSchema($useUUID);
        $schemaDifferences = compareTableSchemas('user', $expectedSchema, $useUUID);

        // Assert - Schema should match exactly with no differences
        expect($schemaDifferences)
            ->toBeEmpty('Table schema should match expected structure with no differences');

        // Verify the record was saved successfully
        expect($userRecord['id'])
            ->not()->toBeNull('User record should be saved and have an ID');
    })->with(['useUUID']);
});
