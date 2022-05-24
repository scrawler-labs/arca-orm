<?php

namespace Scrawler\Arca;

use Closure;
use Doctrine\Common\Collections\Criteria;
use Generator;
use Iterator;
use loophp\collection\Contract\Operation;
use loophp\collection\Iterator\ArrayCacheIterator;
use loophp\collection\Iterator\ClosureIterator;
use loophp\collection\Iterator\IterableIterator;
use loophp\collection\Iterator\ResourceIterator;
use loophp\collection\Iterator\StringIterator;
use loophp\collection\Operation\All;
use loophp\collection\Operation\Append;
use loophp\collection\Operation\Apply;
use loophp\collection\Operation\Associate;
use loophp\collection\Operation\Cache;
use loophp\collection\Operation\Chunk;
use loophp\collection\Operation\Coalesce;
use loophp\collection\Operation\Collapse;
use loophp\collection\Operation\Column;
use loophp\collection\Operation\Combinate;
use loophp\collection\Operation\Combine;
use loophp\collection\Operation\Compact;
use loophp\collection\Operation\Contains;
use loophp\collection\Operation\Current;
use loophp\collection\Operation\Cycle;
use loophp\collection\Operation\Diff;
use loophp\collection\Operation\DiffKeys;
use loophp\collection\Operation\Distinct;
use loophp\collection\Operation\Drop;
use loophp\collection\Operation\DropWhile;
use loophp\collection\Operation\Dump;
use loophp\collection\Operation\Duplicate;
use loophp\collection\Operation\Equals;
use loophp\collection\Operation\Every;
use loophp\collection\Operation\Explode;
use loophp\collection\Operation\Falsy;
use loophp\collection\Operation\Filter;
use loophp\collection\Operation\Find;
use loophp\collection\Operation\First;
use loophp\collection\Operation\FlatMap;
use loophp\collection\Operation\Flatten;
use loophp\collection\Operation\Flip;
use loophp\collection\Operation\FoldLeft;
use loophp\collection\Operation\FoldLeft1;
use loophp\collection\Operation\FoldRight;
use loophp\collection\Operation\FoldRight1;
use loophp\collection\Operation\Forget;
use loophp\collection\Operation\Frequency;
use loophp\collection\Operation\Get;
use loophp\collection\Operation\Group;
use loophp\collection\Operation\GroupBy;
use loophp\collection\Operation\Has;
use loophp\collection\Operation\Head;
use loophp\collection\Operation\IfThenElse;
use loophp\collection\Operation\Implode;
use loophp\collection\Operation\Init;
use loophp\collection\Operation\Inits;
use loophp\collection\Operation\Intersect;
use loophp\collection\Operation\IntersectKeys;
use loophp\collection\Operation\Intersperse;
use loophp\collection\Operation\IsEmpty;
use loophp\collection\Operation\Key;
use loophp\collection\Operation\Keys;
use loophp\collection\Operation\Last;
use loophp\collection\Operation\Limit;
use loophp\collection\Operation\Lines;
use loophp\collection\Operation\Map;
use loophp\collection\Operation\MapN;
use loophp\collection\Operation\Matching;
use loophp\collection\Operation\MatchOne;
use loophp\collection\Operation\Merge;
use loophp\collection\Operation\Normalize;
use loophp\collection\Operation\Nth;
use loophp\collection\Operation\Nullsy;
use loophp\collection\Operation\Pack;
use loophp\collection\Operation\Pad;
use loophp\collection\Operation\Pair;
use loophp\collection\Operation\Partition;
use loophp\collection\Operation\Permutate;
use loophp\collection\Operation\Pipe;
use loophp\collection\Operation\Pluck;
use loophp\collection\Operation\Prepend;
use loophp\collection\Operation\Product;
use loophp\collection\Operation\Random;
use loophp\collection\Operation\Range;
use loophp\collection\Operation\Reduce;
use loophp\collection\Operation\Reduction;
use loophp\collection\Operation\Reject;
use loophp\collection\Operation\Reverse;
use loophp\collection\Operation\RSample;
use loophp\collection\Operation\Same;
use loophp\collection\Operation\Scale;
use loophp\collection\Operation\ScanLeft;
use loophp\collection\Operation\ScanLeft1;
use loophp\collection\Operation\ScanRight;
use loophp\collection\Operation\ScanRight1;
use loophp\collection\Operation\Shuffle;
use loophp\collection\Operation\Since;
use loophp\collection\Operation\Slice;
use loophp\collection\Operation\Sort;
use loophp\collection\Operation\Span;
use loophp\collection\Operation\Split;
use loophp\collection\Operation\Strict;
use loophp\collection\Operation\Tail;
use loophp\collection\Operation\Tails;
use loophp\collection\Operation\TakeWhile;
use loophp\collection\Operation\Times;
use loophp\collection\Operation\Transpose;
use loophp\collection\Operation\Truthy;
use loophp\collection\Operation\Unfold;
use loophp\collection\Operation\Unlines;
use loophp\collection\Operation\Unpack;
use loophp\collection\Operation\Unpair;
use loophp\collection\Operation\Until;
use loophp\collection\Operation\Unwindow;
use loophp\collection\Operation\Unwords;
use loophp\collection\Operation\Unwrap;
use loophp\collection\Operation\Unzip;
use loophp\collection\Operation\When;
use loophp\collection\Operation\Window;
use loophp\collection\Operation\Words;
use loophp\collection\Operation\Wrap;
use loophp\collection\Operation\Zip;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Scrawler\Arca\Collection\CollectionInterface;
use const INF;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * @immutable
 *
 * @template TKey
 * @template T
 *
 * phpcs:disable Generic.Files.LineLength.TooLong
 *
 * @implements \loophp\collection\Contract\Collection<TKey, T>
 */
