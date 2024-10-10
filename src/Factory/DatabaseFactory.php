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
    private \Di\Container $container;

    public function __construct(?\DI\ContainerBuilder $container = null)
    {
        if (is_null($container)) {
            $builder = new \DI\ContainerBuilder();
        } else {
            $builder = $container;
        }
        $this->container = $builder->build();
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
        $this->createConfig($connectionParams['useUUID'] ?? false, $connectionParams['frozen'] ?? false);
        unset($connectionParams['use_uuid']);
        unset($connectionParams['frozen']);
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
        $this->container->set(Connection::class, function () use ($connectionParams): Connection {
            return DriverManager::getConnection($connectionParams);
        });
    }

    private function createModelManager(): void
    {
        $this->container->set(ModelManager::class, function (): ModelManager {
            return new ModelManager($this->container);
        });
    }

    private function createConfig(bool $useUUID = false, bool $frozen = false): void
    {
        $this->container->set(Config::class, function () use ($useUUID, $frozen): Config {
            return new Config($useUUID, $frozen);
        });
    }
}
