<?php
namespace Scrawler\Arca\Collection;

use IteratorAggregate;
use Iterator;

/**
 * @template TKey
 * @template T
 */
interface CollectionInterface extends IteratorAggregate
{
    /**
     * @return string
     */
    public function __toString(): string;
    /**
     * @return array<mixed>
     */
    public function toArray(): array;
    /**
     * @return string
     */
    public function toString(): string;
    /**
     * @return Iterator
     */
    public function getIterator(): Iterator;
    /**
     * @param callable $callables
     */
    public function apply(callable $callables): CollectionInterface;
    /**
     * @param callable $callable
     */
    public function map(callable $callable): CollectionInterface;
    /**
     * @param mixed[] $array
     */
    public static function fromIterable(iterable $array): CollectionInterface;
    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array;
}