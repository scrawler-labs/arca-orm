<?php

namespace Scrawler\Arca\Manager;

use \Doctrine\DBAL\Schema\Schema;
use \Doctrine\DBAL\Schema\Table;
use \Doctrine\DBAL\Schema\Comparator;
use \Doctrine\DBAL\Connection;
use \Doctrine\DBAL\Schema\AbstractSchemaManager;
use Scrawler\Arca\Model;

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
        $this->manager = $this->connection->getSchemaManager();
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
        return new Schema([$this->manager->listTableDetails($table)]);
    }
    
    /**
     * Get Table detail from existing table
     */
    public function getTable(string $table) : Table
    {
        return $this->manager->listTableDetails($table);
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
            $table->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
        }
        $table->setPrimaryKey(array("id"));
        foreach ($model->getProperties() as $key => $value) {
            if ($key != 'id') {
                $table->addColumn($key, gettype($value), ['notnull' => false, 'comment' => $key]);
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
        $comparator = new Comparator();
        $old_table = $this->getTable($table_name);
        $old_schema = $this->getTableSchema($table_name);

        $tableDiff = $comparator->diffTable($old_table, $new_table);
        $mod_table = $old_table;
        if ($tableDiff) {
            foreach ($tableDiff->addedColumns as $column) {
                $mod_table->addColumn($column->getName(), $column->getType()->getName(), ['notnull' => false, 'comment' => $column->getName()]);
            }
            $new_schema = $this->createTableSchema($mod_table);
            $schemaDiff = $comparator->compareSchemas($old_schema, $new_schema);

            $queries = $schemaDiff->toSaveSql($this->platform);
        
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
        return !empty($this->getTable($table_name)->getColumns());
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
