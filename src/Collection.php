<?php

namespace Scrawler\Arca;

use loophp\collection\Collection as LoopCollection;
use loophp\collection\Contract\Collection as LoopCollectionInterface;
use Iterator;
use Scrawler\Arca\Collection\CollectionInterface;

/**
 * Extension of LoopPHP collection 
 * @template TKey
 * @template T
 */
final class Collection implements CollectionInterface
{
    /**
     * @var LoopCollectionInterface<TKey, T>
     */
    private LoopCollectionInterface $collection;

    private function __construct(? LoopCollectionInterface $collection = null)
    {
        $this->collection = $collection ?? LoopCollection::empty();
    }

    /**
     /**
     * @template UKey
     * @template U
     *
     * @param iterable<UKey, U> $iterable
     * @return Collection
     */
    public static function fromIterable(iterable $iterable): self
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
     * @return array<mixed>
     */
    public function toArray(): array
    {
        $toArray = static fn($val) => $val->toArray();
        return $this->map($toArray)->jsonSerialize();
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return \Safe\json_encode($this->toArray());
    }

    /**
     * @param bool $normalize
     * @return array<mixed>
     */
    public function all(bool $normalize = false): array
    {
        return $this->collection->all($normalize);
    }

    /**
     * @param callable $callbacks
     * @return Collection
     */
    public function apply(callable $callbacks): Collection
    {
        return new self($this->collection->apply($callbacks));
    }

    /**
     * @param mixed[] $other
     * @return bool
     */
    public function equals(iterable $other): bool
    {
        return $this->collection->equals($other);
    }

    /**
     * 
     * @return Model|null
     */
    public function first(): Model|null
    {
       return $this->collection->first();
    }

    /**
     * @return Collection
     */
    public function init(): Collection
    {
        return new self($this->collection->init());
    }
    /**
     * @return Collection
     */
    public function inits(): Collection
    {
        return new self($this->collection->inits());
    }

    /**
     * @param array<mixed>|\Traversable<mixed> $sources
     * @return Collection
     */
    public function merge(iterable ...$sources): Collection
    {
        return new self($this->collection->merge(...$sources));
    }

    /**
     * @param callable $callback
     * @return Collection
     */
    public function map(callable $callback): Collection
    {
        return new self($this->collection->map($callback));
    }

    /**
     * @param callable(T, TKey, iterable<TKey, T>): bool ...$callbacks
     * @return Collection
     */
    public function filter(callable ...$callbacks): Collection
    {
        return new self($this->collection->filter(...$callbacks));
    }

    /**
     * 
     * @return int
     */
    public function count(): int
    {
        return iterator_count(iterator: $this);
    }

    /**
     * @param mixed $default
     * @param callable(T, TKey, iterable<TKey, T>): bool ...$callbacks
     * @return mixed
     */
    public function find($default = null, callable ...$callbacks)
    {
        return $this->collection->find($default, ...$callbacks);
    }

    /**
     * @param int $count
     * @param int $offset
     * @return Collection
     */
    public function limit(int $count = -1, int $offset = 0): Collection
    {
        return new self($this->collection->limit($count, $offset));
    }

    /**
     * 
     * @return Model|null
     * 
     */
    public function last(): Model|null
    {
        return $this->collection->last();
    }

    /**
     * 
     * @return Iterator
     */
    public function getIterator(): Iterator
    {
        yield from $this->collection->getIterator();
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