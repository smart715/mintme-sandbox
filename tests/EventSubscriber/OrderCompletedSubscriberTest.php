<?php declare(strict_types = 1);

namespace App\Tests\EventSubscriber;

use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Events\OrderEvent;
use App\EventSubscriber\OrderCompletedSubscriber;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandler;
use App\Exchange\Order;
use App\Mailer\Mailer;
use App\Manager\ScheduledNotificationManager;
use App\Manager\UserNotificationManager;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class OrderCompletedSubscriberTest extends TestCase
{
    private EventDispatcher $dispatcher;

    public function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
    }

    /** @dataProvider orderCreatedEventNameProvider */
    public function testOrderCreated(string $eventName): void
    {
        $subscriber = new OrderCompletedSubscriber(
            $this->mockMarketHandler(),
            $this->mockScheduledNotificationManager(),
            $this->mockUserNotificationManager(),
            $this->mockMailer()
        );

        $this->dispatcher->addSubscriber($subscriber);

        $event = $this->mockOrderEvent();

        $this->dispatcher->dispatch($event, $eventName);
    }


    public function orderCreatedEventNameProvider(): array
    {
        return [
            'order.created event' => [OrderEvent::CREATED],
        ];
    }


    /** @dataProvider orderCancelledEventNameProvider */
    public function testOrderCancelled(string $eventName): void
    {
        $subscriber = new OrderCompletedSubscriber(
            $this->mockMarketHandler(),
            $this->mockScheduledNotificationManager(),
            $this->mockUserNotificationManager(),
            $this->mockMailer()
        );

        $this->dispatcher->addSubscriber($subscriber);

        $event = $this->mockOrderEvent();

        $this->dispatcher->dispatch($event, $eventName);
    }

    public function orderCancelledEventNameProvider(): array
    {
        return [
            'order.cancelled event' => [OrderEvent::CANCELLED],
        ];
    }

    private function mockOrderEvent(): OrderEvent
    {
        $event = $this->createMock(OrderEvent::class);

        $event->method('getOrder')
            ->willReturn($this->mockOrder());

        $event->method('getLeft')
            ->willReturn($this->dummyMoneyObject('1'));

        $event->method('getAmount')
            ->willReturn($this->dummyMoneyObject('2'));

        return $event;
    }


    private function mockOrder(): Order
    {
        $order = $this->createMock(Order::class);

        $order->method('getMaker')
            ->willReturn($this->mockUser());


        $order->method('getMarket')->willReturn($this->mockMarket());
        $order->method('getSide')->willReturn(2); // Order::SIDE_BUY

        return $order;
    }

    private function mockUser(?Profile $profile = null): User
    {
        $user = $this->createMock(User::class);
        $user->method('getTokens')->willReturn([]);
        $user->method('getProfile')->willReturn($profile ?? $this->mockProfile());

        return $user;
    }


    private function mockMarket(): Market
    {
        $market = $this->createMock(Market::class);
        $market->method('getQuote')->willReturn($this->mockToken());

        return $market;
    }

    private function mockMarketHandler(): MarketHandler
    {
        $marketHandler = $this->createMock(MarketHandler::class);

        $marketHandler->method('getAllPendingSellOrders')->willReturn([]);
        $marketHandler->method('getSellOrdersSummaryByUser')->willReturn([]);

        return $marketHandler;
    }

    private function mockScheduledNotificationManager(): ScheduledNotificationManager
    {
        $scheduledNotificationManager = $this->createMock(ScheduledNotificationManager::class);

        $scheduledNotificationManager->expects($this->once())->method('createScheduledNotification');

        return $scheduledNotificationManager;
    }

    private function mockUserNotificationManager(): UserNotificationManager
    {
        return $this->createMock(UserNotificationManager::class);
    }

    private function mockMailer(): Mailer
    {
        return $this->createMock(Mailer::class);
    }

    private function mockToken(): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getProfile')->willReturn($this->mockProfile());

        return $token;
    }

    private function dummyMoneyObject(string $amount = '1', string $symbol = 'TOK'): Money
    {
        return new Money($amount, new Currency($symbol));
    }

    private function mockProfile(): Profile
    {
        $profile = $this->createMock(Profile::class);
        $profile->method('getUser')->willReturn($this->mockUser($profile));

        return $profile;
    }
}
