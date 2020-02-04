<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\User;
use App\Manager\UserManager;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use FOS\UserBundle\Util\CanonicalFieldsUpdater;
use FOS\UserBundle\Util\PasswordUpdaterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    public function testFind(): void
    {
        $user = $this->mockUser();
        $manager = new UserManager(
            $this->mockPasswordUpdater(),
            $this->mockCanonicalFieldsUpdater(),
            $this->mockObjectManager(
                $this->mockRepository($user)
            ),
            'Foo'
        );

        $this->assertEquals($user, $manager->find(1));
    }

    public function testFindByReferralCode(): void
    {
        $user = $this->mockUser();
        $manager = new UserManager(
            $this->mockPasswordUpdater(),
            $this->mockCanonicalFieldsUpdater(),
            $this->mockObjectManager(
                $this->mockRepository($user)
            ),
            'Foo'
        );

        $this->assertEquals($user, $manager->findByReferralCode('foo'));
    }

    public function testGetTradersData(): void
    {
        $users = [1, 2, 3];
        $user = $this->mockUser();
        /** @var UserRepository|MockObject $userRepository */
        $userRepository = $this->mockRepository($user);

        $userRepository
            ->method('getTradersData')
            ->with($users)
            ->willReturn([
                [
                    'id' => 1,
                ],
                [
                    'id' => 2,
                ],
                [
                    'id' => 3,
                ],
            ]);

        $manager = new UserManager(
            $this->mockPasswordUpdater(),
            $this->mockCanonicalFieldsUpdater(),
            $this->mockObjectManager($userRepository),
            'Foo'
        );

        $tradersData = $manager->getTradersData($users);
        $this->assertNotEmpty($tradersData);
    }

    private function mockPasswordUpdater(): PasswordUpdaterInterface
    {
        return $this->createMock(PasswordUpdaterInterface::class);
    }

    private function mockCanonicalFieldsUpdater(): CanonicalFieldsUpdater
    {
        return $this->createMock(CanonicalFieldsUpdater::class);
    }

    private function mockObjectManager(UserRepository $repository): ObjectManager
    {
        $manager = $this->createMock(ObjectManager::class);
        $manager->method('getRepository')->willReturn($repository);

        return $manager;
    }

    private function mockRepository(?User $user): UserRepository
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->method('find')->willReturn($user);
        $repo->method('findOneBy')->willReturn($user);

        return $repo;
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }
}
