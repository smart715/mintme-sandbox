<?php declare(strict_types = 1);

namespace App\Tests\Notifications\Strategy;

use App\Entity\User;
use App\Notifications\Strategy\NotificationContext;
use App\Notifications\Strategy\NotificationStrategyInterface;
use PHPUnit\Framework\TestCase;

class NotificationContextTest extends TestCase
{
    private notificationContext $notificationContext;

    public function setUp(): void
    {
        $this->notificationContext = new NotificationContext($this->mockNotificationStrategy());
    }

    public function testSendNotification(): void
    {
        $this->notificationContext->sendNotification($this->mockUser());
    }

    private function mockNotificationStrategy(): NotificationStrategyInterface
    {
        $notification = $this->createMock(NotificationStrategyInterface::class);
        $notification->expects($this->once())->method('sendNotification');

        return $notification;
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }
}
