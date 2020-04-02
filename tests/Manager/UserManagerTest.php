<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\User;
use App\Manager\UserManager;
use App\Repository\UserRepository;
use Doctrine\Common\Persistence\ObjectManager;
use FOS\UserBundle\Util\CanonicalFieldsUpdater;
use FOS\UserBundle\Util\PasswordUpdaterInterface;
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
