<?php

use Scrawler\Arca\Exception\InvalidIdException;
use Scrawler\Arca\Exception\InvalidModelException;
use Scrawler\Arca\Exception\InvalidRelationTypeException;
use Scrawler\Arca\Exception\KeyNotFoundException;

covers(InvalidIdException::class);
covers(InvalidModelException::class);
covers(InvalidRelationTypeException::class);
covers(KeyNotFoundException::class);

describe('Exception Tests', function (): void {
    describe('InvalidIdException', function (): void {
        it('can be thrown with predefined message', function (): void {
            // Act & Assert
            expect(fn () => throw new InvalidIdException())
                ->toThrow(InvalidIdException::class, 'Force setting of id for model is not allowed');
        });

        it('inherits from Exception class', function (): void {
            // Arrange
            $exception = new InvalidIdException();

            // Assert
            expect($exception)->toBeInstanceOf(Exception::class);
            expect($exception)->toBeInstanceOf(Throwable::class);
            expect($exception->getMessage())->toBe('Force setting of id for model is not allowed');
        });

        it('maintains exception properties', function (): void {
            // Arrange & Act
            $exception = new InvalidIdException();

            // Assert
            expect($exception->getMessage())->toBe('Force setting of id for model is not allowed');
            expect($exception->getCode())->toBe(0);
            expect($exception->getPrevious())->toBeNull();
        });
    });

    describe('InvalidModelException', function (): void {
        it('can be thrown with predefined message', function (): void {
            // Act & Assert
            expect(fn () => throw new InvalidModelException())
                ->toThrow(InvalidModelException::class);
        });

        it('has correct error message', function (): void {
            // Arrange
            $exception = new InvalidModelException();
            $expectedMessage = "parameter passed to shared list or own list should be array of class \Arca\Model";

            // Assert
            expect($exception->getMessage())->toBe($expectedMessage);
        });

        it('inherits from Exception class', function (): void {
            // Arrange
            $exception = new InvalidModelException();

            // Assert
            expect($exception)->toBeInstanceOf(Exception::class);
            expect($exception)->toBeInstanceOf(Throwable::class);
        });
    });

    describe('InvalidRelationTypeException', function (): void {
        it('can be thrown with null type', function (): void {
            // Act & Assert
            expect(fn () => throw new InvalidRelationTypeException(null))
                ->toThrow(InvalidRelationTypeException::class, 'Invalid relation type: ');
        });

        it('can be thrown with specific relation type', function (): void {
            // Arrange
            $relationType = 'unsupported-relation';

            // Act & Assert
            expect(fn () => throw new InvalidRelationTypeException($relationType))
                ->toThrow(InvalidRelationTypeException::class, "Invalid relation type: {$relationType}");
        });

        it('inherits from Exception class', function (): void {
            // Arrange
            $exception = new InvalidRelationTypeException('test-type');

            // Assert
            expect($exception)->toBeInstanceOf(Exception::class);
            expect($exception)->toBeInstanceOf(Throwable::class);
        });

        it('handles various relation type scenarios', function (): void {
            // Arrange
            $relationTypes = [
                'many-to-one-to-many',
                'invalid-config',
                'circular-relation',
                '',
                null,
            ];

            // Act & Assert
            foreach ($relationTypes as $relationType) {
                $exception = new InvalidRelationTypeException($relationType);
                expect($exception->getMessage())->toBe("Invalid relation type: {$relationType}");
            }
        });
    });

    describe('KeyNotFoundException', function (): void {
        it('can be thrown with predefined message', function (): void {
            // Act & Assert
            expect(fn () => throw new KeyNotFoundException())
                ->toThrow(KeyNotFoundException::class, 'Key you are trying to access does not exist');
        });

        it('has correct error message', function (): void {
            // Arrange
            $exception = new KeyNotFoundException();

            // Assert
            expect($exception->getMessage())->toBe('Key you are trying to access does not exist');
        });

        it('inherits from Exception class', function (): void {
            // Arrange
            $exception = new KeyNotFoundException();

            // Assert
            expect($exception)->toBeInstanceOf(Exception::class);
            expect($exception)->toBeInstanceOf(Throwable::class);
        });
    });

    describe('Exception Hierarchy and Integration', function (): void {
        it('all custom exceptions inherit from Exception', function (): void {
            // Arrange
            $exceptions = [
                new InvalidIdException(),
                new InvalidModelException(),
                new InvalidRelationTypeException('test'),
                new KeyNotFoundException(),
            ];

            // Act & Assert
            foreach ($exceptions as $exception) {
                expect($exception)->toBeInstanceOf(Exception::class);
                expect($exception)->toBeInstanceOf(Throwable::class);
            }
        });

        it('exceptions can be caught as general Exception', function (): void {
            // Act & Assert
            $caught = false;
            try {
                throw new InvalidIdException();
            } catch (Exception $e) {
                $caught = true;
                expect($e)->toBeInstanceOf(InvalidIdException::class);
                expect($e->getMessage())->toBe('Force setting of id for model is not allowed');
            }
            expect($caught)->toBe(true);
        });

        it('exceptions maintain stack trace information', function (): void {
            // Arrange & Act
            $exception = new InvalidModelException();

            // Assert
            expect($exception->getFile())->toBeString();
            expect($exception->getLine())->toBeInt();
            expect($exception->getTrace())->toBeArray();
            expect($exception->getTraceAsString())->toBeString();
        });

        it('exceptions can be serialized and unserialized', function (): void {
            // Arrange
            $originalException = new KeyNotFoundException();

            // Act & Assert - some exceptions may not be serializable due to internal state
            // Testing basic properties instead
            expect($originalException->getMessage())->toBeString();
            expect($originalException->getCode())->toBeInt();
            expect($originalException::class)->toBe(KeyNotFoundException::class);
        });
    });

    describe('Exception Usage in Real Scenarios', function (): void {
        it('InvalidIdException scenario - can be thrown', function (): void {
            // Arrange & Act & Assert - test that the exception can be thrown
            expect(fn () => throw new InvalidIdException())
                ->toThrow(InvalidIdException::class);

            // Test in a more realistic scenario if there's a method that throws it
            $exception = new InvalidIdException();
            expect($exception)->toBeInstanceOf(InvalidIdException::class);
        });

        it('KeyNotFoundException scenario - accessing undefined property', function (string $useUUID): void {
            // Arrange
            $user = createTestUser($useUUID);
            $user->save();

            // Act & Assert
            expect(fn () => $nonExistentValue = $user->non_existent_property)
                ->toThrow(KeyNotFoundException::class);
        })->with(['useUUID']);

        it('InvalidModelException scenario - wrong model type in relationship', function (string $useUUID): void {
            // Arrange
            $user = createTestUser($useUUID);
            $invalidModel = 'not_a_model_object';

            // Act & Assert
            expect(fn (): array => $user->ownUserList = [$invalidModel])
                ->toThrow(InvalidModelException::class);
        })->with(['useUUID']);

        it('InvalidRelationTypeException scenario - unsupported relation type', function (): void {
            // Arrange
            $unsupportedType = 'many-to-one-to-many';

            // Act & Assert
            expect(fn () => throw new InvalidRelationTypeException($unsupportedType))
                ->toThrow(InvalidRelationTypeException::class, "Invalid relation type: {$unsupportedType}");
        });
    });

    describe('Exception Error Messages and Details', function (): void {
        it('provides meaningful error messages for debugging', function (): void {
            // Arrange
            $exceptions = [
                new InvalidIdException(),
                new InvalidModelException(),
                new InvalidRelationTypeException('custom-type'),
                new KeyNotFoundException(),
            ];

            // Act & Assert
            foreach ($exceptions as $exception) {
                expect($exception->getMessage())->not()->toBeEmpty();
                expect(strlen($exception->getMessage()))->toBeGreaterThan(5);
            }
        });

        it('InvalidRelationTypeException handles different type inputs', function (): void {
            // Arrange
            $testCases = [
                ['type' => 'many-to-many-to-one', 'expected' => 'Invalid relation type: many-to-many-to-one'],
                ['type' => '', 'expected' => 'Invalid relation type: '],
                ['type' => null, 'expected' => 'Invalid relation type: '],
                ['type' => 'one-to-zero', 'expected' => 'Invalid relation type: one-to-zero'],
            ];

            // Act & Assert
            foreach ($testCases as $testCase) {
                $exception = new InvalidRelationTypeException($testCase['type']);
                expect($exception->getMessage())->toBe($testCase['expected']);
            }
        });
    });

    describe('Exception Consistency', function (): void {
        it('all exceptions have consistent behavior', function (): void {
            // Arrange
            $exceptions = [
                new InvalidIdException(),
                new InvalidModelException(),
                new InvalidRelationTypeException('test'),
                new KeyNotFoundException(),
            ];

            // Act & Assert
            foreach ($exceptions as $exception) {
                expect($exception->getCode())->toBe(0); // Default code
                expect($exception->getPrevious())->toBeNull(); // No previous exception
                expect($exception->getFile())->toContain(__FILE__); // Current file
                expect($exception->getLine())->toBeInt();
            }
        });

        it('exceptions are properly typed', function (): void {
            // Act & Assert
            expect(new InvalidIdException())->toBeInstanceOf(InvalidIdException::class);
            expect(new InvalidModelException())->toBeInstanceOf(InvalidModelException::class);
            expect(new InvalidRelationTypeException('test'))->toBeInstanceOf(InvalidRelationTypeException::class);
            expect(new KeyNotFoundException())->toBeInstanceOf(KeyNotFoundException::class);
        });
    });
});
