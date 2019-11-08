<?php

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
    protected static $defaultName = 'app:markets:update';

    /** @var MarketStatusManagerInterface */
    private $marketStatusManager;

    /** @var MarketFactoryInterface */
    private $marketFactory;

    public function __construct(
        MarketStatusManagerInterface $marketStatusManager,
        MarketFactoryInterface $marketFactory,
        EntityManagerInterface $em,
        MarketHandlerInterface $mh
    ) {
        $this->marketStatusManager = $marketStatusManager;
        $this->marketFactory = $marketFactory;
        $this->em = $em;
        $this->mh = $mh;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Update markets with information from viabtc server')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->marketFactory->createAll() as $market) {
            $output->writeln("Currenly updating market: ".$market->getBase()->getSymbol()."/".$market->getQuote()->getSymbol());
            $this->marketStatusManager->updateMarketStatus($market);
        }
    }
}
