<?php

/*
 * This file is part of the Scrawler package.
 *
 * (c) Pranjal Pandey <its.pranjalpandey@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scrawler\Arca;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Scrawler\Arca\Manager\ModelManager;

/**
 * Extended implementation of \Doctrine\DBAL\Query\QueryBuilder.
 */
final class QueryBuilder extends DoctrineQueryBuilder
{
    private string $table;
    /**
     * @var AbstractSchemaManager
     */
    private readonly AbstractSchemaManager $schemaManager;

    public function __construct(
        Connection $connection,
        private readonly ModelManager $modelManager,
    ) {
        $this->schemaManager = $connection->createSchemaManager();
        parent::__construct($connection);
    }

    public function from(string $table, ?string $alias = null): QueryBuilder
    {
        $this->table = $table;

        return parent::from($table, $alias);
    }

    public function get(): Collection
    {
        if (!$this->schemaManager->tableExists($this->table)) {
            return Collection::fromIterable([]);
        }
        $model = $this->modelManager->create($this->table);
        $results = $this->fetchAllAssociative();

        return Collection::fromIterable($results)
            ->map(static fn ($value): Model => $model->setLoadedProperties($value)->setLoaded());
    }

    public function first(): ?Model
    {
        if (!$this->schemaManager->tableExists($this->table)) {
            return null;
        }

        $result = $this->fetchAssociative() ?: [];
        if ([] === $result) {
            return null;
        }

        return $this->modelManager->create($this->table)->setLoadedProperties($result)->setLoaded();
    }
}
