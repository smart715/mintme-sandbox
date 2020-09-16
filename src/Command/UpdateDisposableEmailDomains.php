<?php declare(strict_types = 1);

namespace App\Command;

use App\Communications\DisposableEmailCommunicatorInterface;
use App\Entity\Blacklist;
use App\Manager\BlacklistManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/* Cron job added to DB. */
class UpdateDisposableEmailDomains extends Command
{

    use LockTrait;

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
        $this->setName('app:synchronize-domains')
            ->setDescription('Synchronize domains list');
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lock = $this->createLock($this->em->getConnection(), 'synchronize-domains');

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
                $this->blacklistManager->addToBlacklist($name, 'email', false);
            }
        }

        $this->em->flush();

        $this->logger->info('[blacklist] Domains from wildcard fetch start..');

        $list = $this->domainSynchronizer->fetchDomainsWildcard();

        $this->logger->info('[blacklist] Domains from wildcard fetched..');

        $existed = $this->blacklistManager->getList('email');

        foreach ($list as $name) {
            if (!$this->isValueExists($name, $existed)) {
                $this->blacklistManager->addToBlacklist($name, 'email', false);
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
