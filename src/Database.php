<?php
declare(strict_types=1);

namespace Scrawler\Arca;
use Scrawler\Arca\Manager\TableManager;
use Scrawler\Arca\Manager\RecordManager;
use Scrawler\Arca\Manager\ModelManager;
use \Doctrine\DBAL\Connection;

/**
 * 
 * Class that manages all interaction with database
 */
class Database
{
    /**
     * Doctrine DBAL connection instance
     * @var \Doctrine\DBAL\Connection
     */
    public Connection $connection;
    /**
     * Store the instance of current platform
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    public \Doctrine\DBAL\Platforms\AbstractPlatform $platform;
    /**
     * Doctrine schema manager
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    public \Doctrine\DBAL\Schema\AbstractSchemaManager $manager;
    /**
     * Instance of table manager responsible for working with tables
     * @var \Scrawler\Arca\Manager\TableManager
     */
    private TableManager $tableManager;
    /**
     * Instance of record manager responsible for working with records
     * @var \Scrawler\Arca\Manager\RecordManager
     */
    private RecordManager $recordManager;
    /**
     * Model manager rsponisble for bootstrapping model instance
     * @var \Scrawler\Arca\Manager\ModelManager
     */
    private ModelManager $modelManager;
    /**
     * When $isFrozen is set to true tables are not updated/created
     * @var bool
     */
    private bool $isFroozen = false;
    /**
     * You can switch between using uuid & id
     * @var bool
     */
    private bool $useUUID = false;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->platform = $this->connection->getDatabasePlatform();
        $this->manager = $this->connection->createSchemaManager();
        
    }

    /**
     * Once you initialize database, use this to set all manager for database class to work correctly
     * @param TableManager $tableManager
     * @param RecordManager $recordManager
     * @param ModelManager $modelManager
     * @return void
     */
    public function setManagers(TableManager $tableManager, RecordManager $recordManager, ModelManager $modelManager){
        $this->tableManager = $tableManager;
        $this->recordManager = $recordManager;
        $this->modelManager = $modelManager;

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
        return $this->modelManager->create($name);
    }

    /**
     * Save model into database
     *
     * @param \Scrawler\Arca\Model $model
     * @return mixed returns int for id and string for uuid
     */
    public function save(\Scrawler\Arca\Model $model) : mixed
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

    private function createRecords($model) : mixed
    {
        if ($model->isLoaded()) {
            return $this->recordManager->update($model);
        }
        
        return $this->recordManager->insert($model);
    }


    /**
     * Save One to One related model into database
     * @param Model $model
     * @return void
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
     * @param Model $model
     * @param mixed $id
     * @return void
     */
    private function saveForeignOtm(\Scrawler\Arca\Model $model, mixed $id): void
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
     * @param Model $model
     * @param mixed $id
     * @return void
     */
    private function saveForeignMtm(\Scrawler\Arca\Model $model, mixed $id): void
    {
        foreach ($model->getForeignModels('mtm') as $foreigns) {
            foreach ($foreigns as $foreign) {
                $model_id = $model->getName().'_id';
                $foreign_id = $foreign->getName().'_id';
                $relational_table = $this->create($model->getName().'_'.$foreign->getName());
                if ($this->isUsingUUID()) {
                    $relational_table->$model_id = "";
                    $relational_table->$foreign_id = "";
                } else {
                    $relational_table->$model_id = 0;
                    $relational_table->$foreign_id = 0;
                }
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
     * @return mixed
     */
    public function delete(\Scrawler\Arca\Model $model) : mixed
    {
        return $this->recordManager->delete($model);
    }

    /**
     * Get collection of all records from table
     *
     * @param String $table
     * @param mixed|null $id
     * @return mixed
     */
    public function get(String $table,mixed $id = null) : Model|Collection
    {
        // For backward compatibility reason
        if($id != null){
            return $this->getOne($table,$id);
        }

        return $this->recordManager->getAll($table);
    }

    /**
     * Get single record
     *
     * @param String $table
     * @param mixed $id
     * @return mixed
     */
    public function getOne(String $table, mixed $id) : Model
    {
        return $this->recordManager->getById($this->create($table), $id);
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
     * Freezes table for production
     * @return void
     */
    public function freeze() : void
    {
        $this->isFroozen = true;
    }

    /**
     * Call this to useUUID over normal id
     * @return void
     */
    public function useUUID() : void
    {
        $this->useUUID = true;
    }

    /**
     * Call this to use id (id is used by default)
     * @return void
     */
    public function useID() : void
    {
        $this->useUUID = false;
    }

    /**
     * Checks if database is currently using uuid rather than id
     * @return bool
     */
    public function isUsingUUID() : bool
    {
        return $this->useUUID;
    }
}
