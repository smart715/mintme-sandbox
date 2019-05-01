<?php declare(strict_types = 1);

namespace App\Command;

use App\Exchange\Factory\MarketFactoryInterface;
use App\Manager\MarketStatusManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @codeCoverageIgnore
 */
class MigrateMarketsCommand extends Command
{
    /** @var MarketFactoryInterface */
    protected $marketFactory;

    /** @var MarketStatusManagerInterface */
    protected $statusManager;

    public function __construct(MarketFactoryInterface $marketFactory, MarketStatusManagerInterface $statusManager)
    {
        $this->marketFactory = $marketFactory;
        $this->statusManager = $statusManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:database:migrate-markets')
            ->setDescription('Create markets stats for predefined markets')
            ->setHelp('This command create markets stats for predefined markets');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->statusManager->createMarketStatus($this->marketFactory->createAll());
        $output->writeln('Predefined markets was migrated to market stats');
    }
}
