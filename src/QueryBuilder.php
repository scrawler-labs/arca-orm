<?php

namespace Scrawler\Arca;

class QueryBuilder extends \Doctrine\DBAL\Query\QueryBuilder
{
    private string $table;
    private Database $db;

    public function __construct(Database $db){
        $this->db = $db;
        parent::__construct($this->db->connection);

    }

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
        $query = $this->getSQL();
        $model = $this->db->create($table);
        return Collection::fromIterable($this->fetchAllAssociative())
        ->map(static fn ($value): Model => ($model)->setProperties($value)->setLoaded());
    }

    public function first() : Model
    {
        $table = $this->table;
        $result = $this->fetchAssociative() ? $this->fetchAssociative() : [];
        return ($this->db->create($table))->setProperties($result)->setLoaded();
    }
}
