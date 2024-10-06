<?php
declare(strict_types=1);

namespace Scrawler\Arca\Facade;


use Scrawler\Arca\Database as DB;
use Scrawler\Arca\Connection;
use Scrawler\Arca\Collection;
use Scrawler\Arca\Model;
use Scrawler\Arca\QueryBuilder;

class Database
{
    /**
     * Store the instance of current connection
     * @var \Scrawler\Arca\Database
     */
    private static DB $database;

    /**
     * Create a new Database instance
     * @param array<mixed> $connectionParams
     * @return \Scrawler\Arca\Database
     */
    public static function connect(array $connectionParams): DB
    {

            $connection =new Connection($connectionParams);
            self::$database = new DB($connection);
            return self::$database;
     
    }

    /**
     * Get the instance of current connection
     * @return \Scrawler\Arca\Database
     */
    private static function getDB(): DB
    {
        return self::$database;
    }

    /**
     * Create a new model
     * @param string $name
     * @return \Scrawler\Arca\Model
     */
    public static function create(string $name): Model
    {
        return self::getDB()->create($name);
    }

    /**
     * Save a model
     * @param string $table
     * @return \Scrawler\Arca\Collection
     */
    public static function get($table): Collection
    {
        return self::getDB()->get($table);
    }

    /**
     * Save a model
     * @param string $table
     * @param mixed $id
     * @return \Scrawler\Arca\Model
     */
    public static function getOne(string $table, mixed $id): Model|null
    {
        return self::getDB()->getOne($table, $id);
    }

    /**
     * Execure a raw sql query
     * @param string $sql
     * @return int|numeric-string
     */
    public static function exec(string $sql): int|string
    {
        return self::getDB()->exec($sql);
    }

    /**
     * Delete a model
     * @param \Scrawler\Arca\Model $model
     * @return mixed
     */
    public static function delete(Model $model): mixed{
        return self::getDB()->delete($model);
    }

    /**
     * QUery builder to find a model 
     * @param string $table
     * @return \Scrawler\Arca\QueryBuilder
     */
    public static function find(string $table): QueryBuilder
    {
        return self::getDB()->find($table);
    }


}