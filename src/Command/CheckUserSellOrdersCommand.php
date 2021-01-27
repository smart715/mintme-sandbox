<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\ScheduledNotification;
use App\Entity\Token\Token;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Mailer\MailerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\ScheduledNotificationManagerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Notifications\Strategy\NotificationContext;
use App\Notifications\Strategy\OrderNotificationStrategy;
use App\Utils\NotificationTypes;
use DateTimeImmutable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckUserSellOrdersCommand extends Command
{
    /** @var string  */
    protected static $defaultName = 'app:check-user-sell-orders';

    /** @var array */
    public array $timeIntervals;

    /** @var ScheduledNotificationManagerInterface */
    private $scheduledNotificationManager;

    /** @var UserNotificationManagerInterface */
    private $userNotificationManager;

    /** @var MarketHandlerInterface */
    private $marketHandler;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var MailerInterface */
    private MailerInterface $mailer;

    public function __construct(
        ScheduledNotificationManagerInterface $scheduledNotificationManager,
        MarketHandlerInterface $marketHandler,
        CryptoManagerInterface $cryptoManager,
        UserNotificationManagerInterface $userNotificationManager,
        MailerInterface $mailer
    ) {
        $this->scheduledNotificationManager = $scheduledNotificationManager;
        $this->marketHandler = $marketHandler;
        $this->cryptoManager = $cryptoManager;
        $this->userNotificationManager = $userNotificationManager;
        $this->mailer = $mailer;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('To send user notification if the market is empty after orders filled or cancellation.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $scheduledNotifications = $this->scheduledNotificationManager->getScheduledNotifications();

        /** @var ScheduledNotification $scheduledNotification */
        foreach ($scheduledNotifications as $scheduledNotification) {
            $quoteTokens = $scheduledNotification->getUser()->getProfile()->getTokens();

            if (!$quoteTokens) {
                $this->checkForTokensDeletions($scheduledNotification);

                continue;
            }

            foreach ($quoteTokens as $quoteToken) {
                $this->scheduleNotificationForToken(
                    $scheduledNotification,
                    $quoteToken
                );
            }
        }

        return 0;
    }

    private function scheduleNotificationForToken(
        ScheduledNotification $scheduledNotification,
        Token $quoteToken
    ): void {
        $notificationType = $scheduledNotification->getType();
        $user = $scheduledNotification->getUser();
        $timeInterval = $scheduledNotification->getTimeInterval();
        $dateToBeSend = $scheduledNotification->getDateToBeSend();
        $baseCrypto = $this->cryptoManager->findBySymbol($quoteToken->getCryptoSymbol());
        $userMarket = new Market($baseCrypto, $quoteToken);

        $userSellOrders = $this->marketHandler->getSellOrdersSummaryByUser($user, $userMarket);

        if (count($userSellOrders) > 0) {
            $this->scheduledNotificationManager->removeScheduledNotification($scheduledNotification->getId());
        }

        $actual_date = new DateTimeImmutable();

        if (0 === count($userSellOrders) && $dateToBeSend <= $actual_date) {
            $this->userNotificationManager->createNotification(
                $user,
                $notificationType,
                []
            );

            $strategy = new OrderNotificationStrategy(
                $this->userNotificationManager,
                $this->mailer,
                $quoteToken,
                $notificationType
            );
            $notificationContext = new NotificationContext($strategy);
            $notificationContext->sendNotification($user);

            $lastSent = $this->isLastNotificationSent($notificationType, $timeInterval);

            if ($lastSent) {
                $this->scheduledNotificationManager->removeScheduledNotification($scheduledNotification->getId());
            } else {
                $this->updateScheduledNotification(
                    $scheduledNotification,
                    $notificationType,
                    $timeInterval,
                    $dateToBeSend
                );
            }
        }
    }

    private function isLastNotificationSent(String $notificationType, String $timeInterval): bool
    {
        if (NotificationTypes::ORDER_CANCELLED === $notificationType &&
            (string)$this->timeIntervals[2] === $timeInterval
        ) {
            return true;
        }

        return NotificationTypes::ORDER_FILLED === $notificationType &&
            (string)$this->timeIntervals[2] === $timeInterval;
    }

    private function updateScheduledNotification(
        ScheduledNotification $scheduledNotification,
        String $notificationType,
        String $timeInterval,
        DateTimeImmutable $timeToBeSend
    ): void {
        $newTimeInterval = '0';

        if (NotificationTypes::ORDER_CANCELLED === $notificationType) {
            $newTimeInterval = (string)$this->timeIntervals[2];
        }

        if (NotificationTypes::ORDER_FILLED === $notificationType) {
            $newTimeInterval = (string)$this->timeIntervals[0] === $timeInterval ?
                (string)$this->timeIntervals[1] :
                (string)$this->timeIntervals[2];
        }

        $newTimeToBeSend = $timeToBeSend->modify('+'.$newTimeInterval.' minutes');

        $this->scheduledNotificationManager->updateScheduledNotification(
            $scheduledNotification,
            $newTimeInterval,
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
