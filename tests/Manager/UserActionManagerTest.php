<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\User;
use App\Entity\UserAction;
use App\Manager\UserActionManager;
use App\Repository\UserActionRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserActionManagerTest extends TestCase
{
    public function testGetCountByUserAtDate(): void
    {
        $expectedCount = 1;
        $user = $this->mockUser();
        $action = 'sleeping';
        $date = new \DateTimeImmutable('2022-01-01');

        /** @var UserActionRepository|MockObject */
        $repository = $this->mockRepository();
        $repository
            ->expects($this->once())
            ->method('getCountByUserAtDate')
            ->with($user, $action, $date)
            ->willReturn(1);

        $entityManagerMock = $this->mockEntityManager();

        $manager = new UserActionManager(
            $repository,
            $entityManagerMock
        );

        $actualCount = $manager->getCountByUserAtDate(
            $user,
            $action,
            $date
        );
        $this->assertEquals($expectedCount, $actualCount);
    }

    public function testGetById(): void
    {
        $userActionId = 1;

        $userAction = $this->mockUserAction();

        /** @var UserActionRepository|MockObject */
        $repository = $this->mockRepository();
        $repository
            ->expects($this->once())
            ->method('find')
            ->with($userActionId)
            ->willReturn($userAction);

        $entityManagerMock = $this->mockEntityManager();

        $manager = new UserActionManager(
            $repository,
            $entityManagerMock
        );

        $actual = $manager->getById($userActionId);
        $this->assertEquals($userAction, $actual);
    }

    public function testCreateUserAction(): void
    {
        $user = $this->mockUser();
        $action = 'dancing';

        $repository = $this->mockRepository();

        /** @var EntityManagerInterface|MockObject */
        $entityManager = $this->mockEntityManager();
        $entityManager
            ->expects($this->once())
            ->method('persist');

        $entityManager
            ->expects($this->once())
            ->method('flush');

        $manager = new UserActionManager(
            $repository,
            $entityManager
        );

        $manager->createUserAction($user, $action);
    }

    public function testGetRepository(): void
    {
        $repository = $this->mockRepository();
        $entityManager = $this->mockEntityManager();

        $manager = new UserActionManager(
            $repository,
            $entityManager
        );

        $actual = $manager->getRepository();
        $this->assertEquals($repository, $actual);
    }

    private function mockRepository(): UserActionRepository
    {
        return $this->createMock(
            UserActionRepository::class
        );
    }

    private function mockEntityManager(): EntityManagerInterface
    {
        return $this->createMock(
            EntityManagerInterface::class
        );
    }

    private function mockUserAction(): UserAction
    {
        return $this->createMock(UserAction::class);
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }
}
