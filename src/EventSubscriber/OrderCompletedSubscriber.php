<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\Token\Token;
use App\Events\OrderCompletedEvent;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Mailer\MailerInterface;
use App\Manager\ScheduledNotificationManagerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Notifications\Strategy\NewInvestorStrategy;
use App\Notifications\Strategy\NotificationContext;
use App\Utils\NotificationTypes;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderCompletedSubscriber implements EventSubscriberInterface
{

    /** @var MarketHandlerInterface */
    private $marketHandler;

    /** @var ScheduledNotificationManagerInterface */
    private $scheduledNotificationManager;

    /** @var UserNotificationManagerInterface */
    private UserNotificationManagerInterface $userNotificationManager;

    /** @var MailerInterface */
    private MailerInterface $mailer;

    public function __construct(
        MarketHandlerInterface $marketHandler,
        ScheduledNotificationManagerInterface $scheduledNotificationManager,
        UserNotificationManagerInterface $userNotificationManager,
        MailerInterface $mailer
    ) {
        $this->marketHandler = $marketHandler;
        $this->scheduledNotificationManager = $scheduledNotificationManager;
        $this->userNotificationManager = $userNotificationManager;
        $this->mailer = $mailer;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OrderCompletedEvent::CREATED => 'orderCreated',
            OrderCompletedEvent::CANCELLED => 'orderCancelled',
        ];
    }

    public function orderCreated(OrderCompletedEvent $event): void
    {
        $this->sendUserNotificationOnCreated($event);
    }

    public function orderCancelled(OrderCompletedEvent $event): void
    {
        $this->sendUserNotificationOnCancel($event);
    }

    private function sendUserNotificationOnCreated(OrderCompletedEvent $event): void
    {
        $order = $event->getOrder();
        $quote =  $event->getQuote();

        if ($quote instanceof Token) {
            $makerTokens = $order->getMaker()->getProfile()->getUser()->getTokens();
            $userProfile = $order->getMaker()->getProfile()->getNickname();
            $userTokenCreator = $quote->getProfile()->getUser();
            $orderType = $order->getSide();
            $market = $order->getMarket();
            $tokenName = $quote->getName();

            if (Order::BUY_SIDE === $orderType && !in_array($quote, $makerTokens, true)) {
                $extraData = [
                    'profile' => $userProfile,
                    'tokenName' => $tokenName,
                ];
                $notificationType = NotificationTypes::NEW_INVESTOR;
                $strategy = new NewInvestorStrategy(
                    $this->userNotificationManager,
                    $this->mailer,
                    $notificationType,
                    $extraData
                );
                $notificationContext = new NotificationContext($strategy);
                $notificationContext->sendNotification($userTokenCreator);
            }

            if (Order::BUY_SIDE === $orderType &&
                !$this->marketHandler->getSellOrdersSummaryByUser($userTokenCreator, $market)
            ) {
                $notificationType = NotificationTypes::ORDER_FILLED;
                $this->scheduledNotificationManager->createScheduledNotification(
                    $notificationType,
                    $userTokenCreator
                );
            }
        }
    }

    private function sendUserNotificationOnCancel(OrderCompletedEvent $event): void
    {
        $quote =  $event->getQuote();

        if ($quote instanceof Token) {
            $market = $event->getOrder()->getMarket();
            $currentUser = $quote->getProfile()->getUser();
            $userToken = $currentUser->getProfile()->getToken();

            if ($userToken && $quote === $userToken) {
                $userSellOrdersSummary = $this->marketHandler->getSellOrdersSummaryByUser($currentUser, $market);

                if (!$userSellOrdersSummary) {
                    $notificationType = NotificationTypes::ORDER_CANCELLED;
                    $this->scheduledNotificationManager->createScheduledNotification(
                        $notificationType,
                        $currentUser
                    );
                }
            }
        }
    }
}
