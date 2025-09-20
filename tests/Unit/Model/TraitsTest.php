<?php

use function Pest\Faker\fake;

covers(Scrawler\Arca\Model::class);

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

// Helper Functions for Traits Tests

// Note: Helper functions moved to TestHelpers.php to avoid redeclaration errors

describe('Model Iterator Trait', function (): void {
    it('can be treated as iterable', function (): void {
        $model = createAndSaveUserForTraits();

        $iteratedKeys = [];
        $iteratedValues = [];

        foreach ($model as $key => $value) {
            $iteratedKeys[] = $key;
            $iteratedValues[] = $value;
        }

        expect($iteratedKeys)->not()->toBeEmpty();
        expect($iteratedValues)->not()->toBeEmpty();

        foreach ($iteratedKeys as $key) {
            expect($key)->not()->toBeNull();
        }

        foreach ($iteratedValues as $value) {
            expect($value)->not()->toBeNull();
        }
    });
});

describe('Model ArrayAccess Trait', function (): void {
    it('can be treated as array with all operations', function (): void {
        $userData = createTestUserDataArray();
        $model = createAndSaveUserForTraits($userData);

        // Test toArray conversion
        expect($model->toArray())->toBeArray();

        // Test array access for reading
        expect($model['name'])->toEqual($model->name);
        expect($model['email'])->toEqual($model->email);

        // Test isset
        expect(isset($model['email']))->toBeTrue();
        expect(isset($model['nonexistent']))->toBeFalse();

        // Test unset
        unset($model['age']);
        expect($model->isset('age'))->toBeFalse();

        // Test array access for writing
        $model['age'] = 25;
        expect($model['age'])->toEqual(25);
        expect($model->age)->toEqual(25);

        // Test exception on invalid array access
        expect(fn (): int => $model[] = 10)->toThrow(Exception::class);
    });

    it('maintains data consistency between array and object access', function (): void {
        $userData = createTestUserDataArray();
        $model = createAndSaveUserForTraits($userData);

        // Test that array access and object access return same values
        expect($model['name'])->toEqual($model->name);
        expect($model['email'])->toEqual($model->email);

        // Test that changes via array access reflect in object access
        $newEmail = fake()->email();
        $model['email'] = $newEmail;
        expect($model->email)->toEqual($newEmail);

        // Test that changes via object access reflect in array access
        $newName = fake()->name();
        $model->name = $newName;
        expect($model['name'])->toEqual($newName);
    });
});

describe('Model Stringable Trait', function (): void {
    it('can be converted to string', function (): void {
        $userData = createTestUserDataArray();
        $model = createAndSaveUserForTraits($userData);

        ob_start();
        echo $model;
        $echoOutput = ob_get_clean();

        expect($echoOutput)->toEqual($model->toString());
        expect($echoOutput)->toBeString();
        expect($echoOutput)->not()->toBeEmpty();
    });

    it('toString method produces valid output', function (): void {
        $userData = createTestUserDataArray();
        $model = createAndSaveUserForTraits($userData);

        $stringOutput = $model->toString();

        expect($stringOutput)->toBeString();
        expect($stringOutput)->not()->toBeEmpty();

        // Should contain some form of model data representation
        expect($stringOutput)->toContain($userData['name']);
    });
});
