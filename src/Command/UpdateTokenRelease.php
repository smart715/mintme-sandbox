<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\Token\LockIn;
use App\Repository\LockInRepository;
use App\Utils\LockFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/* Cron job added to DB. */
class UpdateTokenRelease extends Command
{
    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManagerInterface */
    private $em;

    /** @var LockFactory */
    private $lockFactory;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        LockFactory $lockFactory
    ) {
        $this->logger = $logger;
        $this->em = $entityManager;
        $this->lockFactory = $lockFactory;

        parent::__construct();
    }

    /** {@inheritdoc} */
    protected function configure(): void
    {
        $this
            ->setName('app:update-token-release')
            ->setDescription('Update tokens release')
            ->setHelp('This command updates all token\s release period');
    }

    /** {@inheritdoc} */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lock = $this->lockFactory->createLock('update-token-release');

        if (!$lock->acquire()) {
            return 0;
        }

        $this->logger->info('[release] Update job started..');

        /** @var LockIn[] $locked */
        $locked = $this->getLockInTokenRepository()->findAllUnreleased();

        foreach ($locked as $item) {
            $item->updateFrozenAmount();
            $this->em->persist($item);
        }

        $updateMessage = count($locked) . ' tokens were updated. Saving to DB..';

        $this->logger->info('[release] '.$updateMessage);
        $output->writeln($updateMessage);

        $this->em->flush();

        $this->logger->info('[release] Finished.');

        $lock->release();

        return 0;
    }

    private function getLockInTokenRepository(): LockInRepository
    {
        return $this->em->getRepository(LockIn::class);
    }
}
