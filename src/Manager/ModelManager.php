<?php

namespace Scrawler\Arca\Manager;

use \Scrawler\Arca\Model;

/**
 * Class for initializing and managing models
 */
class ModelManager {
    /**
     * Creates and return models

     */
    function create(string $name): Model
    {
        return new Model($name);
    }

}