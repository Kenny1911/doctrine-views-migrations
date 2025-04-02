<?php

declare(strict_types=1);

namespace Kenny1911\DoctrineViewsMigrations\Console;

use Doctrine\DBAL\Exception;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Generator\Exception\NoChangesDetected;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\Tools\Console\Command\DoctrineCommand;
use Kenny1911\DoctrineViewsMigrations\ViewsDiff;
use Kenny1911\DoctrineViewsMigrations\ViewsProvider\ConnectionViewsProvider;
use Kenny1911\DoctrineViewsMigrations\ViewsProviderLocator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @api
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[AsCommand(name: 'doctrine:views-migrations:diff')]
final class DiffCommand extends DoctrineCommand
{
    public function __construct(
        private readonly ViewsProviderLocator $providers,
        ?DependencyFactory $dependencyFactory,
        ?string $name = null,
    ) {
        parent::__construct($dependencyFactory, $name);
    }

    #[\Override]
    protected function configure(): void
    {
        parent::configure();

        $this->addOption(
            'namespace',
            null,
            InputOption::VALUE_REQUIRED,
            'The namespace to use for the migration (must be in the list of configured namespaces)',
        )
            ->addOption(
                'formatted',
                null,
                InputOption::VALUE_NONE,
                'Format the generated SQL.',
            )
            ->addOption(
                'line-length',
                null,
                InputOption::VALUE_REQUIRED,
                'Max line length of unformatted lines.',
                '120',
            )
            ->addOption(
                'check-database-platform',
                null,
                InputOption::VALUE_OPTIONAL,
                'Check Database Platform to the generated code.',
                false,
            )
            ->addOption(
                'allow-empty-diff',
                null,
                InputOption::VALUE_NONE,
                'Do not throw an exception when no changes are detected.',
            );
    }

    /**
     * @throws \Exception
     * @throws Exception
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $formatted = filter_var($input->getOption('formatted'), FILTER_VALIDATE_BOOLEAN);
        $lineLength = (int) $input->getOption('line-length');
        $checkDbPlatform = filter_var($input->getOption('check-database-platform'), FILTER_VALIDATE_BOOLEAN);
        $allowEmptyDiff  = $input->getOption('allow-empty-diff');

        $statusCalculator = $this->getDependencyFactory()->getMigrationStatusCalculator();
        $executedUnavailableMigrations = $statusCalculator->getExecutedUnavailableMigrations();
        $newMigrations = $statusCalculator->getNewMigrations();

        if (! $this->checkNewMigrationsOrExecutedUnavailable($newMigrations, $executedUnavailableMigrations, $input)) {
            $this->io->error('Migration cancelled!');

            return 3;
        }

        $namespace = $this->getNamespace($input, $output);
        $fqcn = $this->getDependencyFactory()->getClassNameGenerator()->generateClassName($namespace);

        $connection = $this->getDependencyFactory()->getConnection();
        $migrationSqlGenerator = $this->getDependencyFactory()->getMigrationSqlGenerator();
        $migrationGenerator = $this->getDependencyFactory()->getMigrationGenerator();

        $from = new ConnectionViewsProvider($connection);
        $to = $this->providers->getViews($this->getDependencyFactory()->getConfiguration()->getConnectionName());
        $diff = ViewsDiff::createFromConnection($connection);
        $upSql = $diff->generate($from, $to);
        $downSql = $diff->generate($to, $from);

        if ([] === $upSql && [] === $downSql) {
            if ($allowEmptyDiff) {
                $this->io->error('No changes detected.');

                return self::SUCCESS;
            }

            throw new NoChangesDetected('No changes detected.');
        }

        /** @psalm-suppress InternalMethod */
        $up = $migrationSqlGenerator->generate($upSql, $formatted, $lineLength, $checkDbPlatform);
        /** @psalm-suppress InternalMethod */
        $down = $migrationSqlGenerator->generate($downSql, $formatted, $lineLength, $checkDbPlatform);

        /** @psalm-suppress InternalMethod */
        $path = $migrationGenerator->generateMigration($fqcn, $up, $down);

        $this->io->text([
            \sprintf('Generated new migration class to "<info>%s</info>"', $path),
            '',
            \sprintf(
                'To run just this migration for testing purposes, you can use <info>migrations:execute --up \'%s\'</info>',
                addslashes($fqcn),
            ),
            '',
            \sprintf(
                'To revert the migration you can use <info>migrations:execute --down \'%s\'</info>',
                addslashes($fqcn),
            ),
            '',
        ]);

        return self::SUCCESS;
    }

    private function checkNewMigrationsOrExecutedUnavailable(
        AvailableMigrationsList $newMigrations,
        ExecutedMigrationsList $executedUnavailableMigrations,
        InputInterface $input,
    ): bool {
        if (0 === \count($newMigrations) && 0 === \count($executedUnavailableMigrations)) {
            return true;
        }

        if (0 !== \count($newMigrations)) {
            $this->io->warning(\sprintf(
                'You have %d available migrations to execute.',
                \count($newMigrations),
            ));
        }

        if (0 !== \count($executedUnavailableMigrations)) {
            $this->io->warning(\sprintf(
                'You have %d previously executed migrations in the database that are not registered migrations.',
                \count($executedUnavailableMigrations),
            ));
        }

        return $this->canExecute('Are you sure you wish to continue?', $input);
    }
}
