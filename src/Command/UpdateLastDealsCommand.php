<?php declare(strict_types = 1);

namespace App\Command;

use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Manager\MarketStatusManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateLastDealsCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:update-last-deals';

    private MarketFactoryInterface $marketFactory;
    private MarketHandlerInterface $marketHandler;
    private EntityManagerInterface $entityManager;
    private MarketStatusManagerInterface $marketStatusManager;

    public function __construct(
        MarketFactoryInterface $marketFactory,
        MarketHandlerInterface $marketHandler,
        EntityManagerInterface $entityManager,
        MarketStatusManagerInterface $marketStatusManager
    ) {
        $this->marketFactory = $marketFactory;
        $this->marketHandler = $marketHandler;
        $this->entityManager = $entityManager;
        $this->marketStatusManager = $marketStatusManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Update last_deal_id on the market_status table. Intended to be used once')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $markets = $this->marketFactory->createAll();

        $io->progressStart(count($markets));

        foreach ($markets as $market) {
            $tries = 10;

            while ($tries > 0) {
                try {
                    $marketStatus = $this->marketStatusManager->getMarketStatus($market);

                    $result = $this->marketHandler->getExecutedOrders($market, 0, 1);

                    if (isset($result[0])) {
                        $marketStatus->setLastDealId($result[0]->getId() ?? 0);
                    }

                    $this->entityManager->persist($marketStatus);
                    $this->entityManager->flush();

                    break;
                } catch (\Throwable $e) {
                    $tries--;
                }
            }

            $io->progressAdvance();
        }

        $io->success('Markets updated');

        return 0;
    }
}
