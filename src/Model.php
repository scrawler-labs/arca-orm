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
     * Store the table name of model.
     */
    private string $table;
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
        string $name,
        private Connection $connection,
        private RecordManager $recordManager,
        private TableManager $tableManager,
        private WriteManager $writeManager,
    ) {
        $this->table = $name;
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
        if ('id' === $key) {
            $this->__meta['id'] = $val;
            $this->__meta['id_error'] = true;
        }

        if (0 !== \Safe\preg_match('/[A-Z]/', $key)) {
            $parts = \Safe\preg_split('/(?=[A-Z])/', $key, -1, PREG_SPLIT_NO_EMPTY);
            if ('own' === strtolower((string) $parts[0])) {
                $this->__meta['foreign_models']['otm'] = $this->createCollection($this->__meta['foreign_models']['otm'], $val);
                $this->__properties['all'][$key] = $val;

                return;
            }
            if ('shared' === strtolower((string) $parts[0])) {
                $this->__meta['foreign_models']['mtm'] = $this->createCollection($this->__meta['foreign_models']['mtm'], $val);
                $this->__properties['all'][$key] = $val;

                return;
            }
        }
        if ($val instanceof Model) {
            if (isset($this->__properties['all'][$key.'_id'])) {
                unset($this->__properties['all'][$key.'_id']);
            }
            $this->__meta['foreign_models']['oto'] = $this->createCollection($this->__meta['foreign_models']['oto'], Collection::fromIterable([$val]));
            $this->__properties['all'][$key] = $val;

            return;
        }

        $type = $this->getDataType($val);

        $this->__properties['self'][$key] = $this->getDbValue($val, $type);
        $this->__properties['all'][$key] = $val;
        $this->__properties['type'][$key] = $type;
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
        // retrun if alraedy loaded
        if (array_key_exists($key, $this->__properties['all'])) {
            return $this->__properties['all'][$key];
        }

        if (0 !== \Safe\preg_match('/[A-Z]/', $key)) {
            $parts = \Safe\preg_split('/(?=[A-Z])/', $key, -1, PREG_SPLIT_NO_EMPTY);
            if ('own' === strtolower((string) $parts[0]) && 'list' === strtolower((string) $parts[2])) {
                $result = $this->recordManager->find(strtolower((string) $parts[1]))->where($this->getName().'_id = "'.$this->__meta['id'].'"')->get();
                $this->set($key, $result);

                return $result;
            }
            if ('shared' === strtolower((string) $parts[0]) && 'list' === strtolower((string) $parts[2])) {
                $rel_table = $this->tableManager->tableExists($this->table.'_'.strtolower((string) $parts[1])) ? $this->table.'_'.strtolower((string) $parts[1]) : strtolower((string) $parts[1]).'_'.$this->table;
                $relations = $this->recordManager->find($rel_table)->where($this->getName().'_id = "'.$this->__meta['id'].'"')->get();
                $rel_ids = '';
                foreach ($relations as $relation) {
                    $key = strtolower((string) $parts[1]).'_id';
                    $rel_ids .= "'".$relation->$key."',";
                }
                $rel_ids = substr($rel_ids, 0, -1);
                $result = $this->recordManager->find(strtolower((string) $parts[1]))->where('id IN ('.$rel_ids.')')->get();
                $this->set($key, $result);

                return $result;
            }
        }

        if (array_key_exists($key.'_id', $this->__properties['self'])) {
            $result = $this->recordManager->getById($key, $this->__properties['self'][$key.'_id']);
            $this->set($key, $result);

            return $result;
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
    public function refresh(): void{
        $model = $this->recordManager->getById($this->getName(), $this->getId());
        if(!is_null($model)){
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

        if ([] !== array_filter($models, fn ($d): bool => !$d instanceof Model)) {
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
}
