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
    public function __construct(
        private Connection $connection,
        private TableManager $tableManager,
        private RecordManager $recordManager,
        private ModelManager $modelManager,
        private Config $config,
    ) {
    }

    /**
     * Save model into database.
     *
     * @return mixed returns int for id and string for uuid
     */
    public function save(Model $model): mixed
    {
        if ($model->hasForeign('oto')) {
            $this->saveForeignOto($model);
        }

        $this->createTables($model);
        $this->connection->beginTransaction();

        try {
            $id = $this->createRecords($model);
            $model->set('id', $id);
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

        $model->cleanModel();
        $model->setLoaded();

        return $id;
    }

    /**
     * Create tables.
     */
    private function createTables(Model $model): void
    {
        if (!$this->config->isFrozen()) {
            $table = $this->tableManager->createTable($model);
            $this->tableManager->saveOrUpdateTable($model->getName(), $table);
        }
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
    private function saveForeignOto(Model $model): void
    {
        foreach ($model->getForeignModels('oto') as $foreign) {
            $this->createTables($foreign);
        }

        $this->connection->beginTransaction();
        try {
            foreach ($model->getForeignModels('oto') as $foreign) {
                $id = $this->createRecords($foreign);
                $foreign->cleanModel();
                $foreign->setLoaded();
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
     * Save One to Many related model into database.
     */
    private function saveForeignOtm(Model $model, mixed $id): void
    {
        foreach ($model->getForeignModels('otm') as $foreign) {
            $key = $model->getName().'_id';
            $foreign->$key = $id;
            $this->createTables($foreign);
        }
        $this->connection->beginTransaction();
        try {
            foreach ($model->getForeignModels('otm') as $foreign) {
                $this->createRecords($foreign);
                $foreign->cleanModel();
                $foreign->setLoaded();
            }
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * Save Many to Many related model into database.
     */
    private function saveForeignMtm(Model $model, mixed $id): void
    {
        foreach ($model->getForeignModels('mtm') as $foreign) {
            $model_id = $model->getName().'_id';
            $foreign_id = $foreign->getName().'_id';
            $relational_table = $this->modelManager->create($model->getName().'_'.$foreign->getName());
            if ($this->config->isUsingUUID()) {
                $relational_table->$model_id = '';
                $relational_table->$foreign_id = '';
            } else {
                $relational_table->$model_id = 0;
                $relational_table->$foreign_id = 0;
            }
            $this->createTables($relational_table);
            $this->createTables($foreign);
        }
        $this->connection->beginTransaction();
        try {
            foreach ($model->getForeignModels('mtm') as $foreign) {
                $rel_id = $this->createRecords($foreign);
                $foreign->cleanModel();
                $foreign->setLoaded();
                $model_id = $model->getName().'_id';
                $foreign_id = $foreign->getName().'_id';
                $relational_table = $this->modelManager->create($model->getName().'_'.$foreign->getName());
                $relational_table->$model_id = $id;
                $relational_table->$foreign_id = $rel_id;
                $this->createRecords($relational_table);
            }
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
}
