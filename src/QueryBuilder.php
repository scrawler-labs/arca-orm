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
     * @var array<string>
     */
    private array $relations = [];

    private readonly AbstractSchemaManager $SchemaManager;

    public function __construct(
        Connection $connection,
        private readonly ModelManager $modelManager,
    ) {
        $this->SchemaManager = $connection->createSchemaManager();
        parent::__construct($connection);
    }

    public function with(string $relation): QueryBuilder
    {
        array_push($this->relations, $relation);

        return $this;
    }

    public function from(string $table, ?string $alias = null): QueryBuilder
    {
        $this->table = $table;

        return parent::from($table, $alias);
    }

    public function get(): Collection
    {
        if (!$this->SchemaManager->tableExists($this->table)) {
            return Collection::fromIterable([]);
        }
        $model = $this->modelManager->create($this->table);
        $relations = $this->relations;
        $this->relations = [];
        $results = $this->fetchAllAssociative();

        return Collection::fromIterable($results)
            ->map(static fn ($value): Model => $model->setLoadedProperties($value)->with($relations)->setLoaded());
    }

    public function first(): ?Model
    {
        if (!$this->SchemaManager->tableExists($this->table)) {
            return null;
        }
        $relations = $this->relations;
        $this->relations = [];
        $result = $this->fetchAssociative() ?: [];
        if (empty($result)) {
            return null;
        }

        return $this->modelManager->create($this->table)->setLoadedProperties($result)->with($relations)->setLoaded();
    }
}
