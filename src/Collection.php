<?php

namespace Scrawler\Arca;

use loophp\collection\Collection as LoopCollection;
use loophp\collection\Contract\Collection as LoopCollectionInterface;
use Iterator;
use Scrawler\Arca\Collection\CollectionInterface;

/**
 * Extension of LoopPHP collection 
 */
final class Collection implements CollectionInterface
{
    /**
     * @var CollectionInterface<TKey, T>
     */
    private LoopCollectionInterface $collection;

    private function __construct(? LoopCollectionInterface $collection = null)
    {
        $this->collection = $collection ?? LoopCollection::empty();
    }

    /**
     * 
     * @param iterable|null $iterable
     * @return Collection
     */
    public static function fromIterable(?iterable $iterable = null): self
    {
        return new self(LoopCollection::fromIterable($iterable));
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->all(false);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $toArray = static fn($val) => $val->getProperties();
        return $this->map($toArray)->jsonSerialize();
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * @param bool $normalize
     * @return array
     */
    public function all(bool $normalize = true): array
    {
        return $this->collection->all($normalize);
    }

    /**
     * @param callable[] $callbacks
     * @return CollectionInterface
     */
    public function apply(callable ...$callbacks): CollectionInterface
    {
        return new self($this->collection->apply(...$callbacks));
    }

    /**
     * @param iterable $other
     * @return bool
     */
    public function equals(iterable $other): bool
    {
        return $this->collection->equals($other);
    }

    /**
     * 
     * @return CollectionInterface
     */
    public function first(): CollectionInterface
    {
        return new self($this->collection->first());
    }

    /**
     * @return CollectionInterface
     */
    public function init(): CollectionInterface
    {
        return new self($this->collection->init());
    }
    /**
     * @return CollectionInterface
     */
    public function inits(): CollectionInterface
    {
        return new self($this->collection->inits());
    }

    /**
     * @param iterable[] $sources
     * @return CollectionInterface
     */
    public function merge(iterable...$sources): CollectionInterface
    {
        return new self($this->collection->merge(...$sources));
    }

    /**
     * @param callable $callback
     * @return CollectionInterface
     */
    public function map(callable $callback): CollectionInterface
    {
        return new self($this->collection->map($callback));
    }

    /**
     * @param callable[] $callbacks
     * @return CollectionInterface
     */
    public function filter(callable ...$callbacks): CollectionInterface
    {
        return new self($this->collection->filter(...$callbacks));
    }

    /**
     * 
     * @return int
     */
    public function count(): int
    {
        return $this->collection->count();
    }

    /**
     * @param mixed $default
     * @param callable[] $callbacks
     * @return mixed
     */
    public function find($default = null, callable ...$callbacks)
    {
        return $this->collection->find($default, ...$callbacks);
    }

    /**
     * @param int $count
     * @param int $offset
     * @return CollectionInterface
     */
    public function limit(int $count = -1, int $offset = 0): CollectionInterface
    {
        return new self($this->collection->limit($count, $offset));
    }

    /**
     * 
     * @return CollectionInterface
     */
    public function last(): CollectionInterface
    {
        return new self($this->collection->last());
    }

    /**
     * 
     * @return Iterator
     */
    public function getIterator(): Iterator
    {
        yield from$this->collection->getIterator();
    }
    /**
     * @param int $index
     * @param mixed $default
     * @return mixed
     */
    public function current(int $index = 0, $default = null)
    {
        return $this->collection->current($index, $default);
    }

    /**
     * @param int $index
     * @return TKey|null
     */
    public function key(int $index = 0)
    {
        return $this->collection->key($index);
    }
}