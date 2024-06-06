<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\User;
use App\Manager\UserNotificationConfigManagerInterface as ConfigManager;
use App\Manager\UserNotificationManager;
use App\Repository\BroadcastNotificationRepository;
use App\Repository\UserNotificationRepository;
use App\Utils\NotificationTypes;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserNotificationManagerTest extends TestCase
{
    public function testCreateNotification(): void
    {
        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->mockEntityManager();
        $entityManager
            ->expects($this->once())
            ->method('persist');

        $entityManager
            ->expects($this->once())
            ->method('flush');

        $user = $this->mockUser();

        $manager = new UserNotificationManager(
            $entityManager,
            $this->mockUserNotificationRepository(),
            $this->mockConfigManager(),
            $this->mockBroadcastNotificationRepository()
        );

        $manager->createNotification($user, 'TEST', []);
    }

    public function testGetNotifications(): void
    {
        $user = $this->mockUser();
        $notificationLimit = 1;

        /** @var UserNotificationRepository|MockObject $repository*/
        $repository = $this->mockUserNotificationRepository();
        $repository
            ->expects($this->once())
            ->method('findUserNotifications')
            ->with($user)
            ->willReturn([]);

        $entityManager = $this->mockEntityManager();
        $configManager = $this->mockConfigManager();

        $manager = new UserNotificationManager(
            $entityManager,
            $repository,
            $configManager,
            $this->mockBroadcastNotificationRepository()
        );

        $manager->getNotifications($user, $notificationLimit);
    }

    public function testUpdateNotifications(): void
    {
        $user = $this->mockUser();

        /** @var UserNotificationRepository|MockObject $repository */
        $repository = $this->mockUserNotificationRepository();

        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->mockEntityManager();
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($user);
        $entityManager
            ->expects($this->once())
            ->method('flush');

        $configManager = $this->mockConfigManager();

        $manager = new UserNotificationManager(
            $entityManager,
            $repository,
            $configManager,
            $this->mockBroadcastNotificationRepository()
        );

        $manager->updateNotifications($user);
    }

    /**
     * @dataProvider isNotificationAvailableDataProvider
     */
    public function testIsNotificationAvailable(
        string $type,
        array $config,
        bool $expected
    ): void {

        $user = $this->mockUser();

        $repository = $this->mockUserNotificationRepository();

        $entityManager = $this->mockEntityManager();

        /** @var ConfigManager|MockObject $configManager */
        $configManager = $this->mockConfigManager();

        if ($config) {
            $configManager
            ->expects($this->once())
            ->method('getOneUserNotificationConfig')
            ->willReturn($config);
        }

        $manager = new UserNotificationManager(
            $entityManager,
            $repository,
            $configManager,
            $this->mockBroadcastNotificationRepository()
        );

        $result = $manager->isNotificationAvailable(
            $user,
            $type,
            'channel'
        );

        $this->assertEquals($expected, $result);
    }

    public function isNotificationAvailableDataProvider(): array
    {
        return [
            'Return true if order type is filled' => [
                'type' => NotificationTypes::ORDER_FILLED,
                'config' => [],
                'expected' => true,
            ],
            'Return true if order type is cancelled' => [
                'type' => NotificationTypes::ORDER_CANCELLED,
                'config' => [],
                'expected' => true,
            ],
            'Return true if getOneUserNotificationConfig return data' => [
                'type' => 'no-type',
                'config' => ['a','b','c'],
                'expected' => true,
            ],
            'Return false if getOneUserNotificationConfig return empty array' => [
                'type' => 'no-type',
                'config' => [],
                'expected' => false,
            ],
        ];
    }

    private function mockEntityManager(): EntityManagerInterface
    {
        return $this->createMock(EntityManagerInterface::class);
    }

    private function mockConfigManager(): ConfigManager
    {
        return $this->createMock(ConfigManager::class);
    }

    private function mockUserNotificationRepository(): UserNotificationRepository
    {
        return $this->createMock(UserNotificationRepository::class);
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    private function mockBroadcastNotificationRepository(): BroadcastNotificationRepository
    {
        return $this->createMock(BroadcastNotificationRepository::class);
    }
}
