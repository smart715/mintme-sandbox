<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\User;
use App\Manager\EmailAuthManager;
use App\Manager\Model\EmailAuthResultModel;
use App\Utils\DateTime;
use DateInterval;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class EmailAuthManagerTest extends TestCase
{
    public function testCheckCode(): void
    {
        $code = '123';
        $manager = new EmailAuthManager($this->mockEntityManager());
        $this->assertTrue($manager->checkCode($this->mockUser($code, true), $code)->getResult());

        $response = $manager->checkCode($this->mockUser($code, true), '321');
        $this->assertFalse($response->getResult());
        $this->assertEquals(EmailAuthManager::INVALID_CODE, $response->getMessage());

        $response = $manager->checkCode($this->mockUser($code, false), $code);
        $this->assertFalse($response->getResult());
        $this->assertEquals(EmailAuthManager::EXPIRED_CODE, $response->getMessage());
    }

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

    private function mockEntityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        return $entityManager;
    }
}
