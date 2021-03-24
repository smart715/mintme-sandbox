<?php declare(strict_types = 1);

namespace App\Command;

use App\Manager\ScheduledNotificationManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\NotificationTypes;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AirdropFeatureNotificationFixCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:airdrop:notification:fix';

    private TokenManagerInterface $tokenManager;
    private ScheduledNotificationManagerInterface $scheduledNotificationManager;

    public function __construct(
        TokenManagerInterface $tokenManager,
        ScheduledNotificationManagerInterface $scheduledNotificationManager
    ) {
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
        $tokens = $this->tokenManager->getTokensWithAirdrops();

        $io = new SymfonyStyle($input, $output);
        $io->progressStart(count($tokens));

        foreach ($tokens as $token) {
            $this->scheduledNotificationManager->removeByTypeForUser(
                NotificationTypes::MARKETING_AIRDROP_FEATURE,
                $token->getOwner()
            );

            $io->progressAdvance();
        }

        $io->progressFinish();
        $io->success('Finished');

        return 0;
    }
}
