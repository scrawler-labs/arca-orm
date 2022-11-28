<?php

namespace Scrawler\Arca\Manager;

use \Doctrine\DBAL\Schema\Schema;
use \Doctrine\DBAL\Schema\Table;
use \Doctrine\DBAL\Schema\Comparator;
use \Scrawler\Arca\Database;

class TableManager
{
    private Database $db;

    /**
     * create TableManager
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Creates a table schema from table instance
     * @param \Scrawler\Arca\Model $model
     * @return \Doctrine\DBAL\Schema\Schema
     */
    public function createTableSchema(Table $table): Schema
    {
        return new Schema([$table]);
    }

    /**
     * Get Table schema from existing table
     * @param String $table (name of table)
     * @return \Doctrine\DBAL\Schema\Schema
     */
    public function getTableSchema(String $table): Schema
    {
        return new Schema([$this->db->manager->listTableDetails($table)]);
    }
    
    /**
     * Get Table detail from existing table
     * @param String $table (name of table)
     * @return \Doctrine\DBAL\Schema\Table
     */
    public function getTable(String $table) : Table
    {
        return $this->db->manager->listTableDetails($table);
    }

    /**
     * Create table from model
     * @param \Scrawler\Arca\Model $model
     * @return \Doctrine\DBAL\Schema\Table
     */
    public function createTable($model) : Table
    {
        $table = new Table($model->getName());
        if ($this->db->isUsingUUID()) {
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
     * @param \Doctrine\DBAL\Schema\Table $table
     * @return void
     */
    public function saveTable(Table $table): void
    {
        $schema = $this->createTableSchema($table);
        $queries = $schema->toSql($this->db->platform);
        foreach ($queries as $query) {
            $this->db->connection->executeQuery($query);
        }
    }

    /**
     * Add missing column to existing table from given table
     * @param String $table_name (table name of existing table)
     * @param \Doctrine\DBAL\Schema\Table  $new_table (table schema of new table)
     * @return void
     */
    public function updateTable(String $table_name, Table $new_table) : void
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

            $queries = $schemaDiff->toSaveSql($this->db->platform);
        
            foreach ($queries as $query) {
                $this->db->connection->executeQuery($query);
            }
        }
    }

    /**
     * Check if table exists
     * @param String $table_name
     * @return bool
     */
    public function tableExists(String $table_name): bool
    {
        return !empty($this->getTable($table_name)->getColumns());
    }

    /**
     * If table exist update it else create it
     * @param String $table_name (table name of existing table)
     * @param \Doctrine\DBAL\Schema\Table  $new_table (table schema of new table)
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
