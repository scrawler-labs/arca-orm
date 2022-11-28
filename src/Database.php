<?php
declare(strict_types=1);

namespace Scrawler\Arca;
use Scrawler\Arca\Manager\TableManager;
use Scrawler\Arca\Manager\RecordManager;
use Scrawler\Arca\Manager\ModelManager;
use \Doctrine\DBAL\DriverManager;

class Database
{
    public \Doctrine\DBAL\Connection $connection;
    public \Doctrine\DBAL\Platforms\AbstractPlatform $platform;
    public \Doctrine\DBAL\Schema\AbstractSchemaManager $manager;
    private TableManager $tableManager;
    private RecordManager $recordManager;
    private ModelManager $modelManager;
    private bool $isFroozen = false;
    private bool $useUUID = false;

    public function __construct(DriverManager $connection)
    {
        $this->connection = $connection;
        $this->platform = $this->connection->getDatabasePlatform();
        $this->manager = $this->connection->createSchemaManager();
        
    }

    public function setManagers(TableManager $tableManger, RecordManager $recordManager, ModelManager $modelManager){
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
        return $this->modelManager->create($name)
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
     * Get single
     *
     * @param String $table
     * @param mixed|null $id
     * @return mixed
     */
    public function get(String $table, mixed $id=null) : mixed
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

    public function freeze() : void
    {
        $this->isFroozen = true;
    }

    public function useUUID() : void
    {
        $this->useUUID = true;
    }

    public function useID() : void
    {
        $this->useUUID = false;
    }

    public function isUsingUUID() : bool
    {
        return $this->useUUID;
    }
}
