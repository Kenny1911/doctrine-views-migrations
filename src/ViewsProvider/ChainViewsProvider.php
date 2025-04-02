<?php

declare(strict_types=1);

namespace Kenny1911\DoctrineViewsMigrations\ViewsProvider;

use Kenny1911\DoctrineViewsMigrations\Util\RewindableGenerator;
use Kenny1911\DoctrineViewsMigrations\ViewsProvider;

/**
 * @api
 */
final class ChainViewsProvider implements ViewsProvider
{
    /**
     * @param iterable<ViewsProvider> $providers
     */
    public function __construct(
        private readonly iterable $providers,
    ) {}

    #[\Override]
    public function getViews(): iterable
    {
        return new RewindableGenerator(function () {
            foreach ($this->providers as $provider) {
                foreach ($provider->getViews() as $view) {
                    yield $view;
                }
            }
        });
    }
}
