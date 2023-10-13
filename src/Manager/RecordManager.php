<?php

namespace Scrawler\Arca\Manager;

use \Scrawler\Arca\Collection;
use \Scrawler\Arca\QueryBuilder;
use \Scrawler\Arca\Model;
use \Scrawler\Arca\Database;
use Ramsey\Uuid\Uuid;

/**
 * Class responsible for manging single records
 */
class RecordManager
{
    private Database $db;
    
    /**
     * Create RecordManager
     * @param \Scrawler\Arca\Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }


    /**
     * Create a new record
     *
     * @param \Scrawler\Arca\Model $model
     * @return mixed $id int for id and string for uuid
     */
    public function insert(Model $model) : mixed
    {
        if ($this->db->isUsingUUID()) {
            $model->id = UUID::uuid4()->toString();
        }
        $this->db->connection->insert($model->getName(), $model->getProperties());
        if ($this->db->isUsingUUID()) {
            return $model->id;
        }
        return (int) $this->db->connection->lastInsertId();
    }


    /**
     * Update a record
     *
     * @param \Scrawler\Arca\Model $model
     * @return mixed $id
     */
    public function update(Model $model) : mixed
    {
        $this->db->connection->update($model->getName(), $model->getProperties(), ['id'=>$model->getId()]);
        return $model->getId();
    }


    /**
     * Delete a record
     *
     * @param \Scrawler\Arca\Model $model
     * @return mixed $id
     */
    public function delete(Model $model) : mixed
    {
        $this->db->connection->delete($model->getName(), ['id'=>$model->getId()]);
        return $model->getId();
    }

    /**
    * Get single record by id
    *
    * @param \Scrawler\Arca\Model $model
    * @return \Scrawler\Arca\Model
    */
    public function getById(Model $model, mixed $id): Model
    {
        $query =  (new QueryBuilder($this->db))
                 ->select('*')
                 ->from($model->getName(), 't')
                 ->where("t.id = '".$id."'");
        $result = $this->db->connection->executeQuery($query)->fetchAssociative();
        $result = $result ? $result : [];
        return $model->setProperties($result)->setLoaded();
    }


    /**
     * Get all records
     *
     * @param string $tableName
     * @return \Scrawler\Arca\Collection
     */
    public function getAll(string $tableName): Collection
    {
        return (new QueryBuilder($this->db))
            ->select('*')
            ->from($tableName, 't')
            ->get();
    }

    /**
     * get query builder from db
     *
     * @param String $name
     * @return \Scrawler\Arca\QueryBuilder
     */
    public function find(String $name) : QueryBuilder
    {
        return (new QueryBuilder($this->db))
        ->select('*')
        ->from($name, 't');
    }
}
