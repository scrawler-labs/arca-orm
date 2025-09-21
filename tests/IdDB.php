<?php

class IdDB
{

    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance === null) {
            $connectionParams = self::getConnectionParams();
            self::$instance = \Scrawler\Arca\Facade\Database::connect($connectionParams);
        }

        return self::$instance;

    }

    private static function getConnectionParams($uuid = 'ID', $withUUID = true): array
    {
        $dbConnection = $_ENV['DB_CONNECTION'] ?? 'mysql';

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

       
        $config['useUUID'] = false;

        return $config;
    }


}