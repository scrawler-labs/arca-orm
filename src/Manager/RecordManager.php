<?php

namespace Scrawler\Arca\Manager;

use \Scrawler\Arca\Collection;
use \Scrawler\Arca\QueryBuilder;
use \Scrawler\Arca\Model;
use \Scrawler\Arca\Database;
use Ramsey\Uuid\Uuid;

class RecordManager
{
    private \Scrawler\Arca\Database $db;
    
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
        $qb = new QueryBuilder($this->db->connection);
        $query =  $qb
                 ->select('*')
                 ->from($model->getName(), 't')
                 ->where("t.id = '".$id."'");
        $result = $this->db->connection->executeQuery($query)->fetchAssociative();
        $result = $result ? $result : [];
        $model->setProperties($result)->setLoaded();
        return $model;
    }


    /**
     * Get all records
     *
     * @param String $tableName
     * @return \Scrawler\Arca\Collection
     */
    public function getAll(String $tableName): Collection
    {
        $qb = new QueryBuilder($this->db->connection);
        return $qb
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
        $qb = new QueryBuilder($this->db->connection);
        $query = $qb
        ->select('*')
        ->from($name, 't');
        return $query;
    }
}
