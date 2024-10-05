<?php

namespace Scrawler\Arca;
use Scrawler\Arca\Manager\ModelManager;

/**
 * Extended implementation of \Doctrine\DBAL\Query\QueryBuilder
 */
class QueryBuilder extends \Doctrine\DBAL\Query\QueryBuilder
{
    private string $table;
    private array $relations= [];
    private ModelManager $modelManager;

    public function __construct(\Doctrine\DBAL\Connection $connection,ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
        parent::__construct($connection);
    }

    public function with(string $relation): QueryBuilder
    {
        array_push($this->relations, $relation);
        return $this;
    }

    public function from($table, $alias = null): QueryBuilder
    {
        $this->table = $table;
        return parent::from($table,$alias);
    }

    public function get(): Collection|null
    {
        $model = $this->modelManager->create($this->table);
        $relations = $this->relations;
        $this->relations = [];
        $results = $this->fetchAllAssociative();
        if (empty($results)) {
            return null;
        }
        return Collection::fromIterable($results)
            ->map(static fn($value): Model => ($model)->setProperties($value)->with($relations)->setLoaded());
    }

    public function first(): Model|null
    {
        $relations = $this->relations;
        $this->relations = [];
        $result = $this->fetchAssociative() ? $this->fetchAssociative() : [];
        if (empty($result)) {
            return null;
        }
        return ($this->modelManager->create($this->table))->setProperties($result)->with($relations)->setLoaded();
    }
}