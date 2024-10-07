<?php
declare(strict_types=1);

namespace Scrawler\Arca;
use Doctrine\DBAL\Types\Type;
use Dunglas\DoctrineJsonOdm\Serializer;
use Dunglas\DoctrineJsonOdm\Type\JsonDocumentType;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;


/**
 * 
 * Class that manages all interaction with database
 */
class Database
{
    /**
     * Store the instance of current connection
     * @var \Scrawler\Arca\Connection
     */
    private Connection $connection;
    /**
     * When $isFrozen is set to true tables are not updated/created
     * @var bool
     */
    private bool $isFroozen = false;
    

    /**
     * Create a new Database instance
     * @param \Scrawler\Arca\Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->registerEvents();
        $this->registerJsonDocumentType();

    }

    private function registerJsonDocumentType(): void
    {
        if (!Type::hasType('json_document')) {
            Type::addType('json_document', JsonDocumentType::class);
            Type::getType('json_document')->setSerializer(
                new Serializer([new BackedEnumNormalizer(), new UidNormalizer(), new DateTimeNormalizer(), new ArrayDenormalizer(), new ObjectNormalizer()], [new JsonEncoder()])
            );
        }
    }

    /**
     * Register events
     * @return void
     */
    public function registerEvents(): void
    {
        Event::subscribeTo('__arca.model.save.'.$this->connection->getConnectionId(), function ($model) {
            return $this->save($model);
        });
        Event::subscribeTo('__arca.model.delete.'.$this->connection->getConnectionId(), function ($model) {
            return $this->delete($model);
        });
    }

    /**
     * Executes an SQL query and returns the number of row affected
     *
     * @param string $sql
     * @param array<mixed> $params
     * @return int|numeric-string
     */
    public function exec(string $sql, array $params=array()): int|string
    {
        return  $this->connection->executeStatement($sql, $params);
    }

    /**
     * Returns array of data from SQL select statement
     *
     * @param string $sql
     * @param array<mixed> $params
     * @return array<int,array<string,mixed>>
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
        return $this->connection->getModelManager()->create($name);
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
            $model->set('id',$id);
            $model->setLoaded();
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

    /**
     * Create tables
     * @param \Scrawler\Arca\Model $model
     * @return void
     */
    private function createTables($model): void
    {
        if (!$this->isFroozen) {
            $table = $this->connection->getTableManager()->createTable($model);
            $this->connection->getTableManager()->saveOrUpdateTable($model->getName(), $table);
        }
    }

    /**
     * Create records
     * @param \Scrawler\Arca\Model $model
     * @return mixed
     */
    private function createRecords(Model $model) : mixed
    {
        if ($model->isLoaded()) {
            return $this->connection->getRecordManager()->update($model);
        }
        return $this->connection->getRecordManager()->insert($model);
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

    /**
     * Delete record from database
     *
     * @param \Scrawler\Arca\Model $model
     * @return mixed
     */
    public function delete(\Scrawler\Arca\Model $model) : mixed
    {
        return $this->connection->getRecordManager()->delete($model);
    }

    /**
     * Get collection of all records from table
     * @param string $table
     * @return Collection
     */
    public function get(string $table) : Collection
    {
       
        return $this->connection->getRecordManager()->getAll($table);
    }

    /**
     * Get single record
     * @param string $table
     * @param mixed $id
     * @return Model
     */
    public function getOne(string $table, mixed $id) : Model|null
    {
        return $this->connection->getRecordManager()->getById($table, $id);
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
        return $this->connection->getRecordManager()->find($name);
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
     * Helper function to unfreeze table
     * @return void
     */
    public function unfreeze() : void
    {
        $this->isFroozen = false;
    }

    /**
     * Checks if database is currently using uuid rather than id
     * @return bool
     */
    public function isUsingUUID() : bool
    {
        return $this->connection->isUsingUUID();
    }

    /**
     * Returns the current connection
     * @return Connection
     */
    public function getConnection() : Connection
    {
        return $this->connection;
    }

    /**
     * Check if tables exist
     * @param array<int,string> $tables
     * @return bool
     */
    public function tablesExist(array $tables) : bool
    {

        return $this->connection->getSchemaManager()->tablesExist($tables);
    }

    /**
     * Check if table exists
     * @param string $table
     * @return bool
     */
    public function tableExists(string $table) : bool
    {
      
        return $this->connection->getSchemaManager()->tableExists($table);
    }
}
