<?php declare(strict_types = 1);

namespace App\Command;

use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\LockFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/* Cron job added to DB. */
class MarketsUpdateCommand extends Command
{
    private MarketStatusManagerInterface $marketStatusManager;
    private MarketFactoryInterface $marketFactory;
    private CryptoManagerInterface $cryptoManager;
    private TokenManagerInterface $tokenManager;
    private RebrandingConverterInterface $rebrandingConverter;
    private LockFactory $lockFactory;
    private EntityManagerInterface $entityManager;

    private const BATCH_SIZE = 20;

    public function __construct(
        MarketStatusManagerInterface $marketStatusManager,
        MarketFactoryInterface $marketFactory,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        RebrandingConverterInterface $rebrandingConverter,
        LockFactory $lockFactory,
        EntityManagerInterface $entityManager
    ) {
        $this->marketStatusManager = $marketStatusManager;
        $this->marketFactory = $marketFactory;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->lockFactory = $lockFactory;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:markets:update')
            ->setDescription('Update markets with information from viabtc server')
            ->addArgument('market', InputArgument::OPTIONAL, 'The market to update (e.g. MINTME/BTC)')
            ->addOption('cron', null, InputOption::VALUE_NONE, 'Run in cron mode');
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // FileBasedLock because we clear EntityManager later,
        // otherwise this process would lose this Lock
        $lock = $this->lockFactory->createFileBasedLock('markets-update');

        if (!$lock->acquire()) {
            return 0;
        }

        $io = new SymfonyStyle($input, $output);

        /** @var string $market */
        $market = $input->getArgument('market');

        $result = $market
            ? $this->updateOne($this->rebrandingConverter->reverseConvert($market), $io)
            : $this->updateAll($io, (bool)$input->getOption('cron'));

        $lock->release();

        return $result;
    }

    protected function updateAll(SymfonyStyle $io, bool $cron = false): int
    {
        $markets = $cron
            ? array_map(
                fn ($ms) => new Market($ms->getCrypto(), $ms->getQuote()),
                $this->marketStatusManager->getExpired()
            )
            : $this->marketFactory->createAll();

        $marketsCount = count($markets);
        $io->progressStart($marketsCount);

        $this->entityManager->beginTransaction();

        foreach ($markets as $i => $market) {
            $tries = 10;

            while ($tries > 0) {
                try {
                    // We first fetch all the markets that we're updating,
                    // then we clear the EntityManager, detaching all the entities from it,
                    // after that we refetch them so we get an attached one again and update it.
                    // Finally, each self::BATCH_SIZE update EntityManager is cleared again.
                    //
                    // Clearing EntityManager improves performance by 10x since it's a lot of markets/entities
                    // being attached to it
                    $attachedMarket = $this->marketFactory->createBySymbols(
                        $market->getBase()->getSymbol(),
                        $market->getQuote()->getSymbol()
                    );

                    $this->marketStatusManager->updateMarketStatus($attachedMarket);

                    break;
                } catch (\Throwable $e) {
                    $tries--;
                }
            }

            if (0 === (int)$i % self::BATCH_SIZE || (int)$i >= $marketsCount - 1) {
                // It enters here in the first iteration because $i is 0
                $this->entityManager->commit();
                $this->entityManager->clear();
                $this->entityManager->beginTransaction();
            }

            $io->progressAdvance();
        }

        $io->progressFinish();
        $io->success('Markets updated');

        return 0;
    }

    protected function updateOne(string $market, SymfonyStyle $io): int
    {
        $market = explode('/', $market);

        if (2 !== count($market)) {
            $io->error('Invalid argument market');

            return 1;
        }

        $base = $this->cryptoManager->findBySymbol($market[0]);
        $quote = $this->cryptoManager->findBySymbol($market[1]) ?? $this->tokenManager->findByName($market[1]);

        if (!$base) {
            $io->error('Base crypto not found');

            return 1;
        }

        if (!$quote) {
            $io->error('Quote crypto or token not found');

            return 1;
        }

        $market = $this->marketFactory->create($base, $quote);

        try {
            $this->marketStatusManager->updateMarketStatus($market);
            $io->success('Market updated');
        } catch (\Throwable $e) {
            $io->error('Error: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
