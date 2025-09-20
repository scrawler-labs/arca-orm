<?php

/*
 * This file is part of the Scrawler package.
 *
 * (c) Pranjal Pandey <its.pranjalpandey@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Scrawler\Arca\Manager;

use Doctrine\DBAL\Connection;
use Scrawler\Arca\Config;
use Scrawler\Arca\Exception\InvalidIdException;
use Scrawler\Arca\Model;

final class WriteManager
{
    private const RELATION_ONE_TO_ONE = 'oto';
    private const RELATION_ONE_TO_MANY = 'otm';
    private const RELATION_MANY_TO_MANY = 'mtm';
    private const ID_FIELD = 'id';
    private const ID_SUFFIX = '_id';

    public function __construct(
        private readonly Connection $connection,
        private readonly TableManager $tableManager,
        private readonly RecordManager $recordManager,
        private readonly ModelManager $modelManager,
        private readonly Config $config,
    ) {
    }

    /**
     * Save model into database.
     *
     * @return string|int
     * @throws InvalidIdException
     */
    public function save(Model $model): string|int
    {
        if ($model->hasForeign(self::RELATION_ONE_TO_ONE)) {
            $this->saveForeignOneToOne($model);
        }

        $this->ensureTableExists($model);

        $id = $this->executeInTransaction(function() use ($model) {
            $id = $this->createRecords($model);
            $model->set(self::ID_FIELD, $id);
            return $id;
        });

        $this->handleRelations($model);
        $this->finalizeModel($model);

        return $id;
    }

    private function handleRelations(Model $model): void
    {
        if ($model->hasForeign(self::RELATION_ONE_TO_MANY)) {
            $this->saveForeignOneToMany($model);
        }

        if ($model->hasForeign(self::RELATION_MANY_TO_MANY)) {
            $this->saveForeignManyToMany($model);
        }
    }

    private function finalizeModel(Model $model): void
    {
        $model->cleanModel();
        $model->setLoaded();
    }

    private function executeInTransaction(callable $callback): mixed
    {
        if (!$this->connection->isTransactionActive()) {
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
        
        // If already in transaction, just execute the callback
        return $callback();
    }

    /**
     * Create tables.
     *
     * @param array<TableConstraint> $constraints
     */
    private function createTable(Model $model, array $constraints = []): void
    {
        if (!$this->config->isFrozen()) {
            $table = $this->tableManager->createTable($model, $constraints);
            $this->tableManager->saveOrUpdateTable($model->getName(), $table);
        }
    }

    /**
     * Add constraint to table.
     *
     * @return array<TableConstraint>
     */
    private function createConstraintsOto(Model $model): array
    {
        $constraints = [];
        foreach ($model->getForeignModels('oto') as $foreign) {
            $constraints[] = new TableConstraint(
                $foreign->getName(),
                $foreign->getName().'_id',
                'id',
            );
        }

        return $constraints;
    }

    /**
     * Add constraint to table.
     *
     * @return array<TableConstraint>
     */
    private function createConstraintsOtm(Model $model): array
    {
        return [
            new TableConstraint(
                $model->getName(),
                $model->getName().'_id',
                'id'
            ),
        ];
    }

    /**
     * Add constraint to table.
     *
     * @return array<TableConstraint>
     */
    private function createConstraintsMtm(Model $model, Model $foreign): array
    {
        return [
            new TableConstraint(
                $model->getName(),
                $model->getName().'_id',
                'id'
            ),
            new TableConstraint(
                $foreign->getName(),
                $foreign->getName().'_id',
                'id'
            ),
        ];
    }

    /**
     * Create records.
     */
    private function createRecords(Model $model): mixed
    {
        if ($model->isLoaded()) {
            return $this->recordManager->update($model);
        }

        if ($model->hasIdError()) {
            throw new InvalidIdException();
        }

        return $this->recordManager->insert($model);
    }

    /**
     * Save One to One related model into database.
     */
    private function saveForeignOneToOne(Model $model): void
    {
        foreach ($model->getForeignModels(self::RELATION_ONE_TO_ONE) as $foreign) {
            $this->createTable($foreign);
        }

        $this->executeInTransaction(function() use ($model) {
            foreach ($model->getForeignModels(self::RELATION_ONE_TO_ONE) as $foreign) {
                $id = $this->createRecords($foreign);
                $this->finalizeModel($foreign);
                $name = $foreign->getName() . self::ID_SUFFIX;
                $model->$name = $id;
            }
        });
    }

    /**
     * Save One to Many related model into database.
     */
    private function saveForeignOneToMany(Model $model): void
    {
        $id = $model->getId();
        foreach ($model->getForeignModels(self::RELATION_ONE_TO_MANY) as $foreign) {
            $key = $model->getName() . self::ID_SUFFIX;
            $foreign->$key = $id;
            $this->createTable($foreign, $this->createConstraintsOtm($model));
        }

        $this->executeInTransaction(function() use ($model) {
            foreach ($model->getForeignModels(self::RELATION_ONE_TO_MANY) as $foreign) {
                $this->createRecords($foreign);
                $this->finalizeModel($foreign);
            }
        });
    }

    /**
     * Save Many to Many related model into database.
     */
    private function saveForeignManyToMany(Model $model): void
    {
        $id = $model->getId();
        foreach ($model->getForeignModels(self::RELATION_MANY_TO_MANY) as $foreign) {
            $model_id = $model->getName() . self::ID_SUFFIX;
            $foreign_id = $foreign->getName() . self::ID_SUFFIX;
            $relational_table = $this->modelManager->create(
                $model->getName() . '_' . $foreign->getName()
            );

            $default_id = $this->config->isUsingUUID() ? '' : 0;
            $relational_table->$model_id = $default_id;
            $relational_table->$foreign_id = $default_id;

            $this->createTable($foreign);
            $this->createTable(
                $relational_table,
                $this->createConstraintsMtm($model, $foreign)
            );
        }

        $this->executeInTransaction(function() use ($model, $id) {
            foreach ($model->getForeignModels(self::RELATION_MANY_TO_MANY) as $foreign) {
                $rel_id = $this->createRecords($foreign);
                $this->finalizeModel($foreign);

                $model_id = $model->getName() . self::ID_SUFFIX;
                $foreign_id = $foreign->getName() . self::ID_SUFFIX;

                $relational_table = $this->modelManager->create(
                    $model->getName() . '_' . $foreign->getName()
                );
                $relational_table->$model_id = $id;
                $relational_table->$foreign_id = $rel_id;

                $this->createRecords($relational_table);
            }
        });
    }

    private function ensureTableExists(Model $model): void
    {
        $constraints = [];
        if ($model->hasForeign(self::RELATION_ONE_TO_ONE)) {
            $constraints = $this->createConstraintsOto($model);
        }
        $this->createTable($model, $constraints);
    }
}