final class Collection implements CollectionInterface
{
    /**
     * @var iterable<int, mixed>
     */
    private iterable $parameters;

    /**
     * @var callable(mixed ...$parameters): iterable<TKey, T>
     */
    private $source;

    /**
     * @psalm-external-mutation-free
     *
     * @param callable(mixed ...$parameters): iterable<TKey, T> $callable
     * @param iterable<int, mixed> $parameters
     */
    private function __construct(callable $callable, iterable $parameters = [])
    {
        $this->source = $callable;
        $this->parameters = $parameters;
    }

    public function all(bool $normalize = true): array
    {
        return iterator_to_array(All::of()($normalize)($this->getIterator()));
    }

    public function append(...$items): CollectionInterface
    {
        return new self(Append::of()(...$items), [$this->getIterator()]);
    }

    public function apply(callable ...$callbacks): CollectionInterface
    {
        return new self(Apply::of()(...$callbacks), [$this->getIterator()]);
    }

    public function associate(
        ?callable $callbackForKeys = null,
        ?callable $callbackForValues = null
    ): CollectionInterface {
        $defaultCallback =
            /**
             * @param mixed $carry
             *
             * @return mixed
             */
            static fn ($carry) => $carry;

        return new self(Associate::of()($callbackForKeys ?? $defaultCallback)($callbackForValues ?? $defaultCallback), [$this->getIterator()]);
    }

    public function asyncMap(callable $callback): CollectionInterface
    {
        return new self(Apply::of()($callback), [$this->getIterator()]);
    }

    public function asyncMapN(callable ...$callbacks): CollectionInterface
    {
        return new self(Apply::of()(...$callbacks), [$this->getIterator()]);
    }

    public function cache(?CacheItemPoolInterface $cache = null): CollectionInterface
    {
        return iterator_to_array(All::of()(false)($this->getIterator()));
    }

    public function chunk(int ...$sizes): CollectionInterface
    {
        return new self(Chunk::of()(...$sizes), [$this->getIterator()]);
    }

    public function coalesce(): CollectionInterface
    {
        return new self(Coalesce::of(), [$this->getIterator()]);
    }

    public function collapse(): CollectionInterface
    {
        return new self(Collapse::of(), [$this->getIterator()]);
    }

    public function column($column): CollectionInterface
    {
        return new self(Column::of()($column), [$this->getIterator()]);
    }

    public function combinate(?int $length = null): CollectionInterface
    {
        return new self(Combinate::of()($length), [$this->getIterator()]);
    }

    public function combine(...$keys): CollectionInterface
    {
        return new self(Combine::of()(...$keys), [$this->getIterator()]);
    }

