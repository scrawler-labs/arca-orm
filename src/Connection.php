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


class Connection{

    private DBALConnection $connection;
    private AbstractSchemaManager $SchemaManager;
    private AbstractPlatform $platform;
    private RecordManager $RecordManager;
    private TableManager $TableManager;
    private ModelManager $ModelManager;
    private bool $isUsingUUID;
    private string $connectionId;

    public function __construct(array $connectionParams)
    {
        $this->connectionId = UUID::uuid4()->toString();
        if(isset($connectionParams['useUUID']) && $connectionParams['useUUID']){
            $this->isUsingUUID = true;
        }else{
            $this->isUsingUUID = false;
        }
        unset($connectionParams['useUUID']);
        $this->connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
        $this->SchemaManager = $this->connection->createSchemaManager();
        $this->platform = $this->connection->getDatabasePlatform();
        $this->ModelManager = new ModelManager();
        $this->RecordManager = new RecordManager($this->connection,$this->ModelManager,$this->isUsingUUID);
        $this->TableManager = new TableManager($this->connection,$this->isUsingUUID);
        $this->ModelManager->setConnection($this);
    }

    public function getSchemaManager(){
        return $this->SchemaManager;
    }

    public function getPlatform(){
        return $this->platform;
    }

    public function isUsingUUID(){
        return $this->isUsingUUID;
    }

    public function getRecordManager(){
        return $this->RecordManager;
    }

    public function getTableManager(){
        return $this->TableManager;
    }

    public function getModelManager(){
        return $this->ModelManager;
    }

    public function getConnectionId(){
        return $this->connectionId;
    }

    function __call($method, $args) {
        return call_user_func_array(array($this->connection, $method), $args);
    }
    

}