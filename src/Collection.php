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

use loophp\collection\Collection as LoopCollection;
use loophp\collection\Contract\Collection as LoopCollectionInterface;
use Scrawler\Arca\Collection\CollectionInterface;

/**
 * Extension of LoopPHP collection.
 *
 * @template TKey
 * @template T
 */
final class Collection implements CollectionInterface
{
    /**
     * @var LoopCollectionInterface<TKey, T>
     */
    private readonly LoopCollectionInterface $collection;

    private function __construct(?LoopCollectionInterface $collection = null)
    {
        $this->collection = $collection ?? LoopCollection::empty();
    }

    /**
     * /**
     *
     * @template UKey
     * @template U
     *
     * @param iterable<UKey, U> $iterable
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

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        $toArray = static fn ($val) => $val->toArray();

        return $this->map($toArray)->jsonSerialize();
    }

    public function toString(): string
    {
        return \Safe\json_encode($this->toArray());
    }

    /**
     * @return array<mixed>
     */
    public function all(bool $normalize = false): array
    {
        return $this->collection->all($normalize);
    }

    public function apply(callable $callbacks): Collection
    {
        return new self($this->collection->apply($callbacks));
    }

    /**
     * @param mixed[] $other
     */
    public function equals(iterable $other): bool
    {
        return $this->collection->equals($other);
    }

    public function first(): ?Model
    {
        return $this->collection->first();
    }

    public function init(): Collection
    {
        return new self($this->collection->init());
    }

    public function inits(): Collection
    {
        return new self($this->collection->inits());
    }

    /**
     * @param array<mixed>|\Traversable<mixed> $sources
     */
    public function merge(iterable ...$sources): Collection
    {
        return new self($this->collection->merge(...$sources));
    }

    public function map(callable $callback): Collection
    {
        return new self($this->collection->map($callback));
    }

    /**
     * @param callable(T, TKey, iterable<TKey, T>): bool ...$callbacks
     */
    public function filter(callable ...$callbacks): Collection
    {
        return new self($this->collection->filter(...$callbacks));
    }

    public function count(): int
    {
        return iterator_count(iterator: $this);
    }

    /**
     * @param callable(T, TKey, iterable<TKey, T>): bool ...$callbacks
     */
    public function find(mixed $default = null, callable ...$callbacks): mixed
    {
        return $this->collection->find($default, ...$callbacks);
    }

    public function limit(int $count = -1, int $offset = 0): Collection
    {
        return new self($this->collection->limit($count, $offset));
    }

    public function last(): ?Model
    {
        return $this->collection->last();
    }

    public function getIterator(): \Iterator
    {
        yield from $this->collection->getIterator();
    }

    public function current(int $index = 0, mixed $default = null): mixed
    {
        return $this->collection->current($index, $default);
    }

    /**
     * @return TKey|null
     */
    public function key(int $index = 0)
    {
        return $this->collection->key($index);
    }
}
