<?php

declare(strict_types=1);

namespace Kenny1911\DoctrineViewsMigrations;

use Kenny1911\DoctrineViewsMigrations\Util\MapContainer;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @api
 */
final class ViewsProviderLocator
{
    public function __construct(
        private readonly ContainerInterface $providers,
        private readonly string $defaultConnectionName,
    ) {}

    /**
     * @param array<string, ViewsProvider> $map
     */
    public static function fromMap(array $map, string $defaultConnectionName): self
    {
        return new self(new MapContainer($map), $defaultConnectionName);
    }

    public function getViews(?string $connectionName = null): ViewsProvider
    {
        $connectionName ??= $this->defaultConnectionName;

        try {
            if ($this->providers->has($connectionName)) {
                /** @var ViewsProvider */
                return $this->providers->get($connectionName);
            }

            /** @var ViewsProvider */
            return $this->providers->get($this->defaultConnectionName);
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
            throw new \LogicException($e->getMessage());
        }
    }
}
