<?php declare(strict_types = 1);

namespace App\Command\Blacklist;

use App\Communications\CryptoSynchronizerInterface;
use App\Manager\BlacklistManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SynchronizeTokenBlacklist extends Command
{
    private const LIMIT = 500;

    /** @var BlacklistManagerInterface */
    private $blacklistManager;

    /** @var CryptoSynchronizerInterface */
    private $cryptoSynchronizer;

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
            ->setDescription('Synchronize coin list with coinmarketcap');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        /** @var ConsoleSectionOutput $section */
        $section = $output->section();
        $style = new SymfonyStyle($input, $section);

        $i = 1;

        while ([] !== ($list = $this->cryptoSynchronizer->fetchCryptos(
            1 === $i ? $i : self::LIMIT * ($i - 1),
            self::LIMIT
        ))) {
            $style->writeln("Fetched {$i} page from service. Found " . count($list) . " elements.");
            $this->blacklistManager->migrate($list, 'token');
            $i++;
        }

        $section->clear();
        $style->success('Synchronization completed.');
    }
}
