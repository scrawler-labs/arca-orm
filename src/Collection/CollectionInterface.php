<?php
namespace Scrawler\Arca\Collection;

use IteratorAggregate;
use Iterator;

interface CollectionInterface extends IteratorAggregate
{
    public function __toString(): string;
    public function toArray(): array;
    public function toString(): string;
    public function getIterator(): Iterator;
    public function apply(callable $callables): CollectionInterface;
    public function map(callable $callable): CollectionInterface;
    public static function fromIterable(): CollectionInterface;
}