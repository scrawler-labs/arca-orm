<?php 
declare(strict_types=1);

namespace Scrawler\Arca;
use \Doctrine\DBAL\Connection;
use Scrawler\Arca\Manager\ModelManager;
use Scrawler\Arca\Manager\RecordManager;
use Scrawler\Arca\Manager\TableManager;


class Managers{
    private static TableManager $tableManager;
    private static RecordManager $recordManager;

    private static ModelManager $modelManager;

    private function __construct()
    {
        
    }

    public static function create(Connection $connection, bool $isUsingUUID = false){
        self::$tableManager = new TableManager($connection,$isUsingUUID);
        self::$recordManager = new RecordManager($connection,$isUsingUUID);
        self::$modelManager = new ModelManager();
    }

    public static function tableManager(): TableManager
    {
        return self::$tableManager;
    }

    public static function recordManager() : RecordManager
    {
        return self::$recordManager;
    }

    public static function modelManager() : ModelManager
    {
        return self::$modelManager;
    }
}