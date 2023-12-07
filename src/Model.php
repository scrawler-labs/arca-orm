<?php
declare(strict_types=1);
namespace Scrawler\Arca;
use Scrawler\Arca\Manager\ModelManager;

/**
 * Model class that represents single record in database
 */
class Model
{
    private array $properties = array();
    private string $table;
    private $_id = 0;
    private array $__meta = [];


    public function __construct(string $name)
    {

        $this->table = $name;
        $this->__meta['has_foreign']['oto'] = false;
        $this->__meta['has_foreign']['otm'] = false;
        $this->__meta['has_foreign']['mtm'] = false;
        $this->__meta['is_loaded'] = false;
        $this->__meta['foreign_models']['otm'] = [];
        $this->__meta['foreign_models']['oto'] = [];
        $this->__meta['foreign_models']['mtm'] = [];
    }

    /**
     * adds the key to properties
     *
     */
    public function __set(string $key, mixed $val): void
    {
        $this->set($key, $val);
    }

    /**
     * Adds the key to properties
     *
     */
    public function set(string $key, mixed $val): void
    {
        //bug: fix issue with bool storage
        if (gettype($val) == 'boolean') {
            ($val) ? $val = 1 : $val = 0;
        }

        if (preg_match('/[A-Z]/', $key)) {
            $parts = preg_split('/(?=[A-Z])/', $key, -1, PREG_SPLIT_NO_EMPTY);
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

        $this->properties[$key] = $val;
    }

    /**
     * Get a key from properties, keys can be relational
     * like sharedList,ownList or foreign table
     */
    public function __get(string $key): mixed
    {
        return $this->get($key);
    }

    /**
     * Get a key from properties, keys can be relational
     * like sharedList,ownList or foreign table
     */
    public function get(string $key) : mixed
    {

        if (preg_match('/[A-Z]/', $key)) {
            $parts = preg_split('/(?=[A-Z])/', $key, -1, PREG_SPLIT_NO_EMPTY);
            if (strtolower($parts[0]) == 'own') {
                if (strtolower($parts[2]) == 'list') {
                    return Managers::recordManager()->find(strtolower($parts[1]))->where($this->getName() . '_id = "' . $this->_id . '"')->get();
                }
            }
            if (strtolower($parts[0]) == 'shared') {
                if (strtolower($parts[2]) == 'list') {
                    $rel_table = Managers::tableManager()->tableExists($this->table . '_' . strtolower($parts[1])) ? $this->table . '_' . strtolower($parts[1]) : strtolower($parts[1]) . '_' . $this->table;
                    $relations = Managers::recordManager()->find($rel_table)->where($this->getName() . '_id = "' . $this->_id . '"')->get();
                    $rel_ids = '';
                    foreach ($relations as $relation) {
                        $key = strtolower($parts[1]) . '_id';
                        $rel_ids .= "'" . $relation->$key . "',";
                    }
                    $rel_ids = substr($rel_ids, 0, -1);
                    return Managers::recordManager()->find(strtolower($parts[1]))->where('id IN (' . $rel_ids . ')')->get();
                }
            }
        }

        if (array_key_exists($key, $this->properties)) {
            return $this->properties[$key];
        }

        if (array_key_exists($key . '_id', $this->properties)) {
            return Managers::recordManager()->getById(Managers::modelManager()->create($key), $this->properties[$key . '_id']);
        }

        throw new Exception\KeyNotFoundException();
    }

    public function with(array $relations) : Model
    {
        foreach ($relations as $relation) {
            $this->get($relation);
        }
        return $this;
    }


    /**
     * Unset a property from model
     *
     */
    public function __unset(string $key): void
    {
        $this->unset($key);
    }

    /**
     * Unset a property from model
     *
     */
    public function unset(string $key): void
    {
        unset($this->properties[$key]);
    }

    /**
     * Check if property exists
     *
     */
    public function __isset(string $key): bool
    {
        return $this->isset($key);
    }

    /**
     * Check if property exists
     *
     * @param string $key
     * @return boolean
     */
    public function isset(string $key): bool
    {
        return array_key_exists($key, $this->properties);
    }

    /**
     * Set all properties of model via array
     */
    public function setProperties(array $properties): Model
    {
        $this->properties = array_merge($this->properties, $properties);
        if (isset($properties['id'])) {
            $this->_id = $properties['id'];
        }
        return $this;
    }

    /**
     * Get all properties in array form
     *
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Get all properties in array form
     *
     */
    public function toArray(): array
    {
        return $this->getProperties();
    }

    /**
     *  check if model loaded from db
     */
    public function isLoaded(): bool
    {
        return $this->__meta['is_loaded'];
    }

    /**
     * call when model is loaded from database
     *
     */
    public function setLoaded(): Model
    {
        $this->__meta['is_loaded'] = true;
        return $this;
    }

    /**
     * Get current table name of model
     *
     */
    public function getName(): string
    {
        return $this->table;
    }

    /**
     * Get current model Id or UUID
     *
     */
    public function getId(): mixed
    {
        return $this->_id;
    }


    /**
     * Save model to database
     *
     */
    public function save(): mixed
    {
        $id = Event::dispatch('model.save', [$this]);
        $this->id = $id;
        $this->_id = $id;
        return $id;
    }

    /**
     * Delete model data
     */
    public function delete(): void
    {
        Event::dispatch('model.delete', [$this]);
    }

    /**
     * Converts model into json object
     */
    public function toString(): string
    {
        return \json_encode($this->properties);
    }

    /**
     * Converts model into json object
     */
    public function __toString(): string
    {
        return $this->toString();
    }


    /**
     * Function used to compare to models
     *
     */
    public function equals(self $other): bool
    {
        return ($this->getId() === $other->getId() && $this->toString() === $other->toString());
    }

    /**
     * Check if model has any relations
     */
    public function hasForeign($type): bool
    {
        return $this->__meta['has_foreign'][$type];
    }

    /**
     * returns all relational models
     */
    public function getForeignModels($type): array
    {
        return $this->__meta['foreign_models'][$type];
    }
}