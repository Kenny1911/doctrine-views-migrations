<?php

declare(strict_types=1);

namespace Kenny1911\DoctrineViewsMigrations\Util;

/**
 * @internal
 * @psalm-internal Kenny1911\DoctrineViewsMigrations
 *
 * @psalm-template T
 * @implements \IteratorAggregate<array-key, T>
 */
final class RewindableGenerator implements \IteratorAggregate
{
    /** @var \Closure(): iterable<array-key, T> */
    private \Closure $generator;

    /**
     * @param callable(): iterable<array-key, T> $generator
     */
    public function __construct(callable $generator)
    {
        $this->generator = $generator(...);
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        $iter = ($this->generator)();

        return $iter instanceof \Traversable ? $iter : new \ArrayIterator($iter);
    }
}
