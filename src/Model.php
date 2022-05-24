<?php
declare(strict_types=1);
namespace Scrawler\Arca;

class Model
{
    private array $properties = array();
    private String $table;
    private int $_id = 0;
    private array $__meta = [];

    public function __construct(String $name)
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
     * @param [type] $key
     * @param [type] $val
     */
    public function __set(string $key, mixed $val): void
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

    public function __get(string $key): mixed
    {
        if (preg_match('/[A-Z]/', $key)) {
            $parts = preg_split('/(?=[A-Z])/', $key, -1, PREG_SPLIT_NO_EMPTY);
            if (strtolower($parts[0]) == 'own') {
                if (strtolower($parts[2])  == 'list') {
                    return Database::getInstance()->find(strtolower($parts[1]))->where($this->getName() . '_id = ' . $this->_id)->get();
                }
            }
            if (strtolower($parts[0]) == 'shared') {
                if (strtolower($parts[2])  == 'list') {
                    $rel_table = Database::getInstance()->getTableManager()->tableExists($this->table.'_'.strtolower($parts[1])) ? $this->table.'_'.strtolower($parts[1]) : strtolower($parts[1]).'_'.$this->table;
                    print('table: '.$rel_table);
                    $relations = Database::getInstance()->find($rel_table)->where($this->getName() . '_id = ' . $this->_id)->get();
                    $rel_ids = [];
                    foreach ($relations as $relation) {
                        $key = strtolower($parts[1]) . '_id';
                        array_push($rel_ids, $relation->$key);
                    }
                    print('near return');
                    return Database::getInstance()->find(strtolower($parts[1]))->where('id IN (' . implode(',', $rel_ids) . ')')->get();
                }
            }
        }

        if (array_key_exists($key, $this->properties)) {
            return $this->properties[$key];
        }
        
        if (array_key_exists($key.'_id', $this->properties)) {
            return Database::getInstance()->get($key, $this->properties[$key.'_id']);
        }
        
        throw new Exception\KeyNotFoundException();
    }

    /**
     * Unset a property from model
     *
     * @param string $key
     */
    public function __unset(string $key): void
    {
        unset($this->properties[$key]);
    }

    /**
     * Check if property exists
     *
     * @param string $key
     * @return boolean
     */
    public function __isset(string $key): bool
    {
        return array_key_exists($key, $this->properties);
    }
    
    /**
     * Set all properties of model via array
     *
     * @param array $properties
     * @return Model
     */
    public function setProperties(array $properties) : Model
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
     * @return array
     */
    public function getProperties() : array
    {
        return $this->properties;
    }

    /**
     *  check if model loaded from db
     * @return array
     */
    public function isLoaded() : bool
    {
        return  $this->__meta['is_loaded'];
    }

    /**
     * call when model is loaded from database
     *
     * @return array
     */
    public function setLoaded() : Model
    {
        $this->__meta['is_loaded'] = true;
        return $this;
    }
    
    /**
     * Get current table name of model
     *
     * @return String
     */
    public function getName() : String
    {
        return $this->table;
    }

    /**
     * Get current model Id
     *
     * @return int
     */
    public function getId() : int
    {
        return $this->_id;
    }


    /**
     * Save model to database
     *
     * @return int $id
     */
    public function save() : int
    {
        $id = Database::getInstance()->save($this);
        $this->id = $id;
        $this->_id = $id;
        return $id;
    }

    /**
     * Delete model data
     */
    public function delete() : void
    {
        Database::getInstance()->delete($this);
    }

    /**
     * converts model into json object
     * @return string
     */
    public function toString() : string
    {
        return \json_encode($this->properties);
    }

    /**
     *
     * @return string
     */
    public function __toString() : string
    {
        return $this->toString();
    }


    /**
     * Function used to compare to models
     *
     * @param self $other
     * @return boolean
     */
    public function equals(self $other): bool
    {
        return ($this->getId() === $other->getId() && $this->toString() === $other->toString());
    }

    /**
     * Check if model has any relations
     *
     * @return boolean
     */
    public function hasForeign($type) : bool
    {
        return $this->__meta['has_foreign'][$type];
    }

    /**
     * returns all relational models
     *
     * @return array
     */
    public function getForeignModels($type): array
    {
        return $this->__meta['foreign_models'][$type];
    }
}
