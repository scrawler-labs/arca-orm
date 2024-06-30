<?php

namespace Scrawler\Arca\Manager;

use \Doctrine\DBAL\Schema\Schema;
use \Doctrine\DBAL\Schema\Table;
use \Doctrine\DBAL\Connection;
use \Doctrine\DBAL\Schema\AbstractSchemaManager;
use Scrawler\Arca\Model;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Schema\Exception\TableDoesNotExist; 

/**
 * Class resposible for creating and modifing table
 */
class TableManager
{
    private Connection $connection;

    private bool $isUsingUUID;

    private AbstractSchemaManager $manager;

    private \Doctrine\DBAL\Platforms\AbstractPlatform $platform;




    /**
     * create TableManager
     */
    public function __construct(Connection $connection, bool $isUsingUUID = false)
    {
        $this->connection = $connection;
        $this->isUsingUUID = $isUsingUUID;
        $this->manager = $this->connection->createSchemaManager();
        $this->platform = $this->connection->getDatabasePlatform();
    }


    /**
     * Creates a table schema from table instance
     */
    public function createTableSchema(Table $table): Schema
    {
        return new Schema([$table]);
    }

    /**
     * Get Table schema from existing table
     */
    public function getTableSchema(string $table): Schema
    {
        return new Schema([$this->manager->introspectTable($table)]);
    }
    
    /**
     * Get Table detail from existing table
     */
    public function getTable(string $table) : Table
    {
        return $this->manager->introspectTable($table);
    }

    /**
     * Create table from model
     */
    public function createTable(Model $model) : Table
    {
        $table = new Table($model->getName());
        if ($this->isUsingUUID) {
            $table->addColumn('id', 'string', ['length' => 36, 'notnull' => true,]);
        } else {
            $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        }
        $table->setPrimaryKey(array("id"));
        foreach ($model->getProperties() as $key => $value) {
            if ($key != 'id') {
                $type = gettype($value);
                if($type == 'string'){
                    $type = 'text';
                }
                $table->addColumn($key,$type , ['notnull' => false, 'comment' => $key]);
            }
        }
        return $table;
    }


    /**
     * Save table to database
     */
    public function saveTable(Table $table): void
    {
        $schema = $this->createTableSchema($table);
        $queries = $schema->toSql($this->platform);
        foreach ($queries as $query) {
            $this->connection->executeQuery($query);
        }
    }

    /**
     * Add missing column to existing table from given table
     */
    public function updateTable(string $table_name, Table $new_table) : void
    {
        $comparator = $this->manager->createComparator();
        $old_table = $this->getTable($table_name);
        $old_schema = $this->getTableSchema($table_name);

        $tableDiff = $comparator->compareTables($old_table, $new_table);
        $mod_table = $old_table;
        if ($tableDiff) {
            foreach ($tableDiff->getAddedColumns() as $column) {
                $mod_table->addColumn($column->getName(), Type::getTypeRegistry()->lookupName($column->getType()), ['notnull' => false, 'comment' => $column->getName()]);
            }
            $new_schema = $this->createTableSchema($mod_table);
            $schemaDiff = $comparator->compareSchemas($old_schema, $new_schema);

            $queries = $this->platform->getAlterSchemaSQL($schemaDiff);
        
            foreach ($queries as $query) {
                $this->connection->executeQuery($query);
            }
        }
    }

    /**
     * Check if table exists
     */
    public function tableExists(string $table_name): bool
    {
        try {
            $this->getTable($table_name);
            return true;
        }catch(TableDoesNotExist $e){
            return false;
        }
    }

    /**
     * If table exist update it else create it
     */
    public function saveOrUpdateTable(String $table_name, Table $new_table): void
    {
        if ($this->tableExists($table_name)) {
            $this->updateTable($table_name, $new_table);
            return;
        }

        $this->saveTable($new_table);
        return;
    }
}
