<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\Token\LockIn;
use App\Repository\LockInRepository;
use Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/* Cron job added to DB. */
class UpdateTokenRelease extends AbstractCommand
{
    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager
    ) {
        $this->logger = $logger;
        $this->em = $entityManager;

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
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->logger->info('Update job started..');

        /** @var LockIn[] $locked */
        $locked = $this->getLockInTokenRepository()->findAllUnreleased();

        foreach ($locked as $item) {
            $item->updateFrozenAmount();
            $this->em->persist($item);
        }

        $updateMessage = count($locked) . ' tokens were updated. Saving to DB..';

        $this->logger->info($updateMessage);
        $output->writeln($updateMessage);

        $this->em->flush();

        $this->logger->info('Finished.');
    }

    private function getLockInTokenRepository(): LockInRepository
    {
        return $this->em->getRepository(LockIn::class);
    }
}
