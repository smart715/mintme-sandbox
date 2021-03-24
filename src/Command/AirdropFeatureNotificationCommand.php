<?php declare(strict_types = 1);

namespace App\Command;

use App\Manager\ScheduledNotificationManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\NotificationTypes;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AirdropFeatureNotificationCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:airdrop:notification';

    private EntityManagerInterface $em;
    private TokenManagerInterface $tokenManager;
    private ScheduledNotificationManagerInterface $scheduledNotificationManager;

    public function __construct(
        EntityManagerInterface $em,
        TokenManagerInterface $tokenManager,
        ScheduledNotificationManagerInterface $scheduledNotificationManager
    ) {
        $this->em = $em;
        $this->tokenManager = $tokenManager;
        $this->scheduledNotificationManager = $scheduledNotificationManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Add scheduled notifications for airdrop feature for old tokens');
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tokens = $this->tokenManager->getTokensWithoutAirdrops();

        $io = new SymfonyStyle($input, $output);
        $io->progressStart(count($tokens));

        foreach ($tokens as $token) {
            $scheduledNotification = $this->scheduledNotificationManager->createScheduledNotification(
                NotificationTypes::MARKETING_AIRDROP_FEATURE,
                $token->getOwner(),
                false
            );

            $scheduledNotification->setDateToBeSend(
                $token->getCreated()->modify($scheduledNotification->getTimeInterval())
            );

            $this->em->persist($scheduledNotification);

            $io->progressAdvance();
        }

        $this->em->flush();

        $io->progressFinish();
        $io->success('Finished');

        return 0;
    }
}
