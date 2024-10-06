<?php

declare(strict_types=1);
namespace Scrawler\Arca;

use \Doctrine\DBAL\Connection as DBALConnection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use \Doctrine\DBAL\Schema\AbstractSchemaManager;
use \Scrawler\Arca\Manager\RecordManager;
use \Scrawler\Arca\Manager\TableManager;
use \Scrawler\Arca\Manager\ModelManager;
use Ramsey\Uuid\Uuid;

/**
 * @mixin DBALConnection
 */
class Connection
{

    private DBALConnection $connection;
    private AbstractSchemaManager $SchemaManager;
    private AbstractPlatform $platform;
    private RecordManager $RecordManager;
    private TableManager $TableManager;
    private ModelManager $ModelManager;
    private bool $isUsingUUID;
    private string $connectionId;

    /**
     * Create a new connection
     * @param array<mixed> $connectionParams
     */
    public function __construct(array $connectionParams)
    {
        $this->connectionId = UUID::uuid4()->toString();
        if (isset($connectionParams['useUUID']) && $connectionParams['useUUID']) {
            $this->isUsingUUID = true;
        } else {
            $this->isUsingUUID = false;
        }
        unset($connectionParams['useUUID']);
        $this->connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
        $this->SchemaManager = $this->connection->createSchemaManager();
        $this->platform = $this->connection->getDatabasePlatform();
        $this->ModelManager = new ModelManager();
        $this->RecordManager = new RecordManager($this->connection, $this->ModelManager, $this->isUsingUUID);
        $this->TableManager = new TableManager($this->connection, $this->isUsingUUID);
        $this->ModelManager->setConnection($this);
    }

    /**
     * Get the instance of SchemaManager
     * @return AbstractSchemaManager
     */
    public function getSchemaManager(): AbstractSchemaManager
    {
        return $this->SchemaManager;
    }

    /**
     * Get the instance of Platform
     * @return AbstractPlatform
     */
    public function getPlatform(): AbstractPlatform
    {
        return $this->platform;
    }

    /**
     * Check if the connection is using UUID
     * @return bool
     */
    public function isUsingUUID(): bool
    {
        return $this->isUsingUUID;
    }

    /**
     * Get the instance of RecordManager
     * @return RecordManager
     */
    public function getRecordManager(): RecordManager
    {
        return $this->RecordManager;
    }

    /**
     * Get the instance of TableManager
     * @return TableManager
     */
    public function getTableManager(): TableManager
    {
        return $this->TableManager;
    }

    /**
     * Get the instance of ModelManager
     * @return ModelManager
     */
    public function getModelManager(): ModelManager
    {
        return $this->ModelManager;
    }

    /**
     * Get the connection id
     * @return string
     */
    public function getConnectionId(): string
    {
        return $this->connectionId;
    }


    /**
     * Magic method to call methods on the connection
     * @param string $method
     * @param array<mixed> $args
     * @return mixed
     */
    function __call(string $method, array $args): mixed
    {
        
        return $this->connection->$method(...$args);

    }


}