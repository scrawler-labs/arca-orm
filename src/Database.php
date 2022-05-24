<?php
declare(strict_types=1);

namespace Scrawler\Arca;

class Database
{
    public \Doctrine\DBAL\Connection $connection;
    public \Doctrine\DBAL\Platforms\AbstractPlatform $platform;
    public \Doctrine\DBAL\Schema\AbstractSchemaManager $manager;
    private Manager\TableManager $tableManager;
    private Manager\RecordManager $recordManager;
    private bool $isFroozen = false;
    private static $instance;

    public function __construct(array $connectionParams)
    {
        $this->connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
        $this->platform = $this->connection->getDatabasePlatform();
        $this->manager = $this->connection->createSchemaManager();
        $this->tableManager = new Manager\TableManager($this);
        $this->recordManager = new Manager\RecordManager($this);
        self::$instance = $this;
    }

    /**
     * Executes an SQL query and returns the number of row affected
     *
     * @param string $sql
     * @param array $params
     * @return integer
     */
    public function exec(string $sql, array $params=array()): int
    {
        return  $this->connection->executeStatement($sql, $params);
    }

    /**
     * Returns array of data from SQL select statement
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function getAll(string $sql, array $params=[]): array
    {
        return  $this->connection->executeQuery($sql, $params)->fetchAllAssociative();
    }

    /**
     * Creates model from name
     *
     * @param string $name
     * @return \Scrawler\Arca\Model
     */
    public function create(string $name) : Model
    {
        $model = new Model($name);
        return $model;
    }

    /**
     * Save model into database
     *
     * @param \Scrawler\Arca\Model $model
     * @return integer
     */
    public function save(\Scrawler\Arca\Model $model) : int
    {
        if ($model->hasForeign('oto')) {
            $this->saveForeignOto($model);
        }
        
        $this->createTables($model);
        $this->connection->beginTransaction();

        try {
            $id = $this->createRecords($model);
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
        
        if ($model->hasForeign('otm')) {
            $this->saveForeignOtm($model, $id);
        }

        if ($model->hasForeign('mtm')) {
            $this->saveForeignMtm($model, $id);
        }

        return $id;
    }

    private function createTables($model)
    {
        if (!$this->isFroozen) {
            $table = $this->tableManager->createTable($model);
            $this->tableManager->saveOrUpdateTable($model->getName(), $table);
        }
    }

    private function createRecords($model)
    {
        if ($model->isLoaded()) {
            return $this->recordManager->update($model);
        }
        
        return $this->recordManager->insert($model);
    }

    /**
     * Save One to One related model into database
     */
    private function saveForeignOto(\Scrawler\Arca\Model $model): void
    {
        foreach ($model->getForeignModels('oto') as $foreign) {
            $this->createTables($foreign);
        }

        $this->connection->beginTransaction();
        try {
            foreach ($model->getForeignModels('oto') as $foreign) {
                $id = $this->createRecords($foreign);
                $name = $foreign->getName().'_id';
                $model->$name = $id;
            }
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * Save One to Many related model into database
     */
    private function saveForeignOtm(\Scrawler\Arca\Model $model, int $id): void
    {
        foreach ($model->getForeignModels('otm') as $foreigns) {
            foreach ($foreigns as $foreign) {
                $key = $model->getName().'_id';
                $foreign->$key = $id;
                $this->createTables($foreign);
            }
        }
        $this->connection->beginTransaction();
        try {
            foreach ($model->getForeignModels('otm') as $foreigns) {
                foreach ($foreigns as $foreign) {
                    $this->createRecords($foreign);
                }
            }
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * Save Many to Many related model into database
     */
    private function saveForeignMtm(\Scrawler\Arca\Model $model, int $id): void
    {
        foreach ($model->getForeignModels('mtm') as $foreigns) {
            foreach ($foreigns as $foreign) {
                $model_id = $model->getName().'_id';
                $foreign_id = $foreign->getName().'_id';
                $relational_table = $this->create($model->getName().'_'.$foreign->getName());
                $relational_table->$model_id = 0;
                $relational_table->$foreign_id = 0;
                $this->createTables($relational_table);
                $this->createTables($foreign);
            }
        }
        $this->connection->beginTransaction();
        try {
            foreach ($model->getForeignModels('mtm') as $foreigns) {
                foreach ($foreigns as $foreign) {
                    $rel_id = $this->createRecords($foreign);
                    $model_id = $model->getName().'_id';
                    $foreign_id = $foreign->getName().'_id';
                    $relational_table = $this->create($model->getName().'_'.$foreign->getName());
                    $relational_table->$model_id = $id;
                    $relational_table->$foreign_id = $rel_id;
                    $this->createRecords($relational_table);
                }
            }
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function getTableManager()
    {
        return $this->tableManager;
    }

    /**
     * Delete record from database
     *
     * @param \Scrawler\Arca\Model $model
     * @return integer
     */
    public function delete(\Scrawler\Arca\Model $model) : int
    {
        return $this->recordManager->delete($model);
    }

    /**
     * Get single
     *
     * @param String $table
     * @param integer|null $id
     * @return mixed
     */
    public function get(String $table, int $id=null) : mixed
    {
        if (is_null($id)) {
            return $this->recordManager->getAll($table);
        }

        $model = $this->create($table);
        return $this->recordManager->getById($model, $id);
    }

    /**
     * Returns QueryBuilder to build query for finding data
     * Eg: db()->find('user')->where('active = 1')->get();
     *
     * @param string $name
     * @return QueryBuilder
     */
    public function find(string $name) : QueryBuilder
    {
        return $this->recordManager->find($name);
    }


    /**
     * Get current instance of database
     *
     * @return void
     */
    public static function getInstance() : Database
    {
        return self::$instance;
    }

    public function freeze() : void
    {
        $this->isFroozen = true;
    }
}
