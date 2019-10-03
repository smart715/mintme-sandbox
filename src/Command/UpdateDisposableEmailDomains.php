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
        $this->setName('app:synchronize-domains')
            ->setDescription('Synchronize domains list');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->logger->info('[blacklist] Update job started..');

        $list = $this->domainSynchronizer->fetchDomains();
        $existed = $this->blacklistManager->getList('email');

        $this->logger->info($existed[0]->getValue());

        for ($i = 0; $i < 10; $i++) {
            $this->logger->info($list[$i]);
        }

        foreach ($list as $name) {
            if (!$this->isValueExists($name, $existed)) {
                $this->blacklistManager->addToBlacklist($name, 'email', false);
            }
        }

        $this->em->flush();

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