    public function compact(...$values): CollectionInterface
    {
        return new self(Compact::of()(...$values), [$this->getIterator()]);
    }

    public function contains(...$values): bool
    {
        return (new self(Contains::of()(...$values), [$this->getIterator()]))->getIterator()->current();
    }

    public function count(): int
    {
        return iterator_count($this->getIterator());
    }

    public function current(int $index = 0, $default = null)
    {
        return (new self(Current::of()($index)($default), [$this->getIterator()]))->getIterator()->current();
    }

    public function cycle(): CollectionInterface
    {
        return new self(Cycle::of(), [$this->getIterator()]);
    }

    public function diff(...$values): CollectionInterface
    {
        return new self(Diff::of()(...$values), [$this->getIterator()]);
    }

    public function diffKeys(...$keys): CollectionInterface
    {
        return new self(DiffKeys::of()(...$keys), [$this->getIterator()]);
    }

    public function distinct(?callable $comparatorCallback = null, ?callable $accessorCallback = null): CollectionInterface
    {
        $accessorCallback ??=
            /**
             * @param T $value
             * @param TKey $key
             *
             * @return T
             */
            static fn ($value, $key) => $value;

        $comparatorCallback ??=
            /**
             * @param T $left
             *
             * @return Closure(T): bool
             */
            static fn ($left): Closure =>
                /**
                 * @param T $right
                 */
                static fn ($right): bool => $left === $right;

        return new self(Distinct::of()($comparatorCallback)($accessorCallback), [$this->getIterator()]);
    }

    public function drop(int $count): CollectionInterface
    {
        return new self(Drop::of()($count), [$this->getIterator()]);
    }

    public function dropWhile(callable ...$callbacks): CollectionInterface
    {
        return new self(DropWhile::of()(...$callbacks), [$this->getIterator()]);
    }

    public function dump(string $name = '', int $size = 1, ?Closure $closure = null): CollectionInterface
    {
        return new self(Dump::of()($name)($size)($closure), [$this->getIterator()]);
    }

    public function duplicate(?callable $comparatorCallback = null, ?callable $accessorCallback = null): CollectionInterface
    {
        $accessorCallback ??=
            /**
             * @param T $value
             * @param TKey $key
             *
             * @return T
             */
            static fn ($value, $key) => $value;

        $comparatorCallback ??=
            /**
             * @param T $left
             *
             * @return Closure(T): bool
             */
            static fn ($left): Closure =>
                /**
                 * @param T $right
                 */
                static fn ($right): bool => $left === $right;

        return new self(Duplicate::of()($comparatorCallback)($accessorCallback), [$this->getIterator()]);
    }

    /**
     * @pure
     *
     * @return self<mixed, mixed>
     */
    public static function empty(): CollectionInterface
    {
        /** @var array<mixed, mixed> $emptyArray */
        $emptyArray = [];

        return self::fromIterable($emptyArray);
    }

    public function equals(\loophp\collection\Contract\Collection $other): bool
    {
        return (new self(Equals::of()($other->getIterator()), [$this->getIterator()]))->getIterator()->current();
    }

    public function every(callable ...$callbacks): bool
    {
        return (new self(Every::of()(static fn (): bool => false)(...$callbacks), [$this->getIterator()]))->getIterator()->current();
    }

    public function explode(...$explodes): CollectionInterface
    {
        return new self(Explode::of()(...$explodes), [$this->getIterator()]);
    }

    public function falsy(): bool
    {
        return (new self(Falsy::of(), [$this->getIterator()]))->getIterator()->current();
    }

    public function filter(callable ...$callbacks): CollectionInterface
    {
        return new self(Filter::of()(...$callbacks), [$this->getIterator()]);
    }

    public function find($default = null, callable ...$callbacks)
    {
        return (new self(Find::of()($default)(...$callbacks), [$this->getIterator()]))->getIterator()->current();
    }

    public function first(): CollectionInterface
    {
        return new self(First::of(), [$this->getIterator()]);
    }

    public function flatMap(callable $callback): CollectionInterface
    {
        return new self(FlatMap::of()($callback), [$this->getIterator()]);
    }

