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
    private \DI\Container $container;

    public function __construct(?\DI\Container $container = null)
    {
        if (is_null($container)) {
            $this->container = new \DI\Container();
        } else {
            $this->container = $container;
        }
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
        if (isset($connectionParams['useUUID'])) {
            $useUUID = $connectionParams['useUUID'];
        } else {
            $useUUID = false;
        }

        $this->createConfig($useUUID,);
        unset($connectionParams['useUUID']);
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

    private function createConfig(bool $useUUID): void
    {
        $this->container->set(Config::class, function () use ($useUUID): Config {
            return new Config($useUUID);
        });
    }
}
