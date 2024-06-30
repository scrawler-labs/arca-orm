<?php
declare(strict_types=1);

namespace Scrawler\Arca\Manager;

use \Scrawler\Arca\Model;
use \Scrawler\Arca\Connection;

/**
 * Class for initializing and managing models
 */
class ModelManager {
    /**
     * Creates and return models
     */
    private $connection;
    function create(string $name): Model
    {
        return new Model($name,$this->connection);
    }

    function setConnection(Connection $connection)
    {
       $this->connection = $connection;   
    }

}