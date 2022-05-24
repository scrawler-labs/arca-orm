<?php

namespace Scrawler\Arca;

class QueryBuilder extends \Doctrine\DBAL\Query\QueryBuilder
{
    private string $table;


    public function from($from, $alias = null) : QueryBuilder
    {
        $this->table = $from;
        return $this->add('from', [
            'table' => $from,
            'alias' => $alias,
        ], true);
    }


    public function get() : Collection
    {
        $table = $this->table;
        return Collection::fromIterable($this->fetchAllAssociative())
        ->map(static fn ($value): Model => (Database::getInstance()->create($table))->setProperties($value)->setLoaded());
    }

    public function first() : Model
    {
        $table = $this->table;
        $result = $this->fetchAssociative() ? $this->fetchAssociative() : []
        return (Database::getInstance()->create($table))->setProperties($result)->setLoaded();
    }
}
