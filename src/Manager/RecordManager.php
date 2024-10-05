<?php

namespace Scrawler\Arca\Manager;

use \Scrawler\Arca\Collection;
use \Scrawler\Arca\QueryBuilder;
use \Scrawler\Arca\Model;
use \Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;

/**
 * Class responsible for manging single records
 */
class RecordManager
{
    private Connection $connection;

    private bool $isUsingUUID;

    private ModelManager $modelManager;
    
    /**
     * Create RecordManager
     */
    public function __construct(Connection $connection, ModelManager $modelManager,bool $isUsingUUID = false)
    {
        $this->connection = $connection;
        $this->isUsingUUID = $isUsingUUID;
        $this->modelManager = $modelManager;
    }


    /**
     * Create a new record
     *
     */
    public function insert(Model $model) : mixed
    {
        if ($this->isUsingUUID) {
            $model->id = UUID::uuid4()->toString();
        }
        $this->connection->insert($model->getName(), $model->getProperties());
        if ($this->isUsingUUID) {
            return $model->id;
        }
        return (int) $this->connection->lastInsertId();
    }


    /**
     * Update a record
     *
     */
    public function update(Model $model) : mixed
    {
        $this->connection->update($model->getName(), $model->getProperties(), ['id'=>$model->getId()]);
        return $model->getId();
    }


    /**
     * Delete a record
     *
     */
    public function delete(Model $model) : mixed
    {
        $this->connection->delete($model->getName(), ['id'=>$model->getId()]);
        return $model->getId();
    }

    /**
    * Get single record by id
    *
    */
    public function getById($table, mixed $id): Model|null
    {
        $query =  (new QueryBuilder($this->connection,$this->modelManager))
                 ->select('*')
                 ->from($table, 't')
                 ->where("t.id = '".$id."'");
        return $query->first();
    }


    /**
     * Get all records
     *
     */
    public function getAll(string $tableName): Collection
    {
        return (new QueryBuilder($this->connection,$this->modelManager))
            ->select('*')
            ->from($tableName, 't')
            ->get();
    }

    /**
     * get query builder from db
     *
     */
    public function find(String $name) : QueryBuilder
    {
        return (new QueryBuilder($this->connection,$this->modelManager))
        ->select('*')
        ->from($name, 't');
    }
}
