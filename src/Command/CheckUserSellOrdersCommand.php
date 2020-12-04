<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\ScheduledNotification;
use App\Entity\Token\Token;
use App\Entity\UserNotification;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\ScheduledNotificationManagerInterface;
use App\Manager\UserNotificationManagerInterface;
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

    public function __construct(
        ScheduledNotificationManagerInterface $scheduledNotificationManager,
        MarketHandlerInterface $marketHandler,
        CryptoManagerInterface $cryptoManager,
        UserNotificationManagerInterface $userNotificationManager
    ) {
        $this->scheduledNotificationManager = $scheduledNotificationManager;
        $this->marketHandler = $marketHandler;
        $this->cryptoManager = $cryptoManager;
        $this->userNotificationManager = $userNotificationManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('To send user notification if the market is empty after orders filled or cancellation.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $scheduledNotifications = $this->scheduledNotificationManager->getScheduledNotifications();

        foreach ($scheduledNotifications as $scheduledNotification) {
            $notificationType = $scheduledNotification->getType();
            $timeInterval = $scheduledNotification->getTimeInterval();
            $dateToBeSend = $scheduledNotification->getDateToBeSend();
            $user = $scheduledNotification->getUser();
            $quoteToken = $user->getProfile()->getToken();

            if (!$quoteToken) {
                $this->scheduledNotificationManager->removeScheduledNotification($scheduledNotification->getId());

                continue;
            }

            $baseCrypto = $this->cryptoManager->findBySymbol(Token::WEB_SYMBOL);
            $UserMarket = new Market($baseCrypto, $quoteToken);

            $userSellOrders = $this->marketHandler->getSellOrdersSummaryByUser($user, $UserMarket);

            if ($userSellOrders) {
                $this->scheduledNotificationManager->removeScheduledNotification($scheduledNotification->getId());
            }

            $actual_date = new DateTimeImmutable();

            if (!$userSellOrders && $dateToBeSend <= $actual_date) {
                $this->userNotificationManager->createNotification(
                    $user,
                    $notificationType,
                    []
                );

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

        return 0;
    }

    private function isLastNotificationSent(String $notificationType, String $timeInterval): bool
    {
        if (UserNotification::ORDER_CANCELLED_NOTIFICATION === $notificationType &&
            (string)$this->timeIntervals[2] === $timeInterval
        ) {
            return true;
        }

        return UserNotification::ORDER_FILLED_NOTIFICATION === $notificationType &&
            (string)$this->timeIntervals[2] === $timeInterval;
    }

    private function updateScheduledNotification(
        ScheduledNotification $scheduledNotification,
        String $notificationType,
        String $timeInterval,
        DateTimeImmutable $timeToBeSend
    ): void {
        $newTimeInterval = '0';

        if (UserNotification::ORDER_CANCELLED_NOTIFICATION === $notificationType) {
            $newTimeInterval = (string)$this->timeIntervals[2];
        }

        if (UserNotification::ORDER_FILLED_NOTIFICATION === $notificationType) {
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
}
