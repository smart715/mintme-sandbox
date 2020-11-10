<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\Token\Token;
use App\Events\OrderCompletedEvent;
use App\Events\UserNotificationEvent;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Manager\ScheduledNotificationManagerInterface;
use App\Utils\NotificationTypes;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderCompletedSubscriber implements EventSubscriberInterface
{
    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var MarketHandlerInterface */
    private $marketHandler;

    /** @var ScheduledNotificationManagerInterface */
    private $scheduledNotificationManager;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        MarketHandlerInterface $marketHandler,
        ScheduledNotificationManagerInterface $scheduledNotificationManager
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->marketHandler = $marketHandler;
        $this->scheduledNotificationManager = $scheduledNotificationManager;
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
                /** @psalm-suppress TooManyArguments */
                $this->eventDispatcher->dispatch(
                    new UserNotificationEvent(
                        $userTokenCreator,
                        NotificationTypes::NEW_INVESTOR,
                        $extraData
                    ),
                    UserNotificationEvent::NAME
                );
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
                    $notificationType = NotificationType::ORDER_CANCELLED;
                    $this->scheduledNotificationManager->createScheduledNotification(
                        $notificationType,
                        $currentUser
                    );
                }
            }
        }
    }
}
