<?php

namespace Scrawler\Arca\Manager;

use \Scrawler\Arca\Database;
use \Scrawler\Arca\Model;

class ModelManager {
    private \Scrawler\Arca\Database $db;

    /**
     * Create RecordManager
     * @param \Scrawler\Arca\Database $db
     */
    public function __construct(Database $db){
        $this->db = $db
    }

    public function create(string $name){
        return new Model($name,$db);
    }

}