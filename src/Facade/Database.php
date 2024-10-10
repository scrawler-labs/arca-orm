<?php
/*
 * This file is part of the Scrawler package.
 *
 * (c) Pranjal Pandey <its.pranjalpandey@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Scrawler\Arca\Facade;

use Scrawler\Arca\Collection;
use Scrawler\Arca\Connection;
use Scrawler\Arca\Database as DB;
use Scrawler\Arca\Factory\DatabaseFactory;
use Scrawler\Arca\Model;
use Scrawler\Arca\QueryBuilder;

class Database
{
    /**
     * Store the instance of current connection.
     */
    private static DB $database;

    /**
     * Create a new Database instance.
     *
     * @param array<mixed> $connectionParams
     */
    public static function connect(array $connectionParams): DB
    {
        $factory = new DatabaseFactory();
        self::$database = $factory->build($connectionParams);

        return self::$database;
    }

    /**
     * Get the instance of current connection.
     */
    private static function getDB(): DB
    {
        return self::$database;
    }

    /**
     * Create a new model.
     */
    public static function create(string $name): Model
    {
        return self::getDB()->create($name);
    }

    /**
     * Save a model.
     *
     * @param string $table
     */
    public static function get($table): Collection
    {
        return self::getDB()->get($table);
    }

    /**
     * Save a model.
     */
    public static function getOne(string $table, mixed $id): ?Model
    {
        return self::getDB()->getOne($table, $id);
    }

    /**
     * Execure a raw sql query.
     *
     * @return int|numeric-string
     */
    public static function exec(string $sql): int|string
    {
        return self::getDB()->exec($sql);
    }

    /**
     * Delete a model.
     */
    public static function delete(Model $model): mixed
    {
        return self::getDB()->delete($model);
    }

    /**
     * QUery builder to find a model.
     */
    public static function find(string $table): QueryBuilder
    {
        return self::getDB()->find($table);
    }
}
