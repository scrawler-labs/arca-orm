<?php
declare(strict_types=1);

namespace Scrawler\Arca\Facade;

use Scrawler\Arca\Manager\TableManager;
use Scrawler\Arca\Manager\RecordManager;
use Scrawler\Arca\Manager\ModelManager;
use Scrawler\Arca\Database as DB;

class Database {
    private static $database;

    public static function connect(array $connectionParams){
        
        if(self::$database == null)
        {
            $connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
            self::$database = new DB($connection);
            self::$database->setManagers(new TableManager(self::$database),new RecordManager(self::$database),new ModelManager(self::$database));
            return self::$database;
        }

            return self::$database;
    }
}