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
    private readonly AbstractSchemaManager $manager;

    /**
     * Store the instance of Platform.
     */
    private readonly \Doctrine\DBAL\Platforms\AbstractPlatform $platform;

    /**
     * create TableManager.
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly Config $config,
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
     *
     * @param array<TableConstraint> $constraints
     */
    public function createTable(Model $model, array $constraints = []): Table
    {
        $table = new Table($model->getName());
        $table = $this->addPrimaryKey($table);
        $types = $model->getTypes();
        foreach ($model->getSelfProperties() as $key => $value) {
            $key_array = explode('_', $key);
            if ('id' != $key && 'id' == end($key_array)) {
                $table = $this->addIdColumn($table, $key);
            } elseif ('id' != $key) {
                $table->addColumn(
                    $key,
                    $types[$key],
                    ['notnull' => false, 'comment' => $types[$key]]
                );
            }
        }

        foreach ($constraints as $constraint) {
            $table->addForeignKeyConstraint(
                $constraint->getForeignTableName(),
                [$constraint->getLocalColumnName()],
                [$constraint->getForeignColumnName()],
                ['onUpdate' => 'CASCADE', 'onDelete' => 'CASCADE']
            );
        }

        return $table;
    }

    private function addPrimaryKey(Table $table): Table
    {
        if ($this->config->isUsingUUID()) {
            $table->addColumn(
                'id',
                'string',
                ['length' => 36, 'notnull' => true, 'comment' => 'string']
            );
        } else {
            $table->addColumn(
                'id',
                'integer',
                ['unsigned' => true, 'autoincrement' => true, 'comment' => 'integer']
            );
        }
        $table->setPrimaryKey(['id']);

        return $table;
    }

    /**
     * Add id column to table.
     */
    private function addIdColumn(Table $table, string $key): Table
    {
        if ($this->config->isUsingUUID()) {
            $table->addColumn(
                $key,
                'string',
                ['length' => 36, 'notnull' => true, 'comment' => 'string']
            );
        } else {
            $table->addColumn(
                $key,
                'integer',
                ['unsigned' => true, 'notnull' => true, 'comment' => 'integer']
            );
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
        } catch (TableDoesNotExist) {
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
    }
}
