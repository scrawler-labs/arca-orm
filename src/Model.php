<?php
declare(strict_types=1);
namespace Scrawler\Arca;

use Scrawler\Arca\Connection;
use Doctrine\DBAL\Types\Type;
use Scrawler\Arca\Traits\Model\Serializable;
use Scrawler\Arca\Traits\Model\Stringable;
use Scrawler\Arca\Traits\Model\Iterator;
use Scrawler\Arca\Traits\Model\ArrayAccess;
use Scrawler\Arca\Traits\Model\Getter;
use Scrawler\Arca\Traits\Model\Setter;


/**
 * Model class that represents single record in database
 */
class Model implements \Stringable, \IteratorAggregate, \ArrayAccess
{
    use Iterator;
    use Stringable;
    use Serializable;
    use ArrayAccess;
    use Getter;
    use Setter;

    /**
     * Store all properties of model
     * @var array<string,array<string,mixed>>
     */
    private array $__properties = array();

    /**
     * Store the table name of model
     * @var string
     */
    private string $table;
    /**
     * Store the metadata of model
     * @var array<string,mixed>
     */
    private array $__meta = [];
    /**
     * Store the connection
     * @var Connection
     */
    private Connection $connection;


    /**
     * Create a new model
     * @param string $name
     * @param Connection $connection
     */
    public function __construct(string $name, Connection $connection)
    {

        $this->table = $name;
        $this->connection = $connection;
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
     * adds the key to properties
     * @param string $key
     * @param mixed $val
     * @return void
     */
    public function __set(string $key, mixed $val): void
    {
        $this->set($key, $val);
    }

    /**
     * Adds the key to properties
     * @param string $key
     * @param mixed $val
     * @return void
     */
    public function set(string $key, mixed $val): void
    {
        if ($key === 'id') {
            $this->__meta['id'] = $val;
            $this->__meta['id_error'] = true;
        }

        if (\Safe\preg_match('/[A-Z]/', $key)) {
            $parts = \Safe\preg_split('/(?=[A-Z])/', $key, -1, PREG_SPLIT_NO_EMPTY);
            if (strtolower($parts[0]) === 'own') {
                $this->__meta['foreign_models']['otm'] = $this->createCollection($this->__meta['foreign_models']['otm'],$val);
                $this->__properties['all'][$key] = $val;
                return;
            }
            if (strtolower($parts[0]) === 'shared') {
                $this->__meta['foreign_models']['mtm'] = $this->createCollection($this->__meta['foreign_models']['mtm'],$val);
                $this->__properties['all'][$key] = $val;
                return;
            }
        }
        if ($val instanceof Model) {
            if (isset($this->__properties['all'][$key . '_id'])) {
                unset($this->__properties['all'][$key . '_id']);
            }
            $this->__meta['foreign_models']['oto'] = $this->createCollection($this->__meta['foreign_models']['oto'], Collection::fromIterable([$val]));
            $this->__properties['all'][$key] = $val;
            return;
        }

        $type = $this->getDataType($val);

        if ($type === 'boolean') {
            ($val) ? $val = 1 : $val = 0;
        }

        if ($type === 'json_document') {
            $val = Type::getType('json_document')->convertToDatabaseValue($val, $this->connection->getDatabasePlatform());
        }

        $this->__properties['self'][$key] = $val;
        $this->__properties['all'][$key] = $val;
        $this->__properties['type'][$key] = $type;

    }


    /**
     * Get a key from properties, keys can be relational
     * like sharedList,ownList or foreign table
     * @param string $key
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    /**
     * Get a key from properties, keys can be relational
     * like sharedList,ownList or foreign table
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed
    {
        if (\Safe\preg_match('/[A-Z]/', $key)) {
            $parts = \Safe\preg_split('/(?=[A-Z])/', $key, -1, PREG_SPLIT_NO_EMPTY);
            if (strtolower($parts[0]) == 'own') {
                if (strtolower($parts[2]) == 'list') {
                    $result = $this->connection->getRecordManager()->find(strtolower($parts[1]))->where($this->getName() . '_id = "' . $this->__meta['id'] . '"')->get();
                    $this->set($key, $result);
                    return $result;
                }
            }
            if (strtolower($parts[0]) == 'shared') {
                if (strtolower($parts[2]) == 'list') {
                    $rel_table = $this->connection->getTableManager()->tableExists($this->table . '_' . strtolower($parts[1])) ? $this->table . '_' . strtolower($parts[1]) : strtolower($parts[1]) . '_' . $this->table;
                    $relations = $this->connection->getRecordManager()->find($rel_table)->where($this->getName() . '_id = "' . $this->__meta['id'] . '"')->get();
                    $rel_ids = '';
                    foreach ($relations as $relation) {
                        $key = strtolower($parts[1]) . '_id';
                        $rel_ids .= "'" . $relation->$key . "',";
                    }
                    $rel_ids = substr($rel_ids, 0, -1);
                    $result = $this->connection->getRecordManager()->find(strtolower($parts[1]))->where('id IN (' . $rel_ids . ')')->get();
                    $this->set($key, $result);
                    return $result;
                }
            }
        }

        if (array_key_exists($key . '_id', $this->__properties['self'])) {
            $result = $this->connection->getRecordManager()->getById($key, $this->__properties['self'][$key . '_id']);
            $this->set($key, $result);
            return $result;
        }

        if (array_key_exists($key, $this->__properties['self'])) {
            $type = Type::getType($this->connection->getTableManager()->getTable($this->table)->getColumn($key)->getComment());
            $result = $type->convertToPHPValue($this->__properties['self'][$key], $this->connection->getDatabasePlatform());
            $this->set($key, $result);
            return $result;
        }

        throw new Exception\KeyNotFoundException();
    }

    /**
     * Eager Load relation variable
     * @param array<string> $relations
     * @return Model
     */
    public function with(array $relations): Model
    {
        foreach ($relations as $relation) {
            $this->get($relation);
        }
        return $this;
    }

    /**
     * Get the type of value
     * @param mixed $value
     * @return string
     */
    private function getDataType(mixed $value): string
    {
        $type = gettype($value);

        if ($type == 'array' || $type == 'object') {
            return 'json_document';
        }

        if ($type == 'string') {
            return 'text';
        }

        return $type;
    }

    /**
     * Check if array passed is instance of model
     * @param array<mixed>|Collection $models
     * @throws \Scrawler\Arca\Exception\InvalidModelException
     * @return Collection
     */
    private function createCollection(?Collection $collection, array|Collection $models): Collection
    {
        if(is_null($collection)){
            $collection = Collection::fromIterable([]);
        }

        if ($models instanceof Collection) {
            return $collection->merge($models);
        }

        if (count(array_filter($models, fn($d) => !$d instanceof Model)) > 0) {
            throw new Exception\InvalidModelException();
        }

        return $collection->merge(Collection::fromIterable($models));
    }



    /**
     * Unset a property from model
     * @param string $key
     * @return void
     */
    public function __unset(string $key): void
    {
        $this->unset($key);
    }

    /**
     * Unset a property from model
     * @param string $key
     * @return void
     */
    public function unset(string $key): void
    {
        unset($this->__properties['self'][$key]);
        unset($this->__properties['all'][$key]);
        unset($this->__properties['type'][$key]);
    }

    /**
     * Check if property exists
     * @param string $key
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return $this->isset($key);
    }

    /**
     * Check if property exists
     *
     * @param string $key
     * @return bool
     */
    public function isset(string $key): bool
    {
        return array_key_exists($key, $this->__properties['all']);
    }


    /**
     * check if model loaded from db
     * @return bool
     */
    public function isLoaded(): bool
    {
        return $this->__meta['is_loaded'];
    }


    /**
     * Check if model has id error
     * @return bool
     */
    public function hasIdError(): bool
    {
        return $this->__meta['id_error'];
    }


    /**
     * Save model to database
     * @return mixed
     */
    public function save(): mixed
    {
        Event::dispatch('__arca.model.save.' . $this->connection->getConnectionId(), [$this]);

        return $this->getId();
    }

    /**
     * Cleans up model internal state to be consistent after save
     * @return void
     */
    public function cleanModel()
    {
        $this->__properties['all'] = $this->__properties['self'];
        $this->__meta['id_error'] = false;
        $this->__meta['foreign_models']['otm'] = null;
        $this->__meta['foreign_models']['oto'] = null;
        $this->__meta['foreign_models']['mtm'] = null;

    }

    /**
     * Delete model data
     * @return void
     */
    public function delete(): void
    {
        Event::dispatch('__arca.model.delete.' . $this->connection->getConnectionId(), [$this]);
    }

    /**
     * Function used to compare to models
     * @param Model $other
     * @return bool
     */
    public function equals(self $other): bool
    {
        return ($this->getId() === $other->getId() && $this->toString() === $other->toString());
    }

    /**
     * Check if model has any relations
     * @param string $type
     * @return bool
     */
    public function hasForeign(string $type): bool
    {
        return !is_null($this->__meta['foreign_models'][$type]);
    }

}