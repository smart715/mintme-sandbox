<?php declare(strict_types = 1);

namespace App\Tests\EventSubscriber;

use App\Entity\Crypto;
use App\Entity\Rewards\Reward;
use App\Entity\Rewards\RewardMemberInterface;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Entity\UserTokenFollow;
use App\Events\DonationEvent;
use App\Events\OrderEvent;
use App\Events\PostEvent;
use App\Events\RewardEvent;
use App\Events\TokenEvents;
use App\Events\TokenUserEventInterface;
use App\Events\UserAirdropEvent;
use App\EventSubscriber\TokenFollowSubscriber;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Manager\UserTokenFollowManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TokenFollowSubscriberTest extends TestCase
{
    private EventDispatcher $dispatcher;

    public function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
    }

    /** @dataProvider onOrderCompletedEventProvider */
    public function testOnOrderCompletedEvent(
        bool $isQuoteToken,
        int $orderSide
    ): void {
        $taker = $this->createMock(User::class);
        $maker = $this->createMock(User::class);
        $quote = $isQuoteToken
            ? $this->createMock(Token::class)
            : $this->createMock(Crypto::class);

        $follower = Order::SELL_SIDE === $orderSide
            ? $maker
            : $taker;

        $event = $this->mockOrderCompletedEvent($taker, $maker, $quote, $orderSide);

        $utfm = $quote instanceof Token && Order::DONATION_SIDE !== $orderSide
            ? $this->mockUserTokenFollowManager($quote, $follower)
            : $this->mockUserTokenFollowManager();

        $subscriber = new TokenFollowSubscriber($utfm);

        $this->dispatcher->addSubscriber($subscriber);
        $this->dispatcher->dispatch($event, OrderEvent::COMPLETED);
    }

    /** @dataProvider onTokenUserEventProvider */
    public function testOnTokenUserEvent(
        string $eventClass
    ): void {
        $token = $this->createMock(Token::class);
        $user = $this->createMock(User::class);

        $event = $this->mockTokenUserEvent($user, $token, $eventClass);

        $userTokenFollowManager = $this->mockUserTokenFollowManager($token, $user);

        $subscriber = new TokenFollowSubscriber($userTokenFollowManager);

        $this->dispatcher->addSubscriber($subscriber);
        $this->dispatcher->dispatch($event, TokenEvents::AIRDROP_CLAIMED);
    }

    /** @dataProvider onRewardAcceptedEventProvider */
    public function testOnRewardAcceptedAccepted(
        string $eventName
    ): void {
        $token = $this->createMock(Token::class);
        $user = $this->createMock(User::class);

        $event = $this->mockRewardEvent($user, $token);

        $userTokenFollowManager = $this->mockUserTokenFollowManager($token, $user);

        $subscriber = new TokenFollowSubscriber(
            $userTokenFollowManager,
        );

        $this->dispatcher->addSubscriber($subscriber);
        $this->dispatcher->dispatch($event, $eventName);
    }

    private function mockOrderCompletedEvent(
        User $taker,
        User $maker,
        TradableInterface $quote,
        int $orderSide
    ): OrderEvent {
        $market = $this->createMock(Market::class);
        $market->expects($this->once())
            ->method('getQuote')
            ->willReturn($quote);

        $order = $this->createMock(Order::class);
        $order->expects($this->once())
            ->method('getMarket')
            ->willReturn($market);

        $order->expects($this->once())
            ->method('getSide')
            ->willReturn($orderSide);

        if ($quote instanceof Token && Order::DONATION_SIDE !== $orderSide) {
            $order->expects($this->once())
                ->method('getTaker')
                ->willReturn($taker);

            $order->expects($this->once())
                ->method('getMaker')
                ->willReturn($maker);
        }

        $orderEvent = $this->createMock(OrderEvent::class);
        $orderEvent->expects($this->once())
            ->method('getOrder')
            ->willReturn($order);

        return $orderEvent;
    }

    private function mockUserTokenFollowManager(
        ?Token $token = null,
        ?User $follower = null
    ): UserTokenFollowManager {
        $userTokenFollowManager = $this->createMock(UserTokenFollowManager::class);

        if (null !== $token) {
            $userTokenFollowManager->expects($this->once())
                ->method('autoFollow')
                ->with($token, $follower);
        }

        return $userTokenFollowManager;
    }

    private function mockRewardEvent(
        User $user,
        Token $token
    ): RewardEvent {
        $reward = $this->createMock(Reward::class);
        $reward->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $rewardMember = $this->createMock(RewardMemberInterface::class);
        $rewardMember->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $rewardEvent = $this->createMock(RewardEvent::class);
        $rewardEvent->expects($this->once())
            ->method('getReward')
            ->willReturn($reward);

        $rewardEvent->expects($this->once())
            ->method('getRewardMember')
            ->willReturn($rewardMember);

        return $rewardEvent;
    }

    private function mockTokenUserEvent(
        User $user,
        Token $token,
        string $eventClass
    ): TokenUserEventInterface {
        $tokenUserEvent = $this->createMock($eventClass); /** @phpstan-ignore-line */
        $tokenUserEvent->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $tokenUserEvent->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        /** @var TokenUserEventInterface $tokenUserEvent */
        return $tokenUserEvent;
    }

    public function onOrderCompletedEventProvider(): array
    {
        return [
            [
                false,
                Order::SELL_SIDE,
            ],
            [
                true,
                Order::SELL_SIDE,
            ],
            [
                true,
                Order::BUY_SIDE,
            ],
            [
                true,
                Order::DONATION_SIDE,
            ],
        ];
    }

    public function onRewardAcceptedEventProvider(): array
    {
        return [
            [
                RewardEvent::PARTICIPANT_DELIVERED,
            ],
            [
                RewardEvent::VOLUNTEER_COMPLETED,
            ],
            [
                RewardEvent::VOLUNTEER_COMPLETED,
            ],
        ];
    }

    public function onTokenUserEventProvider(): array
    {
        return [
            [
                PostEvent::class,
            ],
            [
                UserAirdropEvent::class,
            ],
            [
                DonationEvent::class,
            ],
        ];
    }
}
