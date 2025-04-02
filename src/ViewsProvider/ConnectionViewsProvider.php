<?php

declare(strict_types=1);

namespace Kenny1911\DoctrineViewsMigrations\ViewsProvider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\View;
use Kenny1911\DoctrineViewsMigrations\ViewsProvider;

/**
 * @api
 */
final class ConnectionViewsProvider implements ViewsProvider
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    /**
     * @throws Exception
     */
    #[\Override]
    public function getViews(): iterable
    {
        $platform = $this->connection->getDatabasePlatform();
        $schemaManager = $platform->createSchemaManager($this->connection);

        $views = $schemaManager->listViews();

        return array_map(
            static function (View $view) use ($platform): View {
                $removeFromSql = 'CREATE VIEW ' . $view->getQuotedName($platform) . ' AS ';

                if (str_starts_with($view->getSql(), $removeFromSql)) {
                    return new View($view->getName(), str_replace($removeFromSql, '', $view->getSql()));
                }

                return $view;
            },
            $views,
        );
    }
}
