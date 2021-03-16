<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\ScheduledNotification;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Mailer\MailerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\ScheduledNotificationManagerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Notifications\Strategy\MarketingAirdropFeatureNotificationStrategy;
use App\Notifications\Strategy\NotificationContext;
use App\Notifications\Strategy\OrderNotificationStrategy;
use App\Notifications\Strategy\TokenMarketingTipsNotificationStrategy;
use App\Utils\NotificationTypes;
use DateTimeImmutable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckScheduledNotificationsCommand extends Command
{
    /** @var string  */
    protected static $defaultName = 'app:check-scheduled-notifications';

    public array $filled_intervals;
    public array $cancelled_intervals;
    public array $token_marketing_tips_intervals;
    public array $marketing_airdrop_feature_intervals;
    public array $kbLinks;

    private ScheduledNotificationManagerInterface $scheduledNotificationManager;
    private UserNotificationManagerInterface $userNotificationManager;
    private MarketHandlerInterface $marketHandler;
    private CryptoManagerInterface $cryptoManager;
    private MailerInterface $mailer;

    public function __construct(
        ScheduledNotificationManagerInterface $scheduledNotificationManager,
        MarketHandlerInterface $marketHandler,
        CryptoManagerInterface $cryptoManager,
        UserNotificationManagerInterface $userNotificationManager,
        MailerInterface $mailer,
        array $kbLinks,
        array $filled_intervals,
        array $cancelled_intervals,
        array $token_marketing_tips_intervals,
        array $marketing_airdrop_feature_intervals
    ) {
        $this->scheduledNotificationManager = $scheduledNotificationManager;
        $this->marketHandler = $marketHandler;
        $this->cryptoManager = $cryptoManager;
        $this->userNotificationManager = $userNotificationManager;
        $this->mailer = $mailer;
        $this->kbLinks = $kbLinks;
        $this->filled_intervals = $filled_intervals;
        $this->cancelled_intervals = $cancelled_intervals;
        $this->token_marketing_tips_intervals = $token_marketing_tips_intervals;
        $this->marketing_airdrop_feature_intervals = $marketing_airdrop_feature_intervals;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Checks the notification scheduled to be sent to the user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $scheduledNotifications = $this->scheduledNotificationManager->getScheduledNotifications();

        /** @var ScheduledNotification $scheduledNotification */
        foreach ($scheduledNotifications as $scheduledNotification) {
            $notificationType = $scheduledNotification->getType();

            if (in_array($notificationType, NotificationTypes::ORDER_TYPES, true)) {
                $this->scheduleNotificationOrdersForToken($scheduledNotification);

                continue;
            }

            if (in_array($notificationType, NotificationTypes::MARKETING_TYPES)) {
                $this->scheduleMarketingNotification($scheduledNotification);
            }
        }

        return 0;
    }

    private function scheduleNotificationOrdersForToken(ScheduledNotification $scheduledNotification): void
    {
        $quoteTokens = $scheduledNotification->getUser()->getProfile()->getTokens();
        $notificationType = $scheduledNotification->getType();
        $user = $scheduledNotification->getUser();
        $timeInterval = $scheduledNotification->getTimeInterval();
        $dateToBeSend = $scheduledNotification->getDateToBeSend();
        $arrayOfIntervals = $this->{strtolower($notificationType) . '_intervals'};

        if (!$quoteTokens) {
            $this->checkForTokensDeletions($scheduledNotification);

            return;
        }

        foreach ($quoteTokens as $quoteToken) {
            $baseCrypto = $this->cryptoManager->findBySymbol($quoteToken->getCryptoSymbol());
            $userMarket = new Market($baseCrypto, $quoteToken);
            $userSellOrders = $this->marketHandler->getSellOrdersSummaryByUser($user, $userMarket);

            if (count($userSellOrders) > 0) {
                $this->scheduledNotificationManager->removeScheduledNotification($scheduledNotification->getId());
            }

            $actual_date = new DateTimeImmutable();

            if ($actual_date < $dateToBeSend) {
                return;
            }

            $strategy = new OrderNotificationStrategy(
                $this->userNotificationManager,
                $this->mailer,
                $quoteToken,
                $notificationType
            );
            $notificationContext = new NotificationContext($strategy);
            $notificationContext->sendNotification($user);

            $lastSent = end($arrayOfIntervals) === $timeInterval;

            if ($lastSent) {
                $this->scheduledNotificationManager->removeScheduledNotification($scheduledNotification->getId());

                return;
            }

            $this->updateScheduledNotification(
                $scheduledNotification,
                $timeInterval,
                $arrayOfIntervals,
                $dateToBeSend
            );
        }
    }

    private function scheduleMarketingNotification(ScheduledNotification $scheduledNotification): void
    {
        $notificationType = $scheduledNotification->getType();
        $timeInterval = $scheduledNotification->getTimeInterval();
        $dateToBeSend = $scheduledNotification->getDateToBeSend();
        $user = $scheduledNotification->getUser();
        $arrayOfIntervals = $this->{strtolower($notificationType) . '_intervals'};

        if (0 === count($user->getTokens())) {
            $this->scheduledNotificationManager->removeScheduledNotification($scheduledNotification->getId());

            return;
        }

        $actual_date = new DateTimeImmutable();

        if ($actual_date < $dateToBeSend) {
            return;
        }

        switch ($notificationType) {
            case NotificationTypes::TOKEN_MARKETING_TIPS:
                $strategy = new TokenMarketingTipsNotificationStrategy(
                    $this->userNotificationManager,
                    $this->mailer,
                    $notificationType,
                    $timeInterval,
                    $this->kbLinks,
                    $arrayOfIntervals
                );

                break;
            case NotificationTypes::MARKETING_AIRDROP_FEATURE:
                $strategy = new MarketingAirdropFeatureNotificationStrategy(
                    $this->userNotificationManager,
                    $this->mailer
                );

                break;
        }

        if (!isset($strategy)) {
            return;
        }

        $notificationContext = new NotificationContext($strategy);
        $notificationContext->sendNotification($user);

        $lastSent = end($arrayOfIntervals) === $timeInterval;

        if ($lastSent) {
            $this->scheduledNotificationManager->removeScheduledNotification($scheduledNotification->getId());

            return;
        }

        $this->updateScheduledNotification(
            $scheduledNotification,
            $timeInterval,
            $arrayOfIntervals,
            $dateToBeSend
        );
    }

    private function updateScheduledNotification(
        ScheduledNotification $scheduledNotification,
        string $timeInterval,
        array $allIntervals,
        DateTimeImmutable $timeToBeSend
    ): void {
        $lastInterval = end($allIntervals);
        $currentPosition = array_search($timeInterval, $allIntervals, true);

        $newInterval = $timeInterval === $lastInterval
            ? $lastInterval
            : $allIntervals[(int)$currentPosition + 1];

        $newTimeToBeSend = $timeToBeSend->modify('+' . $newInterval);

        $this->scheduledNotificationManager->updateScheduledNotification(
            $scheduledNotification,
            $newInterval,
            $newTimeToBeSend
        );
    }

    private function checkForTokensDeletions(ScheduledNotification $scheduledNotification): void
    {
        $notificationType = $scheduledNotification->getType();

        if (in_array($notificationType, NotificationTypes::ORDER_TYPES, true)) {
            $this->scheduledNotificationManager->removeScheduledNotification($scheduledNotification->getId());
        }
    }
}
