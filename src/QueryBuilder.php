<?php

namespace Scrawler\Arca;

/**
 * Extended implementation of \Doctrine\DBAL\Query\QueryBuilder
 */
class QueryBuilder extends \Doctrine\DBAL\Query\QueryBuilder
{
    private string $table;
    private array $relations= [];

    public function __construct(\Doctrine\DBAL\Connection $connection)
    {
        parent::__construct($connection);
    }

    public function with(string $relation): QueryBuilder
    {
        array_push($this->relations, $relation);
        return $this;
    }

    public function from($from, $alias = null): QueryBuilder
    {
        $this->table = $from;
        return $this->add('from', [
            'table' => $from,
            'alias' => $alias,
        ], true);
    }

    public function get(): Collection
    {
        $model = Managers::modelManager()->create($this->table);
        $relations = $this->relations;
        $this->relations = [];
        return Collection::fromIterable($this->fetchAllAssociative())
            ->map(static fn($value): Model => ($model)->setProperties($value)->with($relations)->setLoaded());
    }

    public function first(): Model
    {
        $relations = $this->relations;
        $this->relations = [];
        $result = $this->fetchAssociative() ? $this->fetchAssociative() : [];
        return (Managers::modelManager()->create($this->table))->setProperties($result)->with($relations)->setLoaded();
    }
}