<?php

/*
 * This file is part of the Scrawler package.
 *
 * (c) Pranjal Pandey <its.pranjalpandey@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scrawler\Arca\Factory;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Scrawler\Arca\Config;
use Scrawler\Arca\Database;
use Scrawler\Arca\Manager\ModelManager;

class DatabaseFactory
{
    private readonly \DI\Container $container;

    public function __construct(?\DI\Container $container = null)
    {
        $this->container = is_null($container) ? new \DI\Container() : $container;
    }

    /**
     * Create a new Database instance.
     *
     * @param array<mixed> $connectionParams
     */
    public function build(array $connectionParams): Database
    {
        $this->wireContainer($connectionParams);

        return $this->container->make(Database::class);
    }

    /**
     * Create a new Database instance.
     *
     * @param array<mixed> $connectionParams
     */
    public function wireContainer(array $connectionParams): void
    {
        $useUUID = $connectionParams['useUUID'] ?? false;
        $modelNamespace = $connectionParams['modelNamespace'] ?? '';

        $this->createConfig($useUUID, $modelNamespace);
        unset($connectionParams['useUUID'], $connectionParams['modelNamespace']);
        $this->createConnection($connectionParams);
        $this->createModelManager();
    }

    /**
     * Create a new connection.
     *
     * @param array<mixed> $connectionParams
     */
    private function createConnection(array $connectionParams): void
    {
        $this->container->set(Connection::class, fn (): Connection => DriverManager::getConnection($connectionParams));
    }

    private function createModelManager(): void
    {
        $this->container->set(ModelManager::class, fn (): ModelManager => new ModelManager($this->container,$this->container->get(Config::class)));
    }

    private function createConfig(bool $useUUID, string $modelNamespace): void
    {
        $this->container->set(Config::class, fn (): Config => new Config($useUUID, $modelNamespace));
    }
}
