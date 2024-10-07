<?php
declare(strict_types=1);
namespace Scrawler\Arca;

use Scrawler\Arca\Connection;

/**
 * Model class that represents single record in database
 */
class Model
{
    /**
     * Store all properties of model
     * @var array<string,mixed>
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
    public function __construct(string $name,Connection $connection)
    {

        $this->table = $name;
        $this->connection = $connection;
        $this->__meta['has_foreign']['oto'] = false;
        $this->__meta['has_foreign']['otm'] = false;
        $this->__meta['has_foreign']['mtm'] = false;
        $this->__meta['is_loaded'] = false;
        $this->__meta['id_error'] = false;
        $this->__meta['foreign_models']['otm'] = [];
        $this->__meta['foreign_models']['oto'] = [];
        $this->__meta['foreign_models']['mtm'] = [];
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
        if ($key == 'id') {
            $this->__meta['id'] = $val;
            $this->__meta['id_error'] = true;
        }
        //bug: fix issue with bool storage
        if (gettype($val) == 'boolean') {
            ($val) ? $val = 1 : $val = 0;
        }

        if (\Safe\preg_match('/[A-Z]/', $key)) {
            $parts = \Safe\preg_split('/(?=[A-Z])/', $key, -1, PREG_SPLIT_NO_EMPTY);
            if (strtolower($parts[0]) == 'own') {
                if (gettype($val) == 'array') {
                    array_push($this->__meta['foreign_models']['otm'], $val);
                    $this->__meta['has_foreign']['otm'] = true;
                }
                return;
            }
            if (strtolower($parts[0]) == 'shared') {
                if (gettype($val) == 'array') {
                    array_push($this->__meta['foreign_models']['mtm'], $val);
                    $this->__meta['has_foreign']['mtm'] = true;
                }
                return;
            }
        }
        if ($val instanceof Model) {
            $this->__meta['has_foreign']['oto'] = true;
            array_push($this->__meta['foreign_models']['oto'], $val);
            return;
        }

        $this->__properties[$key] = $val;
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
    public function get(string $key) : mixed
    {

        if (\Safe\preg_match('/[A-Z]/', $key)) {
            $parts = \Safe\preg_split('/(?=[A-Z])/', $key, -1, PREG_SPLIT_NO_EMPTY);
            if (strtolower($parts[0]) == 'own') {
                if (strtolower($parts[2]) == 'list') {
                    return $this->connection->getRecordManager()->find(strtolower($parts[1]))->where($this->getName() . '_id = "' . $this->__meta['id'] . '"')->get();
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
                    return $this->connection->getRecordManager()->find(strtolower($parts[1]))->where('id IN (' . $rel_ids . ')')->get();
                }
            }
        }

        if (array_key_exists($key, $this->__properties)) {
            return $this->__properties[$key];
        }

        if (array_key_exists($key . '_id', $this->__properties)) {
            return $this->connection->getRecordManager()->getById($key, $this->__properties[$key . '_id']);
        }

        throw new Exception\KeyNotFoundException();
    }

    /**
     * Eager Load relation variable
     * @param array<string> $relations
     * @return Model
     */
    public function with(array $relations) : Model
    {
        foreach ($relations as $relation) {
            $this->get($relation);
        }
        return $this;
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
        unset($this->__properties[$key]);
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
        return array_key_exists($key, $this->__properties);
    }

    /**
     * Set all properties of model via array
     * @param array<mixed> $properties
     * @return Model
     */
    public function setProperties(array $properties): Model
    {
        $this->__properties = array_merge($this->__properties, $properties);
        if (isset($properties['id'])) {
            $this->__meta['id'] = $properties['id'];
            $this->__meta['id_error'] = true;
        }
        return $this;
    }

    /**
     * Get all properties in array form
     * @return array<mixed>
     */
    public function getProperties(): array
    {
        return $this->__properties;
    }

    /**
     * Get all properties in array form
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return $this->getProperties();
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
     * call when model is loaded from database
     * @return Model
     */
    public function setLoaded(): Model
    {
        $this->__meta['is_loaded'] = true;
        $this->__meta['id_error'] = false;
        return $this;
    }

    /**
     * Get current table name of model
     * @return string
     */
    public function getName(): string
    {
        return $this->table;
    }

    /**
     * Get current model Id or UUID
     * @return mixed
     */
    public function getId(): mixed
    {
        return $this->__meta['id'];
    }


    /**
     * Save model to database
     * @return mixed
     */
    public function save(): mixed
    {
        if($this->__meta['id_error']){
            throw new Exception\InvalidIdException();
        }
        Event::dispatch('__arca.model.save.'.$this->connection->getConnectionId(), [$this]);
        
        return $this->getId();
    }

    /**
     * Delete model data
     * @return void
     */
    public function delete(): void
    {
        Event::dispatch('__arca.model.delete.'.$this->connection->getConnectionId(), [$this]);
    }

    /**
     * Converts model into json object
     * @return string
     */
    public function toString(): string
    {
        return \Safe\json_encode($this->getProperties());
    }

    /**
     * Converts model into json object
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
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
        return $this->__meta['has_foreign'][$type];
    }

    /**
     * returns all relational models
     * @param string $type
     * @return mixed[]
     */
    public function getForeignModels(string $type): array
    {
        return $this->__meta['foreign_models'][$type];
    }
}