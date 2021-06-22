<?php declare(strict_types = 1);

namespace App\Command;

use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\LockFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/* Cron job added to DB. */
class MarketsUpdateCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:markets:update';

    /** @var MarketStatusManagerInterface */
    private $marketStatusManager;

    /** @var MarketFactoryInterface */
    private $marketFactory;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var RebrandingConverterInterface */
    private $rebrandingConverter;

    /** @var LockFactory */
    private $lockFactory;

    public function __construct(
        MarketStatusManagerInterface $marketStatusManager,
        MarketFactoryInterface $marketFactory,
        CryptoManagerInterface $cryptoManager,
        TokenManagerInterface $tokenManager,
        RebrandingConverterInterface $rebrandingConverter,
        LockFactory $lockFactory
    ) {
        $this->marketStatusManager = $marketStatusManager;
        $this->marketFactory = $marketFactory;
        $this->cryptoManager = $cryptoManager;
        $this->tokenManager = $tokenManager;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->lockFactory = $lockFactory;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Update markets with information from viabtc server')
            ->addArgument('market', InputArgument::OPTIONAL, 'The market to update (e.g. MINTME/BTC)')
            ->addOption('cron', null, InputOption::VALUE_NONE, 'Run in cron mode');
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lock = $this->lockFactory->createLock('markets-update');

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
        $io->progressStart(count($markets));

        foreach ($markets as $market) {
            $tries = 10;

            while ($tries > 0) {
                try {
                    $this->marketStatusManager->updateMarketStatus($market);

                    break;
                } catch (\Throwable $e) {
                    $tries--;
                }
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
