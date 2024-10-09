<?php

namespace Scrawler\Arca;
use Scrawler\Arca\Manager\ModelManager;
use \Doctrine\DBAL\Schema\AbstractSchemaManager;
/**
 * Extended implementation of \Doctrine\DBAL\Query\QueryBuilder
 */
class QueryBuilder extends \Doctrine\DBAL\Query\QueryBuilder
{
    /**
     * @var string
     */
    private string $table;
    /**
     * @var array<string>
     */
    private array $relations= [];

    private AbstractSchemaManager $SchemaManager;
    private ModelManager $modelManager;

    public function __construct(\Doctrine\DBAL\Connection $connection,ModelManager $modelManager)
    {
        $this->modelManager = $modelManager;
        $this->SchemaManager = $connection->createSchemaManager();
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

    public function get(): Collection
    {
        if(!$this->SchemaManager->tableExists($this->table)){
            return Collection::fromIterable([]);
        }
        $model = $this->modelManager->create($this->table);
        $relations = $this->relations;
        $this->relations = [];
        $results = $this->fetchAllAssociative();

        return Collection::fromIterable($results)
            ->map(static fn($value): Model => ($model)->setLoadedProperties($value)->with($relations)->setLoaded());
    }

    public function first(): Model|null
    {
        if(!$this->SchemaManager->tableExists($this->table)){
            return null;
        }
        $relations = $this->relations;
        $this->relations = [];
        $result = $this->fetchAssociative() ? $this->fetchAssociative() : [];
        if (empty($result)) {
            return null;
        }
        return ($this->modelManager->create($this->table))->setLoadedProperties($result)->with($relations)->setLoaded();
    }
}