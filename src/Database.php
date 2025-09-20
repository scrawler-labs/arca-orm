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

namespace Scrawler\Arca;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Dunglas\DoctrineJsonOdm\Serializer;
use Dunglas\DoctrineJsonOdm\Type\JsonDocumentType;
use Scrawler\Arca\Manager\ModelManager;
use Scrawler\Arca\Manager\RecordManager;
use Scrawler\Arca\Manager\WriteManager;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;

/**
 * Class that manages all interaction with database.
 */
final class Database
{
    /**
     * Create a new Database instance.
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly RecordManager $recordManager,
        private readonly WriteManager $writeManager,
        private readonly ModelManager $modelManager,
        private readonly Config $config,
    ) {
        $this->registerJsonDocumentType();
    }

    /**
     * Executes an SQL query and returns the number of row affected.
     *
     * @param array<mixed> $params
     *
     * @return int|numeric-string
     */
    public function exec(string $sql, array $params = []): int|string
    {
        return $this->connection->executeStatement($sql, $params);
    }

    /**
     * Returns array of data from SQL select statement.
     *
     * @param array<mixed> $params
     *
     * @return array<int,array<string,mixed>>
     */
    public function getAll(string $sql, array $params = []): array
    {
        return $this->connection->executeQuery($sql, $params)->fetchAllAssociative();
    }

    /**
     * Creates model from name.
     */
    public function create(string $name): Model
    {
        return $this->modelManager->create($name);
    }

    /**
     * Save record to database.
     */
    public function save(Model $model): mixed
    {
        return $this->writeManager->save($model);
    }

    /**
     * Delete record from database.
     */
    public function delete(Model $model): mixed
    {
        return $this->recordManager->delete($model);
    }

    /**
     * Get collection of all records from table.
     */
    public function get(string $table): Collection
    {
        return $this->recordManager->getAll($table);
    }

    /**
     * Get single record.
     */
    public function getOne(string $table, mixed $id): ?Model
    {
        return $this->recordManager->getById($table, $id);
    }

    /**
     * Returns QueryBuilder to build query for finding data
     * Eg: db()->find('user')->where('active = 1')->get();.
     */
    public function find(string $name): QueryBuilder
    {
        return $this->recordManager->find($name);
    }

    /**
     * Returns QueryBuilder to build query for finding data
     * Eg: db()->select('*')->from('user')->where('active = 1')->get();.
     */
    public function select(string $expression): QueryBuilder
    {
        return $this->recordManager->select($expression);
    }

    /**
     * Freezes table for production.
     */
    public function freeze(): void
    {
        $this->config->setFrozen(true);
    }

    /**
     * Helper function to unfreeze table.
     */
    public function unfreeze(): void
    {
        $this->config->setFrozen(false);
    }

    /**
     * Checks if database is currently using uuid rather than id.
     */
    public function isUsingUUID(): bool
    {
        return $this->config->isUsingUUID();
    }

    /**
     * Returns the current connection.
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Check if tables exist.
     *
     * @param array<int,string> $tables
     */
    public function tablesExist(array $tables): bool
    {
        return $this->connection
            ->createSchemaManager()
            ->tablesExist($tables);
    }

    /**
     * Check if table exists.
     */
    public function tableExists(string $table): bool
    {
        return $this->connection
            ->createSchemaManager()
            ->tableExists($table);
    }

    /**
     * Register additional json_document type.
     * Note: storing and retrival of array is being tested
     * so ignoring this in coverage.
     */
    private function registerJsonDocumentType(): void
    {
        // @codeCoverageIgnoreStart
        if (!Type::hasType('json_document')) {
            Type::addType('json_document', JsonDocumentType::class);
            // @phpstan-ignore-next-line
            $jsonDocumentType = Type::getType('json_document');
            if ($jsonDocumentType instanceof JsonDocumentType) {
                $jsonDocumentType->setSerializer(
                    new Serializer([new BackedEnumNormalizer(), new UidNormalizer(), new DateTimeNormalizer(), new ArrayDenormalizer(), new ObjectNormalizer()], [new JsonEncoder()])
                );
            }
        }
        // @codeCoverageIgnoreEnd
    }
}
