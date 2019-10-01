<?php declare(strict_types = 1);

namespace App\Command\Blacklist;

use App\Communications\DisposableEmailCommunicatorInterface;
use App\Entity\Blacklist;
use App\Manager\BlacklistManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/* Cron job added to DB. */
class UpdateDisposableEmailDomains extends Command
{
    /** @var BlacklistManagerInterface */
    private $blacklistManager;

    /** @var DisposableEmailCommunicatorInterface */
    private $domainSynchronizer;

    /** @var EntityManagerInterface */
    private $em;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        LoggerInterface $logger,
        BlacklistManagerInterface $blacklistManager,
        DisposableEmailCommunicatorInterface $domainSynchronizer,
        EntityManagerInterface $em
    ) {
        $this->logger = $logger;
        $this->blacklistManager = $blacklistManager;
        $this->domainSynchronizer = $domainSynchronizer;
        $this->em = $em;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('blacklist:synchronize-domains')
            ->setDescription('Synchronize domains list');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->logger->info('[blacklist] Update job started..');

        /** @var ConsoleSectionOutput $section */
        $section = $output->section();
        $style = new SymfonyStyle($input, $section);
        $list = $this->domainSynchronizer->fetchDomains();
        $existed = $this->blacklistManager->getList('email');
        $progressBar = new ProgressBar($section, count($list));

        $progressBar->start();

        foreach ($list as $name) {
            if (!$this->isValueExists($name, $existed)) {
                $this->blacklistManager->addToBlacklist($name, 'email', false);
            }

            $progressBar->advance();
        }

        $this->blacklistManager->addToBlacklist('awebro', 'email', false);

        $progressBar->finish();

        $this->em->flush();

        $section->clear();
        $style->success('Synchronization completed.');

        $this->logger->info('[blacklist] Update job finished..');
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
