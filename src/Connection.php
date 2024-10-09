<?php
/*
 * This file is part of the Scrawler package.
 *
 * (c) Pranjal Pandey <its.pranjalpandey@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Scrawler\Arca;

use Doctrine\DBAL\Connection as DBALConnection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Ramsey\Uuid\Uuid;
use Scrawler\Arca\Manager\ModelManager;
use Scrawler\Arca\Manager\RecordManager;
use Scrawler\Arca\Manager\TableManager;

/**
 * Wrapper class for Doctrine DBAL Connection.
 *
 * @mixin DBALConnection
 */
final class Connection
{
    /**
     * Store the instance of current connection.
     */
    private DBALConnection $connection;
    /**
     * Store the instance of SchemaManager.
     */
    private AbstractSchemaManager $schemaManager;
    /**
     * Store the instance of Platform.
     */
    private AbstractPlatform $platform;
    /**
     * Store the instance of RecordManager.
     */
    private RecordManager $recordManager;
    /**
     * Store the instance of TableManager.
     */
    private TableManager $tableManager;
    /**
     * Store the instance of ModelManager.
     */
    private ModelManager $modelManager;
    /**
     * Store if the connection is using UUID.
     */
    private bool $isUsingUUID;
    /**
     * Store the connection id.
     */
    private string $connectionId;

    /**
     * Create a new connection.
     *
     * @param array<mixed> $connectionParams
     */
    public function __construct(array $connectionParams)
    {
        $this->connectionId = Uuid::uuid4()->toString();
        if (isset($connectionParams['useUUID']) && $connectionParams['useUUID']) {
            $this->isUsingUUID = true;
        } else {
            $this->isUsingUUID = false;
        }
        unset($connectionParams['useUUID']);
        $this->connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
        $this->schemaManager = $this->connection->createSchemaManager();
        $this->platform = $this->connection->getDatabasePlatform();
        $this->modelManager = new ModelManager();
        $this->recordManager = new RecordManager($this->connection, $this->modelManager, $this->isUsingUUID);
        $this->tableManager = new TableManager($this->connection, $this->isUsingUUID);
        $this->modelManager->setConnection($this);
    }

    /**
     * Get the instance of SchemaManager.
     */
    public function getSchemaManager(): AbstractSchemaManager
    {
        return $this->schemaManager;
    }

    /**
     * Get the instance of Platform.
     */
    public function getPlatform(): AbstractPlatform
    {
        return $this->platform;
    }

    /**
     * Check if the connection is using UUID.
     */
    public function isUsingUUID(): bool
    {
        return $this->isUsingUUID;
    }

    /**
     * Get the instance of RecordManager.
     */
    public function getRecordManager(): RecordManager
    {
        return $this->recordManager;
    }

    /**
     * Get the instance of TableManager.
     */
    public function getTableManager(): TableManager
    {
        return $this->tableManager;
    }

    /**
     * Get the instance of ModelManager.
     */
    public function getModelManager(): ModelManager
    {
        return $this->modelManager;
    }

    /**
     * Get the connection id.
     */
    public function getConnectionId(): string
    {
        return $this->connectionId;
    }

    /**
     * Magic method to call methods on the connection.
     *
     * @param array<mixed> $args
     */
    public function __call(string $method, array $args): mixed
    {
        return $this->connection->$method(...$args);
    }
}
