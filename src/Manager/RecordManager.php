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
    private const ID_COLUMN = 'id';
    private const DEFAULT_ALIAS = 't';
    private const ALL_COLUMNS = '*';

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
     * Execute operations within a transaction
     * 
     * @template T
     * @param callable(): T $callback
     * @return T
     * @throws \Exception
     */
    private function executeInTransaction(callable $callback): mixed
    {
        $this->connection->beginTransaction();
        try {
            $result = $callback();
            $this->connection->commit();
            return $result;
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * Create a new record.
     */
    public function insert(Model $model): mixed
    {
        return $this->executeInTransaction(function() use ($model) {
            if ($this->config->isUsingUUID()) {
                $model->set(self::ID_COLUMN, Uuid::uuid4()->toString());
            }
            
            $this->connection->insert(
                $model->getName(), 
                $model->getSelfProperties()
            );
            
            if ($this->config->isUsingUUID()) {
                return $model->get(self::ID_COLUMN);
            }

            return (int) $this->connection->lastInsertId();
        });
    }

    /**
     * Update a record.
     */
    public function update(Model $model): mixed
    {
        return $this->executeInTransaction(function() use ($model) {
            $this->connection->update(
                $model->getName(),
                $model->getSelfProperties(),
                [self::ID_COLUMN => $model->getId()]
            );
            return $model->getId();
        });
    }

    /**
     * Delete a record.
     */
    public function delete(Model $model): mixed
    {
        return $this->executeInTransaction(function() use ($model) {
            $this->connection->delete(
                $model->getName(),
                [self::ID_COLUMN => $model->getId()]
            );
            return $model->getId();
        });
    }

    /**
     * Get single record by id.
     */
    public function getById(string $table, mixed $id): ?Model
    {
        return $this->executeInTransaction(function() use ($table, $id) {
            return $this->createQueryBuilder()
                ->select(self::ALL_COLUMNS)
                ->from($table, self::DEFAULT_ALIAS)
                ->where(self::DEFAULT_ALIAS . '.' . self::ID_COLUMN . ' = ?')
                ->setParameter(0, $id)
                ->first();
        });
    }

    /**
     * Get all records.
     */
    public function getAll(string $tableName): Collection
    {
        return $this->executeInTransaction(function() use ($tableName) {
            return $this->createQueryBuilder()
                ->select(self::ALL_COLUMNS)
                ->from($tableName, self::DEFAULT_ALIAS)
                ->get();
        });
    }

    /**
     * Create a new QueryBuilder instance
     */
    private function createQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this->connection, $this->modelManager);
    }

    /**
     * Get query builder from db.
     */
    public function find(string $name): QueryBuilder
    {
        return $this->createQueryBuilder()
            ->select(self::ALL_COLUMNS)
            ->from($name, self::DEFAULT_ALIAS);
    }

    /**
     * Get query builder from db.
     */
    public function select(string $expression): QueryBuilder
    {
        return $this->createQueryBuilder()->select($expression);
    }
}
