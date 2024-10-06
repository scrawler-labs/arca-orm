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
     * @var Connection
     */
    private Connection $connection;

    /**
     * Create a new model
     * @param string $name
     * @return \Scrawler\Arca\Model
     */
    function create(string $name): Model
    {
        return new Model($name,$this->connection);
    }

    /**
     * Set the connection
     * @param Connection $connection
     */
    function setConnection(Connection $connection): void
    {
       $this->connection = $connection;   
    }

}