<?php declare(strict_types = 1);

namespace App\Tests\Notifications\Strategy;

use App\Entity\User;
use App\Manager\UserNotificationManagerInterface;
use App\Notifications\Strategy\WithdrawalNotificationStrategy;
use App\Utils\NotificationChannels;
use PHPUnit\Framework\TestCase;

class WithdrawalNotificationStrategyTest extends TestCase
{
    private const TYPE = 'TEST';
    
    public function testSendNotificationWhenItsAvailable(): void
    {
        $user = $this->createMock(User::class);
        $channel = NotificationChannels::WEBSITE;
        $notificationStrategy = new WithdrawalNotificationStrategy(
            $this->mockUserNotificationManager(true, [$user, self::TYPE, $channel]),
            self::TYPE
        );

        $notificationStrategy->sendNotification($user);
    }

    public function testSendNotificationWhenItsNotAvailable(): void
    {
        $user = $this->createMock(User::class);
        $channel = NotificationChannels::WEBSITE;
        $notificationStrategy = new WithdrawalNotificationStrategy(
            $this->mockUserNotificationManager(false, [$user, self::TYPE, $channel]),
            self::TYPE
        );

        $notificationStrategy->sendNotification($user);
    }

    private function mockUserNotificationManager(bool $isAvailable, array $data): UserNotificationManagerInterface
    {
        [$user, $type, $channel] = $data;

        $notificationManager = $this->createMock(UserNotificationManagerInterface::class);
        $notificationManager->method('isNotificationAvailable')
            ->with($user, $type, $channel)
            ->willReturn($isAvailable);

        $notificationManager->expects($isAvailable ? $this->once() : $this->never())
            ->method('createNotification')
            ->with($user, $type, null);

        return $notificationManager;
    }
}
