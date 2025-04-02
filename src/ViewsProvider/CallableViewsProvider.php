<?php

declare(strict_types=1);

namespace Kenny1911\DoctrineViewsMigrations\ViewsProvider;

use Doctrine\DBAL\Schema\View;
use Kenny1911\DoctrineViewsMigrations\ViewsProvider;

/**
 * @api
 */
final class CallableViewsProvider implements ViewsProvider
{
    /**
     * @param callable(): iterable<View> $callable
     */
    public function __construct(
        private readonly mixed $callable,
    ) {}

    #[\Override]
    public function getViews(): iterable
    {
        return ($this->callable)();
    }
}
