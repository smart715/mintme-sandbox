<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\ScheduledNotification;
use App\Entity\User;
use App\Manager\ScheduledNotificationManager;
use App\Repository\ScheduledNotificationRepository;
use App\Utils\NotificationTypes;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ScheduledNotificationManagerTest extends TestCase
{
    public function testGetScheduledNotifications(): void
    {
        $scheduledNotification = $this->mockScheduledNotification();

        $notificationRepository = $this->mockNotificationRepository();
        $notificationRepository
            ->expects($this->exactly(2))
            ->method('findAll')
            ->willReturnOnConsecutiveCalls([$scheduledNotification], null);

        $scheduledNotificationManager = new ScheduledNotificationManager(
            $this->mockEntityManager(),
            $notificationRepository
        );

        $this->assertEquals([$scheduledNotification], $scheduledNotificationManager->getScheduledNotifications());
        $this->assertNull($scheduledNotificationManager->getScheduledNotifications());
    }

    /**
     * @dataProvider createScheduledNotificationDataProvider
     */
    public function testCreateScheduledNotification(
        array $notificationTypes,
        array $repositoryData,
        array $entityManagerData,
        bool $flush,
        string $notificationType,
        string $expected
    ): void {
        $user = $this->mockUser();
        $user
            ->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $notifications = [];

        foreach ($notificationTypes as $type) {
            $notification = $this->mockScheduledNotification();
            $notification
                ->expects($this->exactly($type['methodTimes']['getType']))
                ->method('getType')
                ->willReturn($type['type']);

            $notification
                ->expects($this->exactly($type['methodTimes']['getId']))
                ->method('getId')
                ->willReturn($type['getIdReturns']);

            array_push($notifications, $notification);
        }

        $notificationRepository = $this->mockNotificationRepository();
        $notificationRepository
            ->expects($this->exactly($repositoryData['findBy']))
            ->method('findBy')
            ->willReturn($notifications);

        $notificationRepository
            ->expects($this->exactly($repositoryData['deleteScheduledNotification']['times']))
            ->method('deleteScheduledNotification')
            ->with($repositoryData['deleteScheduledNotification']['with']);

        $entityManager = $this->mockEntityManager();
        $entityManager
            ->expects($this->exactly($entityManagerData['persist']))
            ->method('persist');

        $entityManager
            ->expects($this->exactly($entityManagerData['flush']))
            ->method('flush');

        $scheduledNotificationManager = new ScheduledNotificationManager(
            $entityManager,
            $notificationRepository
        );

        $scheduledNotificationManager->{$notificationType.'_intervals'} = [1,2];

        $actual = $scheduledNotificationManager->createScheduledNotification($notificationType, $user, $flush);

        $this->assertTrue($actual instanceof ScheduledNotification);
        $this->assertEquals($expected, $actual->getType());
    }

    public function testUpdateScheduledNotification(): void
    {
        $notificationRepository = $this->mockNotificationRepository();

        $entityManager = $this->mockEntityManager();
        $entityManager
            ->expects($this->once())
            ->method('persist');

        $entityManager
            ->expects($this->once())
            ->method('flush');

        $notification = $this->mockScheduledNotification();

        $notification
            ->expects($this->once())
            ->method('setDateToBeSend');

        $notification
            ->expects($this->once())
            ->method('setTimeInterval')
            ->willReturn($notification);

        $scheduledNotificationManager = new ScheduledNotificationManager(
            $entityManager,
            $notificationRepository
        );

        $scheduledNotificationManager->updateScheduledNotification(
            $notification,
            '1',
            (new \DateTimeImmutable())
        );
    }

    public function testRemoveByTypeForUser(): void
    {
        $user = $this->mockUser();
        $type = 'cancelled';

        $notificationRepository = $this->mockNotificationRepository();
        $notificationRepository
            ->expects($this->once())
            ->method('removeByTypeForUser')
            ->with($type, $user)
            ->willReturn(1);

        $entityManager = $this->mockEntityManager();

        $scheduledNotificationManager = new ScheduledNotificationManager(
            $entityManager,
            $notificationRepository
        );

        $this->assertEquals(1, $scheduledNotificationManager->removeByTypeForUser($type, $user));
    }

    public function createScheduledNotificationDataProvider(): array
    {
        return [
            [
                'notificationTypes' => [
                    [
                        'type' => NotificationTypes::ORDER_FILLED,
                        'getIdReturns' => 1,
                        'methodTimes' => ['getType' => 1, 'getId' => 1],
                    ],
                    [
                        'type' => NotificationTypes::ORDER_CANCELLED,
                        'getIdReturns' => 2,
                        'methodTimes' => ['getType' => 1, 'getId' => 0],
                    ],
                ],
                'repositoryData' => [
                    'findBy' => 1,
                    'deleteScheduledNotification' => ['times' => 1, 'with' => 1],
                ],
                'entityManagerData' => [
                    'persist' => 1,
                    'flush' => 1,
                ],
                'flush' => true,
                'notificationType' => NotificationTypes::ORDER_FILLED,
                'expected' => NotificationTypes::ORDER_FILLED,
            ],
            [
                'notificationTypes' => [
                    [
                        'type' => NotificationTypes::ORDER_CANCELLED,
                        'getIdReturns' => 1,
                        'methodTimes' => ['getType' => 1, 'getId' => 0],
                    ],
                    [
                        'type' => NotificationTypes::ORDER_FILLED,
                        'getIdReturns' => 2,
                        'methodTimes' => ['getType' => 1, 'getId' => 1],
                    ],
                ],
                'repositoryData' => [
                    'findBy' => 1,
                    'deleteScheduledNotification' => ['times' => 1, 'with' => 2],
                ],
                'entityManagerData' => [
                    'persist' => 1,
                    'flush' => 1,
                ],
                'flush' => true,
                'notificationType' => NotificationTypes::ORDER_FILLED,
                'expected' => NotificationTypes::ORDER_FILLED,
            ],
            [
                'notificationTypes' => [
                    [
                        'type' => NotificationTypes::ORDER_CANCELLED,
                        'getIdReturns' => 1,
                        'methodTimes' => ['getType' => 1, 'getId' => 0],
                    ],
                    [
                        'type' => NotificationTypes::ORDER_FILLED,
                        'getIdReturns' => 1,
                        'methodTimes' => ['getType' => 1, 'getId' => 0],
                    ],
                ],
                'repositoryData' => [
                    'findBy' => 1,
                    'deleteScheduledNotification' => ['times' => 0, 'with' => null],
                ],
                'entityManagerData' => [
                    'persist' => 1,
                    'flush' => 1,
                ],
                'flush' => true,
                'notificationType' => NotificationTypes::TOKEN_MARKETING_TIPS,
                'expected' => NotificationTypes::TOKEN_MARKETING_TIPS,
            ],
            [
                'notificationTypes' => [
                    [
                        'type' => NotificationTypes::ORDER_CANCELLED,
                        'getIdReturns' => 1,
                        'methodTimes' => ['getType' => 1, 'getId' => 0],
                    ],
                    [
                        'type' => NotificationTypes::ORDER_FILLED,
                        'getIdReturns' => 2,
                        'methodTimes' => ['getType' => 1, 'getId' => 0],
                    ],
                ],
                'repositoryData' => [
                    'findBy' => 1,
                    'deleteScheduledNotification' => ['times' => 0, 'with' => null],
                ],
                'entityManagerData' => [
                    'persist' => 0,
                    'flush' => 0,
                ],
                'flush' => false,
                'notificationType' => NotificationTypes::TOKEN_MARKETING_TIPS,
                'expected' => NotificationTypes::TOKEN_MARKETING_TIPS,
            ],
        ];
    }

    /** @return EntityManagerInterface|MockObject */
    private function mockEntityManager(): EntityManagerInterface
    {
        return $this->createMock(EntityManagerInterface::class);
    }

    /** @return ScheduledNotificationRepository|MockObject */
    private function mockNotificationRepository(): ScheduledNotificationRepository
    {
        return $this->createMock(ScheduledNotificationRepository::class);
    }

    /** @return User|MockObject */
    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    /** @return ScheduledNotification|MockObject */
    private function mockScheduledNotification(): ScheduledNotification
    {
        return $this->createMock(ScheduledNotification::class);
    }
}
