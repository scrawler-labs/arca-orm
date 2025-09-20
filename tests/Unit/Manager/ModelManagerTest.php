<?php

use DI\Container;
use Scrawler\Arca\Config;
use Scrawler\Arca\Factory\DatabaseFactory;
use Scrawler\Arca\Manager\ModelManager;
use Scrawler\Arca\Model;

covers(ModelManager::class);

describe('ModelManager Tests', function (): void {
    describe('Model Creation via Database Factory', function (): void {
        it('creates models through properly configured container', function (): void {
            // Arrange - use DatabaseFactory to get properly wired container
            $factory = new DatabaseFactory();
            $connectionParams = getConnectionParams('false');
            $connectionParams['modelNamespace'] = '';
            $factory->wireContainer($connectionParams);

            $container = new ReflectionProperty($factory, 'container');
            $container->setAccessible(true);
            $actualContainer = $container->getValue($factory);

            $config = $actualContainer->get(Config::class);
            $modelManager = $actualContainer->get(ModelManager::class);

            // Act
            $model = $modelManager->create('user');

            // Assert
            expect($model)->toBeInstanceOf(Model::class);
        });

        it('handles different model names with empty namespace', function (): void {
            // Arrange
            $factory = new DatabaseFactory();
            $connectionParams = getConnectionParams('false');
            $connectionParams['modelNamespace'] = '';
            $factory->wireContainer($connectionParams);

            $container = new ReflectionProperty($factory, 'container');
            $container->setAccessible(true);
            $actualContainer = $container->getValue($factory);

            $modelManager = $actualContainer->get(ModelManager::class);

            // Act
            $userModel = $modelManager->create('user');
            $profileModel = $modelManager->create('profile');
            $tagModel = $modelManager->create('tag');

            // Assert
            expect($userModel)->toBeInstanceOf(Model::class);
            expect($profileModel)->toBeInstanceOf(Model::class);
            expect($tagModel)->toBeInstanceOf(Model::class);
            expect($userModel)->not()->toBe($profileModel);
        });

        it('creates models with namespace configuration', function (): void {
            // Arrange
            $factory = new DatabaseFactory();
            $connectionParams = getConnectionParams('false');
            $connectionParams['modelNamespace'] = 'App\\Models\\';
            $factory->wireContainer($connectionParams);

            $container = new ReflectionProperty($factory, 'container');
            $container->setAccessible(true);
            $actualContainer = $container->getValue($factory);

            $modelManager = $actualContainer->get(ModelManager::class);

            // Act - this will check class_exists('App\Models\User') and fall back to generic Model
            $model = $modelManager->create('user');

            // Assert
            expect($model)->toBeInstanceOf(Model::class);
        });

        it('handles UUID configuration properly', function (): void {
            // Arrange
            $factory = new DatabaseFactory();
            $connectionParams = getConnectionParams('UUID');
            $connectionParams['modelNamespace'] = 'Test\\';
            $factory->wireContainer($connectionParams);

            $container = new ReflectionProperty($factory, 'container');
            $container->setAccessible(true);
            $actualContainer = $container->getValue($factory);

            $modelManager = $actualContainer->get(ModelManager::class);

            // Act
            $model = $modelManager->create('item');

            // Assert
            expect($model)->toBeInstanceOf(Model::class);
        });

        it('tests namespace-based class exists logic', function (): void {
            // Arrange
            $factory = new DatabaseFactory();
            $connectionParams = getConnectionParams('false');
            $connectionParams['modelNamespace'] = 'NonExistent\\Namespace\\';
            $factory->wireContainer($connectionParams);

            $container = new ReflectionProperty($factory, 'container');
            $container->setAccessible(true);
            $actualContainer = $container->getValue($factory);

            $modelManager = $actualContainer->get(ModelManager::class);

            // Act - This specifically tests the class_exists() branch at lines 42-44
            $model = $modelManager->create('testModel');

            // Assert
            expect($model)->toBeInstanceOf(Model::class);
            // This test exercises the uncovered lines 42-44 in ModelManager
        });
    });

    describe('Container Integration', function (): void {
        it('each model creation returns new instance', function (): void {
            // Arrange
            $factory = new DatabaseFactory();
            $connectionParams = getConnectionParams('false');
            $factory->wireContainer($connectionParams);

            $container = new ReflectionProperty($factory, 'container');
            $container->setAccessible(true);
            $actualContainer = $container->getValue($factory);

            $modelManager = $actualContainer->get(ModelManager::class);

            // Act
            $model1 = $modelManager->create('test1');
            $model2 = $modelManager->create('test2');
            $model3 = $modelManager->create('test1'); // Same name, should be different instance

            // Assert
            expect($model1)->toBeInstanceOf(Model::class);
            expect($model2)->toBeInstanceOf(Model::class);
            expect($model3)->toBeInstanceOf(Model::class);
            expect($model1)->not()->toBe($model2);
            expect($model1)->not()->toBe($model3);
            expect($model2)->not()->toBe($model3);
        });

        it('handles edge case model names with namespace', function (): void {
            // Arrange
            $factory = new DatabaseFactory();
            $connectionParams = getConnectionParams('false');
            $connectionParams['modelNamespace'] = 'Edge\\Case\\';
            $factory->wireContainer($connectionParams);

            $container = new ReflectionProperty($factory, 'container');
            $container->setAccessible(true);
            $actualContainer = $container->getValue($factory);

            $modelManager = $actualContainer->get(ModelManager::class);

            // Act
            $modelCamelCase = $modelManager->create('camelCase');
            $modelUnderscore = $modelManager->create('under_score');
            $modelNumber = $modelManager->create('model123');
            $modelSpecial = $modelManager->create('special-chars');

            // Assert
            expect($modelCamelCase)->toBeInstanceOf(Model::class);
            expect($modelUnderscore)->toBeInstanceOf(Model::class);
            expect($modelNumber)->toBeInstanceOf(Model::class);
            expect($modelSpecial)->toBeInstanceOf(Model::class);
        });
    });

    describe('Code Coverage for Lines 42-44', function (): void {
        it('covers class_exists check with various namespaces', function (): void {
            // Arrange - Test multiple namespace scenarios to ensure line coverage
            $testCases = [
                'App\\Models\\',
                'Domain\\Entities\\',
                'My\\Custom\\Models\\',
                'Very\\Deep\\Nested\\Namespace\\Models\\',
            ];

            foreach ($testCases as $namespace) {
                $factory = new DatabaseFactory();
                $connectionParams = getConnectionParams('false');
                $connectionParams['modelNamespace'] = $namespace;
                $factory->wireContainer($connectionParams);

                $container = new ReflectionProperty($factory, 'container');
                $container->setAccessible(true);
                $actualContainer = $container->getValue($factory);

                $modelManager = $actualContainer->get(ModelManager::class);

                // Act - This will trigger class_exists() check for each namespace
                $model = $modelManager->create('testModel');

                // Assert
                expect($model)->toBeInstanceOf(Model::class);
            }
        });

        it('covers both UUID and ID configurations with namespaces', function (): void {
            // Arrange & Act - Test both UUID configurations with namespaces
            foreach (['false', 'UUID'] as $uuidSetting) {
                $factory = new DatabaseFactory();
                $connectionParams = getConnectionParams($uuidSetting);
                $connectionParams['modelNamespace'] = 'Coverage\\Test\\';
                $factory->wireContainer($connectionParams);

                $container = new ReflectionProperty($factory, 'container');
                $container->setAccessible(true);
                $actualContainer = $container->getValue($factory);

                $modelManager = $actualContainer->get(ModelManager::class);

                // This ensures the namespace+class_exists logic is tested with both UUID settings
                $model = $modelManager->create('coverageTest');

                // Assert
                expect($model)->toBeInstanceOf(Model::class);
            }
        });
    });
});
