<?php

namespace Scrawler\Arca\Manager;

use \Scrawler\Arca\Collection;
use \Scrawler\Arca\QueryBuilder;
use \Scrawler\Arca\Model;
use \Scrawler\Arca\Database;

class RecordManager
{
    private \Scrawler\Arca\Database $db;
    private \Scrawler\Arca\QueryBuilder $queryBuilder;
    
    /**
     * Create RecordManager
     * @param \Scrawler\Arca\Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->queryBuilder = new QueryBuilder($db->connection);
    }


    /**
     * Create a new record
     *
     * @param \Scrawler\Arca\Model $model
     * @return Integer $id
     */
    public function insert(Model $model) : int
    {
        $this->db->connection->insert($model->getName(), $model->getProperties());
        return (int) $this->db->connection->lastInsertId();
    }


    /**
     * Update a record
     *
     * @param \Scrawler\Arca\Model $model
     * @return Integer $id
     */
    public function update(Model $model) : int
    {
        $this->db->connection->update($model->getName(), $model->getProperties(), ['id'=>$model->getId()]);
        return (int) $model->getId();
    }


    /**
     * Delete a record
     *
     * @param \Scrawler\Arca\Model $model
     * @return Integer $id
     */
    public function delete(Model $model) : int
    {
        $this->db->connection->delete($model->getName(), ['id'=>$model->getId()]);
        return (int) $model->getId();
    }

    /**
    * Get single record by id
    *
    * @param \Scrawler\Arca\Model $model
    * @return \Scrawler\Arca\Model
    */
    public function getById(Model $model, int $id): Model
    {
        $query = $this
                 ->queryBuilder
                 ->select('*')
                 ->from($model->getName(), 't')
                 ->where('t.id = '.$id);
        $result = $this->db->connection->executeQuery($query)->fetchAssociative();
        $result ? $result : [];
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
        return $this->queryBuilder
            ->select('*')
            ->from($tableName)
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
        $query = $this->queryBuilder
        ->select('*')
        ->from($name, 't');
        return $query;
    }
}
