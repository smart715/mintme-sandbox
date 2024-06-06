<?php declare(strict_types = 1);

namespace App\Tests\EventSubscriber;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Events\TokenEventInterface;
use App\Events\TokenEvents;
use App\EventSubscriber\TokenCreatedSubscriber;
use App\Manager\ScheduledNotificationManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class TokenCreatedSubscriberTest extends TestCase
{
    private EventDispatcher $dispatcher;

    public function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
    }

    /** @dataProvider handleEventNameProvider */
    public function testHandleEvent(string $eventName): void
    {
        $subscriber = new TokenCreatedSubscriber(
            $this->mockScheduledNotificationManager()
        );

        $this->dispatcher->addSubscriber($subscriber);

        $event = $this->mockTokenEvent();

        $this->dispatcher->dispatch($event, $eventName);
    }

    public function handleEventNameProvider(): array
    {
        return [
            'token.created event' => [TokenEvents::CREATED],
        ];
    }

    private function mockTokenEvent(): TokenEventInterface
    {
        $tokenEvent = $this->createMock(TokenEventInterface::class);
        $tokenEvent->expects($this->once())->method('getToken')->willReturn($this->mockToken());

        return $tokenEvent;
    }

    private function mockScheduledNotificationManager(): ScheduledNotificationManagerInterface
    {
        $scheduledNotificationManager = $this->createMock(ScheduledNotificationManagerInterface::class);
        $scheduledNotificationManager->expects($this->once())
            ->method('createScheduledNotification');

        return $scheduledNotificationManager;
    }

    private function mockToken(): Token
    {
        $token = $this->createMock(Token::class);
        $token->expects($this->once())->method('getOwner')->willReturn($this->mockUser());

        return $token;
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }
}
