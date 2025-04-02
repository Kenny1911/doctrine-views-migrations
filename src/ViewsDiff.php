<?php

declare(strict_types=1);

namespace Kenny1911\DoctrineViewsMigrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\View;
use Doctrine\SqlFormatter\NullHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;

/**
 * @api
 */
final class ViewsDiff
{
    private SqlFormatter $sqlFormatter;

    public function __construct(
        private readonly AbstractPlatform $platform,
    ) {
        $this->sqlFormatter = new SqlFormatter(new NullHighlighter());
    }

    /**
     * @throws Exception
     */
    public static function createFromConnection(
        Connection $connection,
    ): self {
        return new self($connection->getDatabasePlatform());
    }

    /**
     * @return list<non-empty-string> List of sql queries for migration
     */
    public function generate(ViewsProvider $from, ViewsProvider $to): array
    {
        return $this->generateFromViewsMap(
            $this->viewsProviderToMap($from),
            $this->viewsProviderToMap($to),
        );
    }

    /**
     * @param array<string, View> $from
     * @param array<string, View> $to
     * @return list<non-empty-string>
     */
    private function generateFromViewsMap(array $from, array $to): array
    {
        $sql = [];
        $fromNames = array_keys($from);
        $toNames = array_keys($to);

        $createViewNames = array_values(array_diff($toNames, $fromNames));
        $dropViewNames = array_values(array_diff($fromNames, $toNames));
        $updateViewNames = array_values(array_intersect($fromNames, $toNames));

        foreach ($createViewNames as $name) {
            $view = $to[$name] ?? throw new \RuntimeException(\sprintf('To view with name %s not exists.', $name));
            $sql[] = $this->platform->getCreateViewSQL(
                $view->getQuotedName($this->platform),
                $view->getSql(),
            );
        }

        foreach ($dropViewNames as $name) {
            $sql[] = $this->platform->getDropViewSQL($name);
        }

        foreach ($updateViewNames as $name) {
            $fromView = $from[$name] ?? throw new \RuntimeException(\sprintf('From view with name %s not exists.', $name));
            $toView = $to[$name] ?? throw new \RuntimeException(\sprintf('To view with name %s not exists.', $name));

            if ($this->sqlFormatter->compress($fromView->getSql()) !== $this->sqlFormatter->compress($toView->getSql())) {
                $sql[] = $this->platform->getDropViewSQL($name);
                $sql[] = $this->platform->getCreateViewSQL(
                    $toView->getQuotedName($this->platform),
                    $toView->getSql(),
                );
            }
        }

        /** @var list<non-empty-string> All sql queries is not empty strings */
        return $sql;
    }

    /**
     * @return array<string, View>
     */
    private function viewsProviderToMap(ViewsProvider $viewsProvider): array
    {
        $map = [];

        foreach ($viewsProvider->getViews() as $view) {
            $map[$view->getName()] = $view;
        }

        return $map;
    }
}
