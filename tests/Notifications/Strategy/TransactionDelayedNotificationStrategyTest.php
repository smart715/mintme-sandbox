<?php declare(strict_types = 1);

namespace App\Tests\Notifications\Strategy;

use App\Entity\User;
use App\Manager\UserNotificationManagerInterface;
use App\Notifications\Strategy\TransactionDelayedNotificationStrategy;
use App\Utils\NotificationChannels;
use App\Utils\NotificationTypes;
use App\Wallet\Model\Type;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TransactionDelayedNotificationStrategyTest extends TestCase
{
    public function testGetParentNotificationType(): void
    {
        $type = $this->mockType();
        $type
            ->method('getTypeCode')
            ->willReturnOnConsecutiveCalls(Type::DEPOSIT, Type::WITHDRAW);

        $notificationStrategy = new TransactionDelayedNotificationStrategy(
            $this->mockUserNotificationManager(),
            $type
        );

        $this->assertEquals(NotificationTypes::DEPOSIT, $notificationStrategy->getParentNotificationType());
        $this->assertEquals(NotificationTypes::WITHDRAWAL, $notificationStrategy->getParentNotificationType());
    }

    /** @dataProvider sendNotificationDataProvider */
    public function testSendNotification(bool $isAvailable, string $typeCode): void
    {
        $user = $this->createMock(User::class);

        $type = $this->mockType();
        $type
            ->method('getTypeCode')
            ->willReturn($typeCode);

        $userNotificationManager = $this->mockUserNotificationManager();

        $notificationStrategy = new TransactionDelayedNotificationStrategy(
            $userNotificationManager,
            $type
        );

        $userNotificationManager
            ->method('isNotificationAvailable')
            ->with($user, $notificationStrategy->getParentNotificationType(), NotificationChannels::WEBSITE)
            ->willReturn($isAvailable);

        $userNotificationManager
            ->expects($isAvailable ? $this->once() : $this->never())
            ->method('createNotification')
            ->with($user, NotificationTypes::TRANSACTION_DELAYED, ['type' => $type->getTypeCode()]);

        $notificationStrategy->sendNotification($user);
    }

    public function sendNotificationDataProvider(): array
    {
        return [
            'send notification when it is available and notification type is deposit' => [
                'isAvailable' => true,
                'typeCode' => Type::DEPOSIT,
            ],
            'send notification when it is available and notification type is withdrawal' => [
                'isAvailable' => true,
                'typeCode' => Type::WITHDRAW,
            ],
            'do not send notification when it is not available and notification type is deposit' => [
                'isAvailable' => false,
                'typeCode' => Type::DEPOSIT,
            ],
            'do not send notification when it is not available and notification type is withdrawal' => [
                'isAvailable' => false,
                'typeCode' => Type::WITHDRAW,
            ],
        ];
    }

    /** @return MockObject|UserNotificationManagerInterface*/
    private function mockUserNotificationManager(): UserNotificationManagerInterface
    {
        return $this->createMock(UserNotificationManagerInterface::class);
    }

    /** @return MockObject|Type */
    private function mockType(): Type
    {
        return $this->createMock(Type::class);
    }
}
