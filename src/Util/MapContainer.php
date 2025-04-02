<?php

declare(strict_types=1);

namespace Kenny1911\DoctrineViewsMigrations\Util;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @internal
 * @psalm-internal Kenny1911\DoctrineViewsMigrations
 *
 * @template T
 */
final class MapContainer implements ContainerInterface
{
    /**
     * @param array<string, T> $map
     */
    public function __construct(
        private readonly array $map,
    ) {}

    /**
     * @return T
     */
    #[\Override]
    public function get(string $id): mixed
    {
        return $this->map[$id] ?? throw new class ("Item with {$id} not found.") extends \LogicException implements NotFoundExceptionInterface {};
    }

    #[\Override]
    public function has(string $id): bool
    {
        return isset($this->map[$id]);
    }
}
