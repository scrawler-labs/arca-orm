<?php
use Scrawler\Arca\Factory\DatabaseFactory;
class TestDB
{

    private static $idinstance = null;
    private static $uuidinstance = null;


    public static function getIdInstance()
    {
        if (self::$idinstance === null) {
            try {
                $connectionParams = self::getConnectionParams();
                $connectionParams['useUUID'] = false;
                $factory = new DatabaseFactory();
                self::$idinstance = $factory->build($connectionParams);
            } catch (Exception $e) {
                throw new RuntimeException(
                    "Failed to create ID database instance: " . $e->getMessage() . 
                    "\nCurrent DB_CONNECTION: " . ($_ENV['DB_CONNECTION'] ?? 'not set') .
                    "\nSuggestion: Set DB_CONNECTION=sqlite for local testing"
                );
            }
        }

        return self::$idinstance;
    }

     public static function getUuidInstance()
    {
        if (self::$uuidinstance === null) {
            try {
                $connectionParams = self::getConnectionParams();
                $connectionParams['useUUID'] = true;
                $factory = new DatabaseFactory();
                self::$uuidinstance = $factory->build($connectionParams);
            } catch (Exception $e) {
                throw new RuntimeException(
                    "Failed to create UUID database instance: " . $e->getMessage() . 
                    "\nCurrent DB_CONNECTION: " . ($_ENV['DB_CONNECTION'] ?? 'not set') .
                    "\nSuggestion: Set DB_CONNECTION=sqlite for local testing"
                );
            }
        }

        return self::$uuidinstance;
    }



    private static function getConnectionParams($uuid = 'ID', $withUUID = true): array
    {
        $dbConnection = $_ENV['DB_CONNECTION'] ?? 'sqlite';

        $config = match ($dbConnection) {
            'mysql' => [
                'dbname' => $_ENV['DB_DATABASE'] ?? 'test',
                'user' => $_ENV['DB_USERNAME'] ?? 'arca_user',
                'password' => $_ENV['DB_PASSWORD'] ?? 'arca_pass',
                'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
                'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
                'driver' => 'pdo_mysql',
            ],
            'pgsql' => [
                'dbname' => $_ENV['DB_DATABASE'] ?? 'test',
                'user' => $_ENV['DB_USERNAME'] ?? 'arca_user',
                'password' => $_ENV['DB_PASSWORD'] ?? 'arca_pass',
                'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
                'port' => (int) ($_ENV['DB_PORT'] ?? 5432),
                'driver' => 'pdo_pgsql',
            ],
            'sqlite' => [
                'path' => $_ENV['DB_DATABASE'] ?? ':memory:',
                'driver' => 'pdo_sqlite',
            ],
            default => throw new InvalidArgumentException("Unsupported database connection: {$dbConnection}")
        };

        return $config;
    }


}