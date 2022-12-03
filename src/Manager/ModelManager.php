<?php

namespace Scrawler\Arca\Manager;

use \Scrawler\Arca\Database;
use \Scrawler\Arca\Model;

/**
 * Class for initializing and managing models
 */
class ModelManager {
    private Database $db;

    /**
     * Create ModelManager
     * @param \Scrawler\Arca\Database $db
     */
    public function __construct(Database $db){
        $this->db = $db;
    }

    /**
     * Creates and return models
     * @param string $name
     * @return Model
     */
    public function create(string $name){
        return new Model($name,$this->db);
    }

}