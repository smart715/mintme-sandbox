<?php declare(strict_types = 1);

namespace App\Tests\EventSubscriber;

use App\Entity\Post;
use App\Entity\Rewards\Reward;
use App\Entity\Rewards\RewardMemberInterface;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Events\DepositCompletedEvent;
use App\Events\OrderEvent;
use App\Events\OrderEventInterface;
use App\Events\PostEvent;
use App\Events\RewardEvent;
use App\Events\TokenEvents;
use App\Events\TokenUserEventInterface;
use App\Events\TransactionCompletedEvent;
use App\Events\WithdrawCompletedEvent;
use App\EventSubscriber\ChangeDiscordRoleSubscriber;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Manager\DiscordManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ChangeDiscordRoleSubscriberTest extends TestCase
{
    private EventDispatcher $dispatcher;

    public function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
    }

    /** @dataProvider handleTokenUserEventNameProvider */
    public function testHandleTokenUserEvent(string $eventName): void
    {
        $subscriber = new ChangeDiscordRoleSubscriber(
            $this->mockDiscordManager($this->once())
        );

        $this->dispatcher->addSubscriber($subscriber);

        $user = $this->mockUser();
        $token = $this->mockToken();
        $event = $this->mockTokenUserEvent($user, $token);

        $this->dispatcher->dispatch($event, $eventName);
    }

    public function handleTokenUserEventNameProvider(): array
    {
        return [
            'airdrop.claimed event' => [TokenEvents::AIRDROP_CLAIMED],
            'donation event' => [TokenEvents::DONATION],
        ];
    }


    /** @dataProvider handleTransactionEventNameProvider */
    public function testHandleTransactionEvent(string $eventName): void
    {
        $subscriber = new ChangeDiscordRoleSubscriber(
            $this->mockDiscordManager($this->once())
        );

        $this->dispatcher->addSubscriber($subscriber);

        $token = $this->mockToken();
        $event = $this->mockTransactionCompletedEvent($token);

        $this->dispatcher->dispatch($event, $eventName);
    }

    /** @dataProvider handleTransactionEventNameProvider */
    public function testHandleTransactionEventWithNonTokenTradableWontBeProcessed(string $eventName): void
    {
        $subscriber = new ChangeDiscordRoleSubscriber(
            $this->mockDiscordManager($this->never())
        );

        $this->dispatcher->addSubscriber($subscriber);

        $tradable = $this->mockTradalble();
        $event = $this->mockTransactionCompletedEvent($tradable);

        $this->dispatcher->dispatch($event, $eventName);
    }

    public function handleTransactionEventNameProvider(): array
    {
        return [
            'deposit.complete event' => [DepositCompletedEvent::NAME],
            'withdraw.complete event' => [WithdrawCompletedEvent::NAME],
        ];
    }

    /** @dataProvider handleOrderEventNameProvider */
    public function testHandleOrderEvent(string $eventName): void
    {
        $subscriber = new ChangeDiscordRoleSubscriber(
            $this->mockDiscordManager($this->exactly(2))
        );

        $this->dispatcher->addSubscriber($subscriber);

        $event = $this->mockOrderEvent(true, true);

        $this->dispatcher->dispatch($event, $eventName);
    }

    /** @dataProvider handleOrderEventNameProvider */
    public function testHandleOrderWithoutTokenMarketWillNotProceed(string $eventName): void
    {
        $subscriber = new ChangeDiscordRoleSubscriber(
            $this->mockDiscordManager($this->never())
        );

        $this->dispatcher->addSubscriber($subscriber);

        $event = $this->mockOrderEvent(false);

        $this->dispatcher->dispatch($event, $eventName);
    }

    /** @dataProvider handleOrderEventNameProvider */
    public function testHandleOrderWithoutTaker(string $eventName): void
    {
        $subscriber = new ChangeDiscordRoleSubscriber(
            $this->mockDiscordManager($this->once())
        );

        $this->dispatcher->addSubscriber($subscriber);

        $event = $this->mockOrderEvent(true, false);

        $this->dispatcher->dispatch($event, $eventName);
    }

    public function handleOrderEventNameProvider(): array
    {
        return [
            'order.completed event' => [OrderEvent::COMPLETED],
            'order.created event' => [OrderEvent::CREATED],
        ];
    }

    /** @dataProvider handleCancelledOrderEventNameProvider */
    public function testHandleCancelledOrderEvent(string $eventName): void
    {
        $subscriber = new ChangeDiscordRoleSubscriber(
            $this->mockDiscordManager($this->never())
        );

        $this->dispatcher->addSubscriber($subscriber);

        $event = $this->mockOrderEvent(true, false, 1, 2);

        $this->dispatcher->dispatch($event, $eventName);

        // Do it twice to test it on both cases (Already exists and not)
        $this->doAddUserAndTokenToHandleOnTerminateTest($subscriber);
        $this->doAddUserAndTokenToHandleOnTerminateTest($subscriber);
    }

    /** @dataProvider handleCancelledOrderEventNameProvider */
    public function testHandleCancelledOrderEventWithoutTokenMarketWillNotProceed(string $eventName): void
    {
        $subscriber = new ChangeDiscordRoleSubscriber(
            $this->mockDiscordManager($this->never())
        );

        $this->dispatcher->addSubscriber($subscriber);

        $event = $this->mockOrderEvent(false);

        $this->dispatcher->dispatch($event, $eventName);
    }

    public function handleCancelledOrderEventNameProvider(): array
    {
        return [
            'order.cancelled event' => [OrderEvent::CANCELLED],
        ];
    }

    /** @dataProvider handlePostSharedEventNameProvider */
    public function testHandlePostSharedEvent(string $eventName): void
    {
        $subscriber = new ChangeDiscordRoleSubscriber(
            $this->mockDiscordManager($this->never())
        );

        $this->dispatcher->addSubscriber($subscriber);

        $event = $this->mockPostEvent();

        $this->dispatcher->dispatch($event, $eventName);
        $this->doAddUserAndTokenToHandleOnTerminateTest($subscriber);
    }

    /** @dataProvider handlePostSharedEventNameProvider */
    public function testHandlePostSharedEventWontProceedIfRewardIsZero(string $eventName): void
    {
        $subscriber = new ChangeDiscordRoleSubscriber(
            $this->mockDiscordManager($this->never())
        );

        $this->dispatcher->addSubscriber($subscriber);

        $event = $this->mockPostEvent(true);

        $this->dispatcher->dispatch($event, $eventName);
    }

    public function handlePostSharedEventNameProvider(): array
    {
        return [
            'post.shared event' => [TokenEvents::POST_SHARED],
        ];
    }

    /** @dataProvider handleRewardEventNameProvider */
    public function testHandleRewardEvent(string $eventName): void
    {
        $subscriber = new ChangeDiscordRoleSubscriber(
            $this->mockDiscordManager($this->never())
        );

        $this->dispatcher->addSubscriber($subscriber);

        $event = $this->mockRewardEvent(true);

        $this->dispatcher->dispatch($event, $eventName);
        $this->doAddUserAndTokenToHandleOnTerminateTest($subscriber);
    }

    /** @dataProvider handleRewardEventNameProvider */
    public function testHandleRewardEventWithoutRewardMember(string $eventName): void
    {
        $subscriber = new ChangeDiscordRoleSubscriber(
            $this->mockDiscordManager($this->never())
        );

        $this->dispatcher->addSubscriber($subscriber);

        $event = $this->mockRewardEvent(false);

        $this->dispatcher->dispatch($event, $eventName);
    }

    public function handleRewardEventNameProvider(): array
    {
        return [
            'reward.participant_added' => [RewardEvent::PARTICIPANT_ADDED],
            'reward.volunteer_accepted' => [RewardEvent::VOLUNTEER_COMPLETED],
        ];
    }

    /** @dataProvider changeRolesOnTerminateEventNameProvider */
    public function testChangeRolesOnTerminate(string $eventName): void
    {
        $randomInt = rand(0, 10);

        $subscriber = new ChangeDiscordRoleSubscriber(
            $this->mockDiscordManager($this->exactly($randomInt))
        );

        $this->dispatcher->addSubscriber($subscriber);

        $event = $this->mockKernelEvent();


        $this->mockSubscriberProperties($subscriber, $randomInt);

        $this->dispatcher->dispatch($event, $eventName);
    }

    public function changeRolesOnTerminateEventNameProvider(): array
    {
        return [
            'kernel.terminate event' => [KernelEvents::TERMINATE],
        ];
    }

    private function doAddUserAndTokenToHandleOnTerminateTest(ChangeDiscordRoleSubscriber $subscriber): void
    {
        $reflection = new \ReflectionClass($subscriber);
        $mapProperty = $reflection->getProperty('map');
        $mapProperty->setAccessible(true);

        $usersProperty = $reflection->getProperty('users');
        $usersProperty->setAccessible(true);

        $tokensProperty = $reflection->getProperty('tokens');
        $tokensProperty->setAccessible(true);

        $this->assertEquals(2, $mapProperty->getValue($subscriber)[1][0]);
        $this->assertArrayHasKey(1, $mapProperty->getValue($subscriber));
        $this->assertArrayHasKey(2, $tokensProperty->getValue($subscriber));
        $this->assertArrayHasKey(1, $usersProperty->getValue($subscriber));
    }

    private function mockSubscriberProperties(ChangeDiscordRoleSubscriber $subscriber, int $randomInt): void
    {
        $reflection = new \ReflectionClass($subscriber);
        $mapProperty = $reflection->getProperty('map');
        $mapProperty->setAccessible(true);

        $usersProperty = $reflection->getProperty('users');
        $usersProperty->setAccessible(true);
        $usersProperty->setValue($subscriber, []);

        $tokensProperty = $reflection->getProperty('tokens');
        $tokensProperty->setAccessible(true);
        $tokensProperty->setValue($subscriber, []);

        $tokens = $tokensProperty->getValue($subscriber);
        $users = $usersProperty->getValue($subscriber);
        $map = $mapProperty->getValue($subscriber);
        $map[0] = [];
        $users[0] = $this->mockUser();

        for ($i = 0; $i < $randomInt; $i++) {
            $tokens[$i] = $this->mockToken();
            $map[0][] = $i;
        }

        $mapProperty->setValue($subscriber, $map);
        $usersProperty->setValue($subscriber, $users);
        $tokensProperty->setValue($subscriber, $tokens);
    }

    private function mockToken(?int $id = null): Token
    {
        $token = $this->createMock(Token::class);

        if ($id) {
            $token->method('getId')->willReturn($id);
        }

        return $token;
    }

    private function mockDiscordManager(InvokedCount $invokedCount): DiscordManagerInterface
    {
        $discordManager = $this->createMock(DiscordManagerInterface::class);
        $discordManager->expects($invokedCount)
            ->method('updateRoleOfUser');

        return $discordManager;
    }

    private function mockTokenUserEvent(User $user, Token $token): TokenUserEventInterface
    {
        $event = $this->createMock(TokenUserEventInterface::class);
        $event->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $event->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        return $event;
    }

    private function mockUser(?int $id = null): User
    {
        $user = $this->createMock(User::class);

        if ($id) {
            $user->method('getId')->willReturn($id);
        }

        return $user;
    }

    private function mockTransactionCompletedEvent(TradableInterface $tradable): TransactionCompletedEvent
    {
        $event = $this->createMock(TransactionCompletedEvent::class);
        $event->expects($this->once())
            ->method('getTradable')
            ->willReturn($tradable);

        return $event;
    }

    private function mockTradalble(): TradableInterface
    {
        return $this->createMock(TradableInterface::class);
    }

    private function mockOrderEvent(
        bool $isTokenMarket,
        bool $takerExist = false,
        ?int $userId = null,
        ?int $tokenId = null
    ): OrderEventInterface {
        $event = $this->createMock(OrderEventInterface::class);
        $event->expects($this->once())
            ->method('getOrder')
            ->willReturn($this->mockOrder($isTokenMarket, $takerExist, $userId, $tokenId));

        return $event;
    }

    private function mockOrder(bool $isTokenMarket, bool $takerExist, ?int $userId, ?int $tokenId): Order
    {
        $order = $this->createMock(Order::class);
        $order->expects($this->once())
            ->method('getMarket')
            ->willReturn($this->mockMarket($isTokenMarket, $tokenId));


        $order->method('getTaker')
            ->willReturn($takerExist ? $this->mockUser() : null);

        $order->expects($isTokenMarket ? $this->once() : $this->never())
            ->method('getMaker')
            ->willReturn($this->mockUser($userId));

        return $order;
    }

    private function mockMarket(bool $isTokenMarket, ?int $tokenId): Market
    {
        $market = $this->createMock(Market::class);
        $market->expects($this->once())
            ->method('isTokenMarket')
            ->willReturn($isTokenMarket);

        $market->expects($isTokenMarket ? $this->once() : $this->never())
            ->method('getQuote')
            ->willReturn($this->mockToken($tokenId));

        return $market;
    }

    private function mockPostEvent(bool $isZero = false): PostEvent
    {
        $event = $this->createMock(PostEvent::class);
        $event->expects($this->once())
            ->method('getPost')
            ->willReturn($this->mockPost($isZero));

        $event->expects($isZero ? $this->never() : $this->once())
            ->method('getUser')
            ->willReturn($this->mockUser(1));

        $event->expects($isZero ? $this->never() : $this->once())
            ->method('getToken')
            ->willReturn($this->mockToken(2));

        return $event;
    }

    private function mockPost(bool $isZero): Post
    {
        $post = $this->createMock(Post::class);
        $post->expects($this->once())
            ->method('getShareReward')
            ->willReturn($this->dummyMoneyObject($isZero ? '0' : '1'));

        return $post;
    }

    private function dummyMoneyObject(string $amount, string $currency = 'TEST'): Money
    {
        return new Money($amount, new Currency($currency));
    }

    private function mockRewardEvent(bool $rewardMemberExist = true): RewardEvent
    {
        $event = $this->createMock(RewardEvent::class);
        $event->expects($this->once())
            ->method('getRewardMember')
            ->willReturn($rewardMemberExist ? $this->mockRewardMember(1) : null);

        $event->expects($rewardMemberExist ? $this->once() : $this->never())
            ->method('getReward')
            ->willReturn($this->mockReward(2));

        return $event;
    }

    private function mockReward(int $tokenId): Reward
    {
        $reward = $this->createMock(Reward::class);
        $reward->method('getToken')->willReturn($this->mockToken($tokenId));

        return $reward;
    }

    private function mockRewardMember(int $userId): RewardMemberInterface
    {
        $rewardMember = $this->createMock(RewardMemberInterface::class);
        $rewardMember->expects($this->once())
            ->method('getUser')
            ->willReturn($this->mockUser($userId));

        return $rewardMember;
    }

    private function mockKernelEvent(): KernelEvent
    {
        return $this->createMock(KernelEvent::class);
    }
}
