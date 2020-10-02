<?php declare(strict_types = 1);

namespace App\Command\Blacklist;

use App\Communications\CryptoSynchronizerInterface;
use App\Entity\Blacklist;
use App\Manager\BlacklistManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @codeCoverageIgnore
 * Ignored due the conflicting ConsoleSectionOutput class.
 */
class SynchronizeTokenBlacklist extends Command
{
    private BlacklistManagerInterface $blacklistManager;
    private CryptoSynchronizerInterface $cryptoSynchronizer;

    public function __construct(
        BlacklistManagerInterface $blacklistManager,
        CryptoSynchronizerInterface $cryptoSynchronizer
    ) {
        $this->blacklistManager = $blacklistManager;
        $this->cryptoSynchronizer = $cryptoSynchronizer;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('blacklist:synchronize')
            ->setDescription('Synchronize existing coins list with a crypto data aggregator');
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ConsoleSectionOutput $section */
        /** @var ConsoleOutput $output */
        $section = $output->section();

        $style = new SymfonyStyle($input, $section);

        $existing = $this->cryptoSynchronizer->fetchCryptos();

        $progressBar = new ProgressBar($section, 4);
        $progressBar->start();

        $this->blacklistManager->bulkDelete(Blacklist::CRYPTO_NAME);
        $progressBar->advance(1);

        $this->blacklistManager->bulkDelete(Blacklist::CRYPTO_SYMBOL);
        $progressBar->advance(1);

        $this->blacklistManager->bulkAdd($existing['names'], Blacklist::CRYPTO_NAME);
        $progressBar->advance(1);

        $this->blacklistManager->bulkAdd($existing['symbols'], Blacklist::CRYPTO_SYMBOL);
        $style->success(
            'Synchronized '.count($existing['names']).' coins'
        );
        $progressBar->finish();

        return 0;
    }
}
