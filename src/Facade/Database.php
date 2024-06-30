<?php
declare(strict_types=1);

namespace Scrawler\Arca\Facade;


use Scrawler\Arca\Database as DB;
use Scrawler\Arca\Connection;

class Database
{
    private static $database;

    public static function connect(array $connectionParams)
    {

            $connection =new Connection($connectionParams);
            self::$database = new DB($connection);
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

    public static function get($table)
    {
        return self::getDB()->get($table);
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