    public function flatten(int $depth = PHP_INT_MAX): CollectionInterface
    {
        return new self(Flatten::of()($depth), [$this->getIterator()]);
    }

    public function flip(): CollectionInterface
    {
        return new self(Flip::of(), [$this->getIterator()]);
    }

    public function foldLeft(callable $callback, $initial = null): CollectionInterface
    {
        return new self(FoldLeft::of()($callback)($initial), [$this->getIterator()]);
    }

    public function foldLeft1(callable $callback): CollectionInterface
    {
        return new self(FoldLeft1::of()($callback), [$this->getIterator()]);
    }

    public function foldRight(callable $callback, $initial = null): CollectionInterface
    {
        return new self(Foldright::of()($callback)($initial), [$this->getIterator()]);
    }

    public function foldRight1(callable $callback): CollectionInterface
    {
        return new self(FoldRight1::of()($callback), [$this->getIterator()]);
    }

    public function forget(...$keys): CollectionInterface
    {
        return new self(Forget::of()(...$keys), [$this->getIterator()]);
    }

    public function frequency(): CollectionInterface
    {
        return new self(Frequency::of(), [$this->getIterator()]);
    }

    /**
     * @pure
     * @template NewTKey
     * @template NewT
     *
     * @param callable(mixed ...$parameters): iterable<NewTKey, NewT> $callable
     * @param iterable<int, mixed> $parameters
     *
     * @return self<NewTKey, NewT>
     */
    public static function fromCallable(callable $callable, iterable $parameters = []): self
    {
        return new self($callable, $parameters);
    }

    /**
     * @pure
     *
     * @return self<int, string>
     */
    public static function fromFile(string $filepath): self
    {
        return new self(
            static fn (): Iterator => new ResourceIterator(fopen($filepath, 'rb'), true),
        );
    }

    /**
     * @template NewTKey
     * @template NewT
     *
     * @param Generator<NewTKey, NewT> $generator
     *
     * @return self<NewTKey, NewT>
     */
    public static function fromGenerator(Generator $generator): self
    {
        return self::fromIterable(
            new ArrayCacheIterator(
                new ClosureIterator(
                    static function (Generator $generator): Generator {
                        while ($generator->valid()) {
                            yield $generator->key() => $generator->current();
                            $generator->next();
                        }
                    },
                    [$generator]
                )
            )
        );
    }

    /**
     * @pure
     *
     * @template NewTKey
     * @template NewT
     *
     * @param iterable<NewTKey, NewT> $iterable
     *
     * @return self<NewTKey, NewT>
     */
    public static function fromIterable(iterable $iterable): self
    {
        return new self(static fn (): Iterator => new IterableIterator($iterable));
    }

    /**
     * @pure
     *
     * @param resource $resource
     *
     * @return self<int, string>
     */
    public static function fromResource($resource): self
    {
        return new self(static fn (): Iterator => new ResourceIterator($resource));
    }

    /**
     * @pure
     *
     * @return self<int, string>
     */
    public static function fromString(string $string, string $delimiter = ''): self
    {
        return new self(static fn (): Iterator => new StringIterator($string, $delimiter));
    }

    public function get($key, $default = null): CollectionInterface
    {
        return new self(Get::of()($key)($default), [$this->getIterator()]);
    }

    /**
     * @return Iterator<TKey, T>
     */
    public function getIterator(): Iterator
    {
        return new ClosureIterator($this->source, $this->parameters);
    }

    public function group(): CollectionInterface
    {
        return new self(Group::of(), [$this->getIterator()]);
    }

    public function groupBy(callable $callable): CollectionInterface
    {
        return new self(GroupBy::of()($callable), [$this->getIterator()]);
    }

    public function has(callable ...$callbacks): bool
    {
        return (new self(Has::of()(...$callbacks), [$this->getIterator()]))->getIterator()->current();
    }

    public function head(): CollectionInterface
    {
        return new self(Head::of(), [$this->getIterator()]);
    }

    public function ifThenElse(callable $condition, callable $then, ?callable $else = null): CollectionInterface
    {
        $identity =
            /**
             * @param T $value
             *
             * @return T
             */
            static fn ($value) => $value;

        return new self(IfThenElse::of()($condition)($then)($else ?? $identity), [$this->getIterator()]);
    }

