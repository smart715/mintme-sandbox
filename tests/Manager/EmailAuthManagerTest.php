<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\User;
use App\Manager\EmailAuthManager;
use App\Utils\DateTime;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailAuthManagerTest extends TestCase
{
    public function testCheckCode(): void
    {
        $code = '123';

        $user = $this->mockUser($code, true);
        $user->expects($this->once())->method('getEmailAuthCode');
        $user->expects($this->once())->method('getEmailAuthCodeExpirationTime');

        $manager = new EmailAuthManager($this->mockEntityManager());
        $this->assertTrue($manager->checkCode($user, $code)->getResult());

        $response = $manager->checkCode($this->mockUser($code, true), '321');
        $this->assertFalse($response->getResult());
        $this->assertEquals(EmailAuthManager::INVALID_CODE, $response->getMessage());

        $response = $manager->checkCode($this->mockUser($code, false), $code);
        $this->assertFalse($response->getResult());
        $this->assertEquals(EmailAuthManager::EXPIRED_CODE, $response->getMessage());
    }

    public function testGenerateCode(): void
    {
        $user = $this->mockUser('123', true);
        $user->expects($this->once())->method('setEmailAuthCode');
        $user->expects($this->once())->method('setEmailAuthCodeExpirationTime');

        $entityManager = $this->mockEntityManager();
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($user);

        $entityManager
            ->expects($this->once())
            ->method('flush');

        $manager = new EmailAuthManager($entityManager);

        $result = $manager->generateCode($user, 1);

        $this->assertEquals(strlen($result), 64);
    }

    /** @return User|MockObject */
    private function mockUser(string $code, bool $increase): User
    {
        $time = (new DateTime())->now();
        $interval = new DateInterval('PT10M');
        
        /** @var User|MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('getEmailAuthCode')->willReturn($code);
        $time = $increase
            ? $time->add($interval)
            : $time->sub($interval);
        $user->method('getEmailAuthCodeExpirationTime')->willReturn($time);

        return $user;
    }

    /** @return EntityManagerInterface|MockObject */
    private function mockEntityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        return $entityManager;
    }
}
