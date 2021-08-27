<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\UserManager;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use FOS\UserBundle\Util\CanonicalFieldsUpdater;
use FOS\UserBundle\Util\PasswordUpdaterInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

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
            'Foo',
            $this->mockCryptoManagerInterface(),
            $this->mockMailerInterface(),
            $this->mockEntityManagerInterface(),
            $this->mockTranslatorInterface()
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
            'Foo',
            $this->mockCryptoManagerInterface(),
            $this->mockMailerInterface(),
            $this->mockEntityManagerInterface(),
            $this->mockTranslatorInterface()
        );

        $this->assertEquals($user, $manager->findByReferralCode('foo'));
    }

    public function testFindByDiscordId(): void
    {
        $user = $this->mockUser();

        $manager = new UserManager(
            $this->mockPasswordUpdater(),
            $this->mockCanonicalFieldsUpdater(),
            $this->mockObjectManager(
                $this->mockRepository($user)
            ),
            'Foo',
            $this->mockCryptoManagerInterface(),
            $this->mockMailerInterface(),
            $this->mockEntityManagerInterface(),
            $this->mockTranslatorInterface()
        );

        $this->assertEquals($user, $manager->findByDiscordId(1));
    }

    private function mockPasswordUpdater(): PasswordUpdaterInterface
    {
        return $this->createMock(PasswordUpdaterInterface::class);
    }

    private function mockCanonicalFieldsUpdater(): CanonicalFieldsUpdater
    {
        return $this->createMock(CanonicalFieldsUpdater::class);
    }

    private function mockCryptoManagerInterface(): CryptoManagerInterface
    {
        return $this->createMock(CryptoManagerInterface::class);
    }

    private function mockMailerInterface(): MailerInterface
    {
        return $this->createMock(MailerInterface::class);
    }

    private function mockEntityManagerInterface(): EntityManagerInterface
    {
        return $this->createMock(EntityManagerInterface::class);
    }

    private function mockTranslatorInterface(): TranslatorInterface
    {
        return $this->createMock(TranslatorInterface::class);
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