    public function implode(string $glue = ''): CollectionInterface
    {
        return new self(Implode::of()($glue), [$this->getIterator()]);
    }

    public function init(): CollectionInterface
    {
        return new self(Init::of(), [$this->getIterator()]);
    }

    public function inits(): CollectionInterface
    {
        return new self(Inits::of(), [$this->getIterator()]);
    }

    public function intersect(...$values): CollectionInterface
    {
        return new self(Intersect::of()(...$values), [$this->getIterator()]);
    }

    public function intersectKeys(...$keys): CollectionInterface
    {
        return new self(IntersectKeys::of()(...$keys), [$this->getIterator()]);
    }

    public function intersperse($element, int $every = 1, int $startAt = 0): CollectionInterface
    {
        return new self(Intersperse::of()($element)($every)($startAt), [$this->getIterator()]);
    }

    public function isEmpty(): bool
    {
        return (new self(IsEmpty::of(), [$this->getIterator()]))->getIterator()->current();
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->all(false);
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

    public function key(int $index = 0)
    {
        return (new self(Key::of()($index), [$this->getIterator()]))->getIterator()->current();
    }

    public function keys(): CollectionInterface
    {
        return new self(Keys::of(), [$this->getIterator()]);
    }

    public function last(): CollectionInterface
    {
        return new self(Last::of(), [$this->getIterator()]);
    }

    public function limit(int $count = -1, int $offset = 0): CollectionInterface
    {
        return new self(Limit::of()($count)($offset), [$this->getIterator()]);
    }

    public function lines(): CollectionInterface
    {
        return new self(Lines::of(), [$this->getIterator()]);
    }

    public function map(callable $callback): CollectionInterface
    {
        return new self(Map::of()($callback), [$this->getIterator()]);
    }

    public function mapN(callable ...$callbacks): CollectionInterface
    {
        return new self(MapN::of()(...$callbacks), [$this->getIterator()]);
    }

    public function match(callable $callback, ?callable $matcher = null): bool
    {
        return (new self(MatchOne::of()($matcher ?? static fn (): bool => true)($callback), [$this->getIterator()]))
            ->getIterator()
            ->current();
    }

    public function matching(Criteria $criteria): CollectionInterface
    {
        return new self(Matching::of()($criteria), [$this->getIterator()]);
    }

    public function merge(iterable ...$sources): CollectionInterface
    {
        return new self(Merge::of()(...$sources), [$this->getIterator()]);
    }

    public function normalize(): CollectionInterface
    {
        return new self(Normalize::of(), [$this->getIterator()]);
    }

    public function nth(int $step, int $offset = 0): CollectionInterface
    {
        return new self(Nth::of()($step)($offset), [$this->getIterator()]);
    }

    public function nullsy(): bool
    {
        return (new self(Nullsy::of(), [$this->getIterator()]))->getIterator()->current();
    }

    public function pack(): CollectionInterface
    {
        return new self(Pack::of(), [$this->getIterator()]);
    }

    public function pad(int $size, $value): CollectionInterface
    {
        return new self(Pad::of()($size)($value), [$this->getIterator()]);
    }

    public function pair(): CollectionInterface
    {
        return new self(Pair::of(), [$this->getIterator()]);
    }

    public function partition(callable ...$callbacks): CollectionInterface
    {
        // TODO: Move this docblock above closure when https://github.com/phpstan/phpstan/issues/3770 lands.
        $mapCallback =
            /**
             * @param array{0: (Closure(Iterator<TKey, T>): Generator<TKey, T>), 1: (array{0: Iterator<TKey, T>})} $partitionResult
             */
            static function (array $partitionResult): CollectionInterface {
                /**
                 * @var Closure(Iterator<TKey, T>): Generator<TKey, T> $callback
                 * @var array{0: Iterator<TKey, T>} $parameters
                 */
                [$callback, $parameters] = $partitionResult;

                return self::fromCallable($callback, $parameters);
            };

        return new self(Pipe::of()(Partition::of()(...$callbacks), Map::of()($mapCallback)), [$this->getIterator()]);
    }

    public function permutate(): CollectionInterface
    {
        return new self(Permutate::of(), [$this->getIterator()]);
    }

    public function pipe(callable ...$callbacks): CollectionInterface
    {
        return new self(Pipe::of()(...$callbacks), [$this->getIterator()]);
    }

    public function pluck($pluck, $default = null): CollectionInterface
    {
        return new self(Pluck::of()($pluck)($default), [$this->getIterator()]);
    }

    public function prepend(...$items): CollectionInterface
    {
        return new self(Prepend::of()(...$items), [$this->getIterator()]);
    }

    public function product(iterable ...$iterables): CollectionInterface
    {
        return new self(Product::of()(...$iterables), [$this->getIterator()]);
    }

    public function random(int $size = 1, ?int $seed = null): CollectionInterface
    {
        return new self(Random::of()($seed ?? random_int(PHP_INT_MIN, PHP_INT_MAX))($size), [$this->getIterator()]);
    }

    public static function range(float $start = 0.0, float $end = INF, float $step = 1.0): CollectionInterface
    {
        return self::empty()->pipe(Range::of()($start)($end)($step));
    }

    public function reduce(callable $callback, $initial = null): CollectionInterface
    {
        return new self(Reduce::of()($callback)($initial), [$this->getIterator()]);
    }

    public function reduction(callable $callback, $initial = null): CollectionInterface
    {
        return new self(Reduction::of()($callback)($initial), [$this->getIterator()]);
    }

    public function reject(callable ...$callbacks): CollectionInterface
    {
        return new self(Reject::of()(...$callbacks), [$this->getIterator()]);
    }

    public function reverse(): CollectionInterface
    {
        return new self(Reverse::of(), [$this->getIterator()]);
    }

    public function rsample(float $probability): CollectionInterface
    {
        return new self(RSample::of()($probability), [$this->getIterator()]);
    }

    public function same(\loophp\collection\Contract\Collection $other, ?callable $comparatorCallback = null): bool
    {
        $comparatorCallback ??=
            /**
             * @param T $leftValue
             * @param TKey $leftKey
             *
             * @return Closure(T, TKey): bool
             */
            static fn ($leftValue, $leftKey): Closure =>
                /**
                 * @param T $rightValue
                 * @param TKey $rightKey
                 */
                static fn ($rightValue, $rightKey): bool => $leftValue === $rightValue && $leftKey === $rightKey;

        return (new self(Same::of()($other->getIterator())($comparatorCallback), [$this->getIterator()]))->getIterator()->current();
    }

    public function scale(
        float $lowerBound,
        float $upperBound,
        float $wantedLowerBound = 0.0,
        float $wantedUpperBound = 1.0,
        float $base = 0.0
    ): CollectionInterface {
        return new self(Scale::of()($lowerBound)($upperBound)($wantedLowerBound)($wantedUpperBound)($base), [$this->getIterator()]);
    }

    public function scanLeft(callable $callback, $initial = null): CollectionInterface
    {
        return new self(ScanLeft::of()($callback)($initial), [$this->getIterator()]);
    }

    public function scanLeft1(callable $callback): CollectionInterface
    {
        return new self(ScanLeft1::of()($callback), [$this->getIterator()]);
    }

    public function scanRight(callable $callback, $initial = null): CollectionInterface
    {
        return new self(ScanRight::of()($callback)($initial), [$this->getIterator()]);
    }

    public function scanRight1(callable $callback): CollectionInterface
    {
        return new self(ScanRight1::of()($callback), [$this->getIterator()]);
    }

    public function shuffle(?int $seed = null): CollectionInterface
    {
        return new self(Shuffle::of()($seed ?? random_int(PHP_INT_MIN, PHP_INT_MAX)), [$this->getIterator()]);
    }

    public function since(callable ...$callbacks): CollectionInterface
    {
        return new self(Since::of()(...$callbacks), [$this->getIterator()]);
    }

    public function slice(int $offset, int $length = -1): CollectionInterface
    {
        return new self(Slice::of()($offset)($length), [$this->getIterator()]);
    }

    public function sort(int $type = Operation\Sortable::BY_VALUES, ?callable $callback = null): CollectionInterface
    {
        return new self(Sort::of()($type)($callback), [$this->getIterator()]);
    }

    public function span(callable ...$callbacks): CollectionInterface
    {
        // TODO: Move this docblock above closure when https://github.com/phpstan/phpstan/issues/3770 lands.
        $mapCallback =
            /**
             * @param array{0: (Closure(Iterator<TKey, T>): Generator<TKey, T>), 1: (array{0: Iterator<TKey, T>})} $spanResult
             */
            static function (array $spanResult): CollectionInterface {
                /**
                 * @var Closure(Iterator<TKey, T>): Generator<TKey, T> $callback
                 * @var array{0: Iterator<TKey, T>} $parameters
                 */
                [$callback, $parameters] = $spanResult;

                return self::fromCallable($callback, $parameters);
            };

        return new self(Pipe::of()(Span::of()(...$callbacks), Map::of()($mapCallback)), [$this->getIterator()]);
    }

    public function split(int $type = Operation\Splitable::BEFORE, callable ...$callbacks): CollectionInterface
    {
        return new self(Split::of()($type)(...$callbacks), [$this->getIterator()]);
    }

    public function squash(): CollectionInterface
    {
        return self::fromIterable($this->pack()->all(false))->unpack();
    }

    public function strict(?callable $callback = null): CollectionInterface
    {
        return new self(Strict::of()($callback), [$this->getIterator()]);
    }

    public function tail(): CollectionInterface
    {
        return new self(Tail::of(), [$this->getIterator()]);
    }

    public function tails(): CollectionInterface
    {
        return new self(Tails::of(), [$this->getIterator()]);
    }

    public function takeWhile(callable ...$callbacks): CollectionInterface
    {
        return new self(TakeWhile::of()(...$callbacks), [$this->getIterator()]);
    }

    public static function times(int $number = 0, ?callable $callback = null): CollectionInterface
    {
        return self::empty()->pipe(Times::of()($number)($callback));
    }

    public function transpose(): CollectionInterface
    {
        return new self(Transpose::of(), [$this->getIterator()]);
    }

    public function truthy(): bool
    {
        return (new self(Truthy::of(), [$this->getIterator()]))->getIterator()->current();
    }

    public static function unfold(callable $callback, ...$parameters): CollectionInterface
    {
        return self::empty()->pipe(Unfold::of()(...$parameters)($callback));
    }

    public function unlines(): CollectionInterface
    {
        return new self(Unlines::of(), [$this->getIterator()]);
    }

    public function unpack(): CollectionInterface
    {
        return new self(Unpack::of(), [$this->getIterator()]);
    }

    public function unpair(): CollectionInterface
    {
        return new self(Unpair::of(), [$this->getIterator()]);
    }

    public function until(callable ...$callbacks): CollectionInterface
    {
        return new self(Until::of()(...$callbacks), [$this->getIterator()]);
    }

    public function unwindow(): CollectionInterface
    {
        return new self(Unwindow::of(), [$this->getIterator()]);
    }

    public function unwords(): CollectionInterface
    {
        return new self(Unwords::of(), [$this->getIterator()]);
    }

    public function unwrap(): CollectionInterface
    {
        return new self(Unwrap::of(), [$this->getIterator()]);
    }

    public function unzip(): CollectionInterface
    {
        return new self(Unzip::of(), [$this->getIterator()]);
    }

    public function when(callable $predicate, callable $whenTrue, ?callable $whenFalse = null): CollectionInterface
    {
        $whenFalse ??=
            /**
             * @param Iterator<TKey, T> $collection
             *
             * @return iterable<TKey, T>
             */
            static fn (Iterator $collection): iterable => $collection;

        return new self(When::of()($predicate)($whenTrue)($whenFalse), [$this->getIterator()]);
    }

    public function window(int $size): CollectionInterface
    {
        return new self(Window::of()($size), [$this->getIterator()]);
    }

    public function words(): CollectionInterface
    {
        return new self(Words::of(), [$this->getIterator()]);
    }

    public function wrap(): CollectionInterface
    {
        return new self(Wrap::of(), [$this->getIterator()]);
    }

    public function zip(iterable ...$iterables): CollectionInterface
    {
        return new self(Zip::of()(...$iterables), [$this->getIterator()]);
    }
}
