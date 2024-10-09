<?php
/*
 * This file is part of the Scrawler package.
 *
 * (c) Pranjal Pandey <its.pranjalpandey@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Scrawler\Arca\Collection;

/**
 * @template TKey
 * @template T
 */
interface CollectionInterface extends \IteratorAggregate, \Countable
{
    public function __toString(): string;

    /**
     * @return array<mixed>
     */
    public function toArray(): array;

    public function toString(): string;

    public function getIterator(): \Iterator;

    public function apply(callable $callables): CollectionInterface;

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
