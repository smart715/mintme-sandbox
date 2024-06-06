<?php declare(strict_types = 1);

namespace App\Command;

use App\Communications\DisposableEmailCommunicatorInterface;
use App\Entity\Blacklist\Blacklist;
use App\Manager\BlacklistManagerInterface;
use App\Utils\LockFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/* Cron job added to DB. */
class UpdateDisposableEmailDomains extends Command
{
    private BlacklistManagerInterface $blacklistManager;
    private DisposableEmailCommunicatorInterface $domainSynchronizer;
    private EntityManagerInterface $em;
    private LoggerInterface $logger;
    private LockFactory $lockFactory;

    public function __construct(
        LoggerInterface $logger,
        BlacklistManagerInterface $blacklistManager,
        DisposableEmailCommunicatorInterface $domainSynchronizer,
        EntityManagerInterface $em,
        LockFactory $lockFactory
    ) {
        $this->logger = $logger;
        $this->blacklistManager = $blacklistManager;
        $this->domainSynchronizer = $domainSynchronizer;
        $this->em = $em;
        $this->lockFactory = $lockFactory;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:synchronize-domains')
            ->setDescription('Synchronize domains list');
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lock = $this->lockFactory->createLock('synchronize-domains');

        if (!$lock->acquire()) {
            return 0;
        }

        $io = new SymfonyStyle($input, $output);

        $this->logger->info('[blacklist] Update job started..');
        $this->logger->info('[blacklist] Domains from index fetch start..');

        $list = $this->domainSynchronizer->fetchDomainsIndex();

        $this->logger->info('[blacklist] Domains from index fetched..');

        $existed = $this->blacklistManager->getList('email');

        foreach ($list as $name) {
            if (!$this->isValueExists($name, $existed)) {
                $this->blacklistManager->add($name, 'email', false);
            }
        }

        $this->em->flush();

        $this->logger->info('[blacklist] Domains from wildcard fetch start..');

        $list = $this->domainSynchronizer->fetchDomainsWildcard();

        $this->logger->info('[blacklist] Domains from wildcard fetched..');

        $existed = $this->blacklistManager->getList('email');

        foreach ($list as $name) {
            if (!$this->isValueExists($name, $existed)) {
                $this->blacklistManager->add($name, 'email', false);
            }
        }

        $this->em->flush();

        $io->success('Synchronization completed');
        $this->logger->info('[blacklist] Update job finished..');

        $lock->release();

        return 0;
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
