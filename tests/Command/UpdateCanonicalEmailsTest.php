<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Canonicalizer\EmailCanonicalizer;
use App\Command\UpdateCanonicalEmails;
use App\Entity\User;
use App\Manager\UserManagerInterface;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateCanonicalEmailsTest extends KernelTestCase
{
    /** @dataProvider executeDataProvider */
    public function testExecute(
        string $emailCanonical,
        bool $isCanonicalExist,
        string $expected,
        int $statusCode
    ): void {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new UpdateCanonicalEmails(
                $this->mockEntityManager($isCanonicalExist),
                $this->mockUserManager($emailCanonical),
                $this->mockEmailCanonicalizer($emailCanonical, $isCanonicalExist),
            )
        );

        $command = $application->find('app:update-canonical-emails');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
        $this->assertEquals($statusCode, $commandTester->getStatusCode());
    }
    public function executeDataProvider(): array
    {
        return [
            'email canonical does not exist' => [
                'emailCanonical' => '',
                'isCanonicalExist' => false,
                'expected' => 'All canonical fields was updated',
                'statusCode' => 0,
            ],
            'email canonical is set' => [
                'emailCanonical' => 'user@example.com',
                'isCanonicalExist' => true,
                'expected' => 'All canonical fields was updated',
                'statusCode' => 0,
            ],
        ];
    }

    private function mockEntityManager(bool $isCanonicalExist): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($isCanonicalExist ? $this->never() : $this->once())
            ->method('persist');
        $entityManager
            ->expects($this->once())
            ->method('flush');

        return $entityManager;
    }

    private function mockUserManager(string $emailCanonical): UserManagerInterface
    {
        $userManager = $this->createMock(UserManagerInterface::class);
        $userManager
            ->method('getRepository')
            ->willReturn($this->mockUserRepository($emailCanonical));

        return $userManager;
    }

    private function mockEmailCanonicalizer(
        string $emailCanonical,
        bool $isCanonicalExist
    ): EmailCanonicalizer {
        $user = $this->mockUser($emailCanonical);

        $emailCanonicalizer = $this->createMock(EmailCanonicalizer::class);
        $emailCanonicalizer
            ->expects($isCanonicalExist ? $this->exactly(1): $this->exactly(2))
            ->method('canonicalize')
            ->willReturn($user->getEmail());

        return $emailCanonicalizer;
    }

    private function mockUser(string $emailCanonical): User
    {
        $user = $this->createMock(User::class);
        $user
            ->method('getEmailCanonical')
            ->willReturn($emailCanonical);
        $user
            ->method('getEmail')
            ->willReturn('user@example.com');

        return $user;
    }

    private function mockUserRepository(string $emailCanonical): UserRepository
    {
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->method('findAll')
            ->willReturn([$this->mockUser($emailCanonical)]);

        return $userRepository;
    }
}
