<?php declare(strict_types = 1);

namespace App\Command\Blacklist;

use App\Communications\CryptoSynchronizerInterface;
use App\Entity\Blacklist;
use App\Manager\BlacklistManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SynchronizeTokenBlacklist extends Command
{
    /** @var BlacklistManagerInterface */
    private $blacklistManager;

    /** @var CryptoSynchronizerInterface */
    private $cryptoSynchronizer;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(
        BlacklistManagerInterface $blacklistManager,
        CryptoSynchronizerInterface $cryptoSynchronizer,
        EntityManagerInterface $em
    ) {
        $this->blacklistManager = $blacklistManager;
        $this->cryptoSynchronizer = $cryptoSynchronizer;
        $this->em = $em;

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
        $list = $this->cryptoSynchronizer->fetchCryptos();
        $existed = $this->blacklistManager->getList('token');
        $progressBar = new ProgressBar($section, count($list));

        $progressBar->start();

        foreach ($list as $name) {
            if (!$this->isValueExists($name, $existed)) {
                $this->blacklistManager->addToBlacklist($name, 'token', false);
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        $this->em->flush();

        $section->clear();
        $style->success('Synchronization completed.');
    }

    /** @param array<Blacklist> $list */
    private function isValueExists(string $value, array $list): bool
    {
        foreach ($list as $item) {
            if ($item->getValue() === $value) {
                return true;
            }
        }

        return false;
    }
}
