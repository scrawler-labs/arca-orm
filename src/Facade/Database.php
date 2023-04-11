<?php
declare(strict_types=1);

namespace Scrawler\Arca\Facade;

use Scrawler\Arca\Manager\TableManager;
use Scrawler\Arca\Manager\RecordManager;
use Scrawler\Arca\Manager\ModelManager;
use Scrawler\Arca\Database as DB;

class Database
{
    private static $database;

    public static function connect(array $connectionParams)
    {

        if (self::$database == null) {
            $connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
            self::$database = new DB($connection);
            self::$database->setManagers(new TableManager(self::$database), new RecordManager(self::$database), new ModelManager(self::$database));
            return self::$database;
        }

        return self::$database;
    }

    private static function getDB()
    {
        return self::$database;
    }

    public static function create($name)
    {
        return self::getDB()->create($name);
    }

    public static function get($table, $id)
    {
        return self::getDB()->get($table, $id);
    }

    public static function getOne($table, $id)
    {
        return self::getDB()->getOne($table, $id);
    }

    public static function exec($sql)
    {
        return self::getDB()->exec($sql);
    }

    public static function delete($model){
        return self::getDB()->delete($model);
    }

    public static function find($table)
    {
        return self::getDB()->find($table);
    }


}