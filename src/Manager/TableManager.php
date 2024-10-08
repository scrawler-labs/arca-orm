<?php
/*
 * This file is part of the Scrawler package.
 *
 * (c) Pranjal Pandey <its.pranjalpandey@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scrawler\Arca\Manager;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Exception\TableDoesNotExist;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Scrawler\Arca\Config;
use Scrawler\Arca\Model;

/**
 * Class resposible for creating and modifing table.
 */
final class TableManager
{
    /**
     * Store the instance of SchemaManager.
     */
    private AbstractSchemaManager $manager;

    /**
     * Store the instance of Platform.
     */
    private \Doctrine\DBAL\Platforms\AbstractPlatform $platform;

    /**
     * create TableManager.
     */
    public function __construct(
        private Connection $connection,
        private Config $config,
    ) {
        $this->manager = $this->connection->createSchemaManager();
        $this->platform = $this->connection->getDatabasePlatform();
    }

    /**
     * Creates a table schema from table instance.
     */
    public function createTableSchema(Table $table): Schema
    {
        return new Schema([$table]);
    }

    /**
     * Get Table schema from existing table.
     */
    public function getTableSchema(string $table): Schema
    {
        return new Schema([$this->manager->introspectTable($table)]);
    }

    /**
     * Get Table detail from existing table.
     */
    public function getTable(string $table): Table
    {
        return $this->manager->introspectTable($table);
    }

    /**
     * Create table from model.
     */
    public function createTable(Model $model): Table
    {
        $table = new Table($model->getName());
        if ($this->config->isUsingUUID()) {
            $table->addColumn('id', 'string', ['length' => 36, 'notnull' => true, 'comment' => 'string']);
        } else {
            $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true, 'comment' => 'integer']);
        }
        $table->setPrimaryKey(['id']);
        $types = $model->getTypes();
        foreach ($model->getSelfProperties() as $key => $value) {
            if ('id' != $key) {
                $table->addColumn($key, $types[$key], ['notnull' => false, 'comment' => $types[$key]]);
            }
        }

        return $table;
    }

    /**
     * Save table to database.
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
     * Add missing column to existing table from given table.
     */
    public function updateTable(string $table_name, Table $new_table): void
    {
        $comparator = $this->manager->createComparator();
        $old_table = $this->getTable($table_name);
        $old_schema = $this->getTableSchema($table_name);

        $tableDiff = $comparator->compareTables($old_table, $new_table);
        $mod_table = $old_table;
        foreach ($tableDiff->getAddedColumns() as $column) {
            $mod_table->addColumn(
                $column->getName(),
                Type::getTypeRegistry()->lookupName($column->getType()),
                [
                    'notnull' => $column->getNotnull(),
                    'comment' => $column->getComment(),
                    'default' => $column->getDefault(),
                ]
            );
        }
        $new_schema = $this->createTableSchema($mod_table);
        $schemaDiff = $comparator->compareSchemas($old_schema, $new_schema);

        $queries = $this->platform->getAlterSchemaSQL($schemaDiff);

        foreach ($queries as $query) {
            $this->connection->executeQuery($query);
        }
    }

    /**
     * Check if table exists.
     */
    public function tableExists(string $table_name): bool
    {
        try {
            $this->getTable($table_name);

            return true;
        } catch (TableDoesNotExist $e) {
            return false;
        }
    }

    /**
     * If table exist update it else create it.
     */
    public function saveOrUpdateTable(string $table_name, Table $new_table): void
    {
        if ($this->tableExists($table_name)) {
            $this->updateTable($table_name, $new_table);

            return;
        }

        $this->saveTable($new_table);

        return;
    }
}
