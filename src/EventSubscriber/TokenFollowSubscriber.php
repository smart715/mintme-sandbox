<?php declare(strict_types = 1);

namespace App\EventSubscriber;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\UserTokenFollow;
use App\Events\OrderEvent;
use App\Events\RewardEvent;
use App\Events\TokenEvents;
use App\Events\TokenUserEventInterface;
use App\Exception\UserTokenFollowException;
use App\Exchange\Order;
use App\Manager\UserTokenFollowManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TokenFollowSubscriber implements EventSubscriberInterface
{
    private UserTokenFollowManager $userTokenFollowManager;

    public function __construct(
        UserTokenFollowManager $userTokenFollowManager
    ) {
        $this->userTokenFollowManager = $userTokenFollowManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OrderEvent::COMPLETED => 'onOrderCompleted',
            RewardEvent::VOLUNTEER_COMPLETED => 'onRewardCompleted',
            RewardEvent::PARTICIPANT_DELIVERED => 'onRewardCompleted',
            TokenEvents::AIRDROP_CLAIMED => 'onTokenUserEvent',
            TokenEvents::POST_SHARED => 'onTokenUserEvent',
            TokenEvents::DONATION => 'onTokenUserEvent',
        ];
    }

    public function onOrderCompleted(OrderEvent $event): void
    {
        $order = $event->getOrder();
        $quote =  $order->getMarket()->getQuote();
        $orderSide = $order->getSide();

        if ($quote instanceof Token && Order::DONATION_SIDE !== $orderSide) {
            $taker = $order->getTaker();
            $maker = $order->getMaker();

            $buyer = Order::SELL_SIDE === $orderSide
                ? $maker
                : $taker;

            $this->tryFollowToken($quote, $buyer);
        }
    }

    public function onRewardCompleted(RewardEvent $event): void
    {
        $token = $event->getReward()->getToken();
        $volunteer = $event->getRewardMember()->getUser();

        $this->tryFollowToken($token, $volunteer);
    }

    public function onTokenUserEvent(TokenUserEventInterface $event): void
    {
        $this->tryFollowToken(
            $event->getToken(),
            $event->getUser()
        );
    }

    private function tryFollowToken(Token $token, User $user): void
    {
        try {
            $this->userTokenFollowManager->autoFollow($token, $user);
        } catch (UserTokenFollowException $exception) {
        }
    }
}
