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

namespace Scrawler\Arca;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\ArrayParameterType;
use Scrawler\Arca\Manager\RecordManager;
use Scrawler\Arca\Manager\TableManager;
use Scrawler\Arca\Manager\WriteManager;
use Scrawler\Arca\Traits\Model\ArrayAccess;
use Scrawler\Arca\Traits\Model\Getter;
use Scrawler\Arca\Traits\Model\Iterator;
use Scrawler\Arca\Traits\Model\Setter;
use Scrawler\Arca\Traits\Model\Stringable;

/**
 * Model class that represents single record in database.
 *
 * @property int|string $id
 */
class Model implements \Stringable, \IteratorAggregate, \ArrayAccess
{
    use Iterator;
    use Stringable;
    use ArrayAccess;
    use Getter;
    use Setter;

    /**
     * Store all properties of model.
     *
     * @var array<string,array<string,mixed>>
     */
    private array $__properties = [];
    /**
     * Store the metadata of model.
     *
     * @var array<string,mixed>
     */
    private array $__meta = [];

    /**
     * Create a new model.
     */
    public function __construct(
        /**
         * Store the table name of model.
         */
        private string $table,
        private Connection $connection,
        private RecordManager $recordManager,
        private TableManager $tableManager,
        private WriteManager $writeManager,
    ) {
        $this->__properties['all'] = [];
        $this->__properties['self'] = [];
        $this->__meta['is_loaded'] = false;
        $this->__meta['id_error'] = false;
        $this->__meta['foreign_models']['otm'] = null;
        $this->__meta['foreign_models']['oto'] = null;
        $this->__meta['foreign_models']['mtm'] = null;
        $this->__meta['id'] = 0;
    }

    /**
     * adds the key to properties.
     */
    public function __set(string $key, mixed $val): void
    {
        $this->set($key, $val);
    }

    /**
     * Adds the key to properties.
     */
    public function set(string $key, mixed $val): void
    {
        // Handle ID setting
        if ('id' === $key) {
            $this->__meta['id'] = $val;
            $this->__meta['id_error'] = true;
            $this->setRegularProperty('id', $val);
            return;
        }

        // Handle model relations
        if ($val instanceof Model) {
            $this->handleModelRelation($key, $val);
            return;
        }

        // Handle complex relations (own/shared)
        if (0 !== \Safe\preg_match('/[A-Z]/', $key)) {
            if ($this->handleComplexRelation($key, $val)) {
                return;
            }
        }

        // Handle regular properties
        $this->setRegularProperty($key, $val);
    }

    private function handleRelationalKey(string $key, array $parts): mixed
    {
        if ('own' === strtolower((string) $parts[0])) {
            return $this->handleOwnRelation($key, $parts);
        }

        if ('shared' === strtolower((string) $parts[0])) {
            return $this->handleSharedRelation($key, $parts);
        }

        return null;
    }

    private function handleOwnRelation(string $key, array $parts): mixed
    {
        if ('list' !== strtolower((string) $parts[2])) {
            return null;
        }

        $qb = $this->recordManager->find(strtolower((string) $parts[1]));
        $qb->where($this->getName() . '_id = :id')
           ->setParameter('id', $this->__meta['id'], 
                $this->determineIdType($this->__meta['id'])
           );
        
        $result = $qb->get();

        $this->set($key, $result);
        return $result;
    }

    private function handleSharedRelation(string $key, array $parts): mixed
    {
        if ('list' !== strtolower((string) $parts[2])) {
            return null;
        }

        $targetTable = strtolower((string) $parts[1]);
        $relTable = $this->getRelationTable($targetTable);

        $db = $this->recordManager->find($relTable);
        $db->where($this->getName() . '_id = :id')
           ->setParameter('id', $this->__meta['id'], 
                $this->determineIdType($this->__meta['id'])
           );
        
        $relations = $db->get();
        $relIds = $this->extractRelationIds($relations, $targetTable);

        if (empty($relIds)) {
            return Collection::fromIterable([]);
        }

        $db = $this->recordManager->find($targetTable);
        $db->where('id IN (:ids)')
           ->setParameter('ids', $relIds, 
                $this->determineIdsType($relIds)
           );

        $result = $db->get();

        $this->set($key, $result);
        return $result;
    }

    private function getRelationTable(string $targetTable): string
    {
        return $this->tableManager->tableExists($this->table . '_' . $targetTable)
            ? $this->table . '_' . $targetTable
            : $targetTable . '_' . $this->table;
    }

    private function extractRelationIds(Collection $relations, string $targetTable): array
    {
        return $relations->map(function ($relation) use ($targetTable) {
            $key = $targetTable . '_id';

            return $relation->$key;
        })->toArray();
    }

    /**
     * Get a key from properties, keys can be relational
     * like sharedList,ownList or foreign table.
     */
    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    /**
     * Get a key from properties, keys can be relational
     * like sharedList,ownList or foreign table.
     */
    public function get(string $key): mixed
    {
        // Early return for cached properties
        if (array_key_exists($key, $this->__properties['all'])) {
            return $this->__properties['all'][$key];
        }

        // Handle foreign key relations
        if (array_key_exists($key . '_id', $this->__properties['self'])) {
            $result = $this->recordManager->getById($key, $this->__properties['self'][$key . '_id']);
            $this->set($key, $result);

            return $result; 
        }

        // Handle complex relations (own/shared)
        if (0 !== \Safe\preg_match('/[A-Z]/', $key)) {
            $parts = \Safe\preg_split('/(?=[A-Z])/', $key, -1, PREG_SPLIT_NO_EMPTY);
            $result = $this->handleRelationalKey($key, $parts);
            if (null !== $result) {
                return $result;
            }
        }

        throw new Exception\KeyNotFoundException();
    }

