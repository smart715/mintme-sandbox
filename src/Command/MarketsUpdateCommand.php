<?php declare(strict_types = 1);

namespace App\Command;

use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Manager\MarketStatusManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MarketsUpdateCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:markets:update';

    /** @var MarketStatusManagerInterface */
    private $marketStatusManager;

    /** @var MarketFactoryInterface */
    private $marketFactory;

    public function __construct(
        MarketStatusManagerInterface $marketStatusManager,
        MarketFactoryInterface $marketFactory
    ) {
        $this->marketStatusManager = $marketStatusManager;
        $this->marketFactory = $marketFactory;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Update markets with information from viabtc server')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $markets = $this->marketFactory->createAll();
        $io->progressStart(count($markets));

        foreach ($markets as $market) {
            $this->marketStatusManager->updateMarketStatus($market);
            $io->progressAdvance();
        }

        $io->progressFinish();
        $io->success('Markets updated');
    }
}
