<?php

namespace Scrawler\Arca;

use Closure;
use Doctrine\Common\Collections\Criteria;
use loophp\collection\Collection as LoopCollection;
use loophp\collection\Contract\Collection as LoopCollectionInterface;
use Psr\Cache\CacheItemPoolInterface;
use Iterator;
use Scrawler\Arca\Collection\CollectionInterface;

use const INF;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

final class Collection implements CollectionInterface
{
    /**
     * @var CollectionInterface<TKey, T>
     */
    private CollectionInterface $loopcollection;

    private function __construct(?LoopCollectionInterface $collection = null)
    {
        $this->collection = $collection ?? LoopCollection::empty();
    }

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
        $toArray = static fn ($val) => $val->getProperties();
        return $this->map($toArray)->jsonSerialize();
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return json_encode($this->toArray());
    }

    public function all(bool $normalize = true): array
    {
        return $this->collection->all($normalize);
    }

    public function apply(callable ...$callbacks): CollectionInterface
    {
        return new self($this->collection->apply(...$callbacks));
    }
    
    public function equals(iterable $other): bool
    {
        return $this->collection->equals($other);
    }


    public function first(): CollectionInterface
    {
        return new self($this->collection->first());
    }

    public function init(): CollectionInterface
    {
        return new self($this->collection->init());
    }
    public function inits(): CollectionInterface
    {
        return new self($this->collection->inits());
    }

    public function merge(iterable ...$sources): CollectionInterface
    {
        return new self($this->collection->merge(...$sources));
    }

    public function map(callable $callback): CollectionInterface
    {
        return new self($this->collection->map($callback));
    }

    public function filter(callable ...$callbacks): CollectionInterface
    {
        return new self($this->collection->filter(...$callbacks));
    }

    public function count(): int
    {
        return $this->collection->count();
    }

    public function find($default = null, callable ...$callbacks)
    {
        return $this->collection->find($default, ...$callbacks);
    }

    public function limit(int $count = -1, int $offset = 0): CollectionInterface
    {
        return new self($this->collection->limit($count, $offset));
    }

    public function last(): CollectionInterface
    {
        return new self($this->collection->last());
    }

    public function getIterator(): Iterator
    {
        yield from $this->collection->getIterator();
    }

    public function current(int $index = 0, $default = null)
    {
        return $this->collection->current($index, $default);
    }

    public function key(int $index = 0)
    {
        return $this->collection->key($index);
    }
}
