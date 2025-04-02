<?php

declare(strict_types=1);

namespace Kenny1911\DoctrineViewsMigrations\Console;

use Doctrine\DBAL\Exception;
use Doctrine\Migrations\DependencyFactory;
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
#[AsCommand(name: 'doctrine:views-migrations:dump-sql')]
final class DumpSqlCommand extends DoctrineCommand
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

        $this->addOption('plain-sql', mode: InputOption::VALUE_NONE, description: 'Output plain sql.');
    }

    /**
     * @throws Exception
     */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = $this->getDependencyFactory()->getConnection();
        $plain = (bool) $input->getOption('plain-sql');

        $from = new ConnectionViewsProvider($connection);
        $to = $this->providers->getViews($this->getDependencyFactory()->getConfiguration()->getConnectionName());
        $diff = ViewsDiff::createFromConnection($connection);
        $sql = $diff->generate($from, $to);

        foreach ($sql as $s) {
            $output->writeln($s . ($plain ? ';' : ''));
        }

        if ([] === $sql && !$plain) {
            $this->io->success('No changes detected.');
        }

        return self::SUCCESS;
    }
}