    /**
     * Eager Load relation variable.
     *
     * @param array<string> $relations
     */
    public function with(array $relations): Model
    {
        foreach ($relations as $relation) {
            $this->get($relation);
        }

        return $this;
    }

    /**
     * Refresh the current model from database.
     */
    public function refresh(): void
    {
        $model = $this->recordManager->getById($this->getName(), $this->getId());
        if (!is_null($model)) {
            $this->cleanModel();
            $this->setLoadedProperties($model->getSelfProperties());
            $this->setLoaded();
        }
    }

    /**
     * Unset a property from model.
     */
    public function __unset(string $key): void
    {
        $this->unset($key);
    }

    /**
     * Unset a property from model.
     */
    public function unset(string $key): void
    {
        unset($this->__properties['self'][$key]);
        unset($this->__properties['all'][$key]);
        unset($this->__properties['type'][$key]);
    }

    /**
     * Check if property exists.
     */
    public function __isset(string $key): bool
    {
        return $this->isset($key);
    }

    /**
     * Check if property exists.
     */
    public function isset(string $key): bool
    {
        return array_key_exists($key, $this->__properties['all']);
    }

    /**
     * check if model loaded from db.
     */
    public function isLoaded(): bool
    {
        return $this->__meta['is_loaded'];
    }

    /**
     * Check if model has id error.
     */
    public function hasIdError(): bool
    {
        return $this->__meta['id_error'];
    }

    /**
     * Save model to database.
     */
    public function save(): mixed
    {
        $this->id = $this->writeManager->save($this);

        return $this->getId();
    }

    /**
     * Cleans up model internal state to be consistent after save.
     */
    public function cleanModel(): void
    {
        $this->__properties['all'] = $this->__properties['self'];
        $this->__meta['id_error'] = false;
        $this->__meta['foreign_models']['otm'] = null;
        $this->__meta['foreign_models']['oto'] = null;
        $this->__meta['foreign_models']['mtm'] = null;
    }

    /**
     * Delete model data.
     */
    public function delete(): void
    {
        $this->recordManager->delete($this);
    }

    /**
     * Function used to compare to models.
     */
    public function equals(self $other): bool
    {
        return $this->getId() === $other->getId() && $this->toString() === $other->toString();
    }

    /**
     * Check if model has any relations.
     */
    public function hasForeign(string $type): bool
    {
        return !is_null($this->__meta['foreign_models'][$type]);
    }

    /**
     * Get the type of value.
     */
    private function getDataType(mixed $value): string
    {
        $type = gettype($value);

        if ('array' === $type || 'object' === $type) {
            return 'json_document';
        }

        if ('string' === $type) {
            return 'text';
        }

        if ('double' === $type) {
            return 'float';
        }

        return $type;
    }

    /**
     * Check if array passed is instance of model.
     *
     * @param array<mixed>|Collection $models
     *
     * @throws Exception\InvalidModelException
     */
    private function createCollection(?Collection $collection, array|Collection $models): Collection
    {
        if (is_null($collection)) {
            $collection = Collection::fromIterable([]);
        }

        if ($models instanceof Collection) {
            return $collection->merge($models);
        }

        if ([] !== array_filter($models, fn($d): bool => !$d instanceof Model)) {
            throw new Exception\InvalidModelException();
        }

        return $collection->merge(Collection::fromIterable($models));
    }

    /**
     * Get the database value from PHP value.
     */
    private function getDbValue(mixed $val, string $type): mixed
    {
        if ('boolean' === $type) {
            return ($val) ? 1 : 0;
        }

        return Type::getType($type)->convertToDatabaseValue($val, $this->connection->getDatabasePlatform());
    }

    private function handleModelRelation(string $key, Model $val): void
    {
        if (isset($this->__properties['all'][$key . '_id'])) {
            unset($this->__properties['all'][$key . '_id']);
        }

        $this->__meta['foreign_models']['oto'] = $this->createCollection(
            $this->__meta['foreign_models']['oto'],
            Collection::fromIterable([$val])
        );
        $this->__properties['all'][$key] = $val;
    }

    private function handleComplexRelation(string $key, mixed $val): bool
    {
        $parts = \Safe\preg_split('/(?=[A-Z])/', $key, -1, PREG_SPLIT_NO_EMPTY);
        $type = strtolower((string) $parts[0]);

        if ('own' === $type) {
            $this->__meta['foreign_models']['otm'] = $this->createCollection(
                $this->__meta['foreign_models']['otm'],
                $val
            );
            $this->__properties['all'][$key] = $val;

            return true;
        }

        if ('shared' === $type) {
            $this->__meta['foreign_models']['mtm'] = $this->createCollection(
                $this->__meta['foreign_models']['mtm'],
                $val
            );
            $this->__properties['all'][$key] = $val;

            return true;
        }

        return false;
    }

    private function setRegularProperty(string $key, mixed $val): void
    {
        $type = $this->getDataType($val);
        $this->__properties['self'][$key] = $this->getDbValue($val, $type);
        $this->__properties['all'][$key] = $val;
        $this->__properties['type'][$key] = $type;
    }

    /**
     * Determines the ParameterType for an ID value
     */
    private function determineIdType(int|string $id): ParameterType
    {
        return is_int($id) ? ParameterType::INTEGER : ParameterType::STRING;
    }

    /**
     * Get all properties of model.
     *
     * @return ArrayParameterType
     */
    private function determineIdsType(array $ids): ArrayParameterType
    {
        if (empty($ids)) {
            return ArrayParameterType::STRING;
        }

        $firstIdType = $this->determineIdType($ids[0]);

        return match ($firstIdType) {
            ParameterType::INTEGER => ArrayParameterType::INTEGER,
            ParameterType::STRING => ArrayParameterType::STRING,
        };
    }

}
