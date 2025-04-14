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
use Ramsey\Uuid\Uuid;
use Scrawler\Arca\Collection;
use Scrawler\Arca\Config;
use Scrawler\Arca\Model;
use Scrawler\Arca\QueryBuilder;

/**
 * Class responsible for manging single records.
 */
final class RecordManager
{
    /**
     * Create RecordManager.
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly ModelManager $modelManager,
        private readonly Config $config,
    ) {
    }

    /**
     * Create a new record.
     */
    public function insert(Model $model): mixed
    {
        if ($this->config->isUsingUUID()) {
            $model->set('id', Uuid::uuid4()->toString());
        }
        $this->connection->insert($model->getName(), $model->getSelfProperties());
        if ($this->config->isUsingUUID()) {
            return $model->get('id');
        }

        return (int) $this->connection->lastInsertId();
    }

    /**
     * Update a record.
     */
    public function update(Model $model): mixed
    {
        $this->connection->update($model->getName(), $model->getSelfProperties(), ['id' => $model->getId()]);

        return $model->getId();
    }

    /**
     * Delete a record.
     */
    public function delete(Model $model): mixed
    {
        $this->connection->delete($model->getName(), ['id' => $model->getId()]);

        return $model->getId();
    }

    /**
     * Get single record by id.
     */
    public function getById(string $table, mixed $id): ?Model
    {
        $query = (new QueryBuilder($this->connection, $this->modelManager))
            ->select('*')
            ->from($table, 't')
            ->where('t.id = ?')
            ->setParameter(0, $id);

        return $query->first();
    }

    /**
     * Get all records.
     */
    public function getAll(string $tableName): Collection
    {
        return (new QueryBuilder($this->connection, $this->modelManager))
            ->select('*')
            ->from($tableName, 't')
            ->get();
    }

    /**
     * get query builder from db.
     */
    public function find(string $name): QueryBuilder
    {
        return (new QueryBuilder($this->connection, $this->modelManager))
            ->select('*')
            ->from($name, 't');
    }

    /**
     * get query builder from db.
     */
    public function select(string $expression): QueryBuilder
    {
        return (new QueryBuilder($this->connection, $this->modelManager))
            ->select($expression);
    }
}
