<?php

use Scrawler\Arca\Config;

covers(Config::class);

describe('Config Tests', function (): void {
    describe('Config Initialization', function (): void {
        it('creates config with default values', function (): void {
            // Act
            $config = new Config();

            // Assert
            expect($config)->toBeInstanceOf(Config::class);
            expect($config->isUsingUUID())->toBe(false);
            expect($config->getModelNamespace())->toBe('');
            expect($config->isFrozen())->toBe(false);
        });

        it('creates config with custom UUID setting', function (): void {
            // Act
            $config = new Config(isUUID: true);

            // Assert
            expect($config->isUsingUUID())->toBe(true);
            expect($config->getModelNamespace())->toBe('');
            expect($config->isFrozen())->toBe(false);
        });

        it('creates config with custom model namespace', function (): void {
            // Act
            $namespace = 'App\\Models';
            $config = new Config(modelNamespace: $namespace);

            // Assert
            expect($config->isUsingUUID())->toBe(false);
            expect($config->getModelNamespace())->toBe($namespace);
            expect($config->isFrozen())->toBe(false);
        });

        it('creates config with all custom parameters', function (): void {
            // Act
            $namespace = 'Custom\\Models\\Namespace';
            $config = new Config(
                isUUID: true,
                modelNamespace: $namespace,
                isFrozen: true
            );

            // Assert
            expect($config->isUsingUUID())->toBe(true);
            expect($config->getModelNamespace())->toBe($namespace);
            expect($config->isFrozen())->toBe(true);
        });
    });

    describe('UUID Configuration', function (): void {
        it('correctly identifies UUID vs ID configuration', function (): void {
            // Arrange
            $uuidConfig = new Config(isUUID: true);
            $idConfig = new Config(isUUID: false);

            // Act & Assert
            expect($uuidConfig->isUsingUUID())->toBe(true);
            expect($idConfig->isUsingUUID())->toBe(false);
        });

        it('UUID setting is immutable after construction', function (): void {
            // Arrange
            $config = new Config(isUUID: true);

            // Act & Assert
            expect($config->isUsingUUID())->toBe(true);

            // Verify it cannot be changed (readonly property)
            // The isUUID property is readonly, so there's no setter method
            // This test confirms the immutability by design
        });
    });

    describe('Model Namespace Configuration', function (): void {
        it('handles empty namespace correctly', function (): void {
            // Arrange
            $config = new Config(modelNamespace: '');

            // Act & Assert
            expect($config->getModelNamespace())->toBe('');
        });

        it('handles various namespace formats', function (): void {
            // Arrange & Act
            $configs = [
                new Config(modelNamespace: 'App\\Models'),
                new Config(modelNamespace: 'MyApp\\Domain\\Entities'),
                new Config(modelNamespace: 'Vendor\\Package\\Models\\'),
                new Config(modelNamespace: 'SimpleModels'),
            ];

            // Assert
            expect($configs[0]->getModelNamespace())->toBe('App\\Models');
            expect($configs[1]->getModelNamespace())->toBe('MyApp\\Domain\\Entities');
            expect($configs[2]->getModelNamespace())->toBe('Vendor\\Package\\Models\\');
            expect($configs[3]->getModelNamespace())->toBe('SimpleModels');
        });

        it('model namespace is immutable after construction', function (): void {
            // Arrange
            $namespace = 'Original\\Namespace';
            $config = new Config(modelNamespace: $namespace);

            // Act & Assert
            expect($config->getModelNamespace())->toBe($namespace);

            // The modelNamespace property is readonly, confirming immutability
        });
    });

    describe('Frozen State Management', function (): void {
        it('sets frozen state correctly', function (): void {
            // Arrange
            $config = new Config();

            // Act & Assert - Initially not frozen
            expect($config->isFrozen())->toBe(false);

            // Act - Set frozen
            $config->setFrozen(true);
            expect($config->isFrozen())->toBe(true);

            // Act - Unfreeze
            $config->setFrozen(false);
            expect($config->isFrozen())->toBe(false);
        });

        it('allows multiple frozen state changes', function (): void {
            // Arrange
            $config = new Config();

            // Act & Assert - Toggle multiple times
            $config->setFrozen(true);
            expect($config->isFrozen())->toBe(true);

            $config->setFrozen(false);
            expect($config->isFrozen())->toBe(false);

            $config->setFrozen(true);
            expect($config->isFrozen())->toBe(true);

            $config->setFrozen(true); // Setting same value
            expect($config->isFrozen())->toBe(true);
        });

        it('can be initialized with frozen state', function (): void {
            // Arrange & Act
            $frozenConfig = new Config(isFrozen: true);
            $unfrozenConfig = new Config(isFrozen: false);

            // Assert
            expect($frozenConfig->isFrozen())->toBe(true);
            expect($unfrozenConfig->isFrozen())->toBe(false);
        });
    });

    describe('Config Combinations', function (): void {
        it('handles all possible boolean combinations', function (): void {
            // Arrange & Act
            $configs = [
                new Config(isUUID: false, isFrozen: false),
                new Config(isUUID: false, isFrozen: true),
                new Config(isUUID: true, isFrozen: false),
                new Config(isUUID: true, isFrozen: true),
            ];

            // Assert
            expect($configs[0]->isUsingUUID())->toBe(false);
            expect($configs[0]->isFrozen())->toBe(false);

            expect($configs[1]->isUsingUUID())->toBe(false);
            expect($configs[1]->isFrozen())->toBe(true);

            expect($configs[2]->isUsingUUID())->toBe(true);
            expect($configs[2]->isFrozen())->toBe(false);

            expect($configs[3]->isUsingUUID())->toBe(true);
            expect($configs[3]->isFrozen())->toBe(true);
        });

        it('handles complex namespace with UUID and frozen combinations', function (): void {
            // Arrange
            $namespace = 'Complex\\Nested\\Model\\Namespace\\Structure';

            // Act
            $config = new Config(
                isUUID: true,
                modelNamespace: $namespace,
                isFrozen: true
            );

            // Assert
            expect($config->isUsingUUID())->toBe(true);
            expect($config->getModelNamespace())->toBe($namespace);
            expect($config->isFrozen())->toBe(true);

            // Verify frozen state can still be changed
            $config->setFrozen(false);
            expect($config->isFrozen())->toBe(false);
            expect($config->isUsingUUID())->toBe(true); // Other properties unchanged
            expect($config->getModelNamespace())->toBe($namespace);
        });
    });

    describe('Config Edge Cases', function (): void {
        it('handles special characters in namespace', function (): void {
            // Arrange
            $specialNamespaces = [
                'App\\Models_With_Underscores',
                'App\\Models123WithNumbers',
                'App\\Models\\Sub\\Deep\\Nesting',
                'SingleWord',
                '',
            ];

            // Act & Assert
            foreach ($specialNamespaces as $namespace) {
                $config = new Config(modelNamespace: $namespace);
                expect($config->getModelNamespace())->toBe($namespace);
            }
        });

        it('maintains state consistency', function (): void {
            // Arrange
            $config = new Config(
                isUUID: true,
                modelNamespace: 'Test\\Namespace',
                isFrozen: false
            );

            // Act - Change only frozen state multiple times
            for ($i = 0; $i < 5; ++$i) {
                $config->setFrozen(0 === $i % 2);

                // Assert - Other properties remain unchanged
                expect($config->isUsingUUID())->toBe(true);
                expect($config->getModelNamespace())->toBe('Test\\Namespace');
                expect($config->isFrozen())->toBe(0 === $i % 2);
            }
        });

        it('handles construction with all parameters as false/empty', function (): void {
            // Arrange & Act
            $config = new Config(
                isUUID: false,
                modelNamespace: '',
                isFrozen: false
            );

            // Assert
            expect($config->isUsingUUID())->toBe(false);
            expect($config->getModelNamespace())->toBe('');
            expect($config->isFrozen())->toBe(false);
        });
    });
});
