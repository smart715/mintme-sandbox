<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\TokenDescriptionReminderCommand;
use App\Entity\Token\Token;
use App\Mailer\MailerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\LockFactory;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockInterface;

class TokenDescriptionReminderCommandTest extends KernelTestCase
{
    /** @dataProvider executeDataProvider */
    public function testExecute(
        bool $isLockAcquired,
        bool $isNextReminderDateExist,
        \DateTimeImmutable $nextReminderDate,
        string $expected,
        int $statusCode
    ): void {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new TokenDescriptionReminderCommand(
                $this->mockEntityManager($isLockAcquired),
                $this->mockMailer($isLockAcquired),
                $this->mockTokenManager($nextReminderDate, $isNextReminderDateExist, $isLockAcquired),
                $this->mockLockFactory($isLockAcquired),
            )
        );

        $command = $application->find('app:token-description-reminder');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
        $this->assertEquals($statusCode, $commandTester->getStatusCode());
    }

    public function executeDataProvider(): array
    {
        $nextReminderDate = new \DateTimeImmutable();

        return  [
            'lock acquired equals false will return an empty message and status code equals 0' => [
                'isLockAcquired' => false,
                'isNextReminderDateExist' => false,
                'nextReminderDate' => $nextReminderDate,
                'expected' => '',
                'statusCode' => 0,
            ],
            'lock acquired equals true and next reminder date is not set will return a success and status code equals 0' => [
                'isLockAcquired' => true,
                'isNextReminderDateExist' => false,
                'nextReminderDate' => $nextReminderDate->modify('1 month')->setTime(0, 0),
                'expected' => 'Done.',
                'statusCode' => 0,
            ],
            'lock acquired equals true and next reminder date is set will return a success and status code equals 0' => [
                'isLockAcquired' => true,
                'isNextReminderDateSExist' => true,
                'nextReminderDate' => $nextReminderDate->modify('+12 months')->setTime(0, 0),
                'expected' => 'Done.',
                'statusCode' => 0,
            ],
        ];
    }

    private function mockMailer(bool $isLockAcquired): MailerInterface
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects($isLockAcquired ? $this->once() : $this->never())
            ->method('sendTokenDescriptionReminderMail');

        return $mailer;
    }

    private function mockTokenManager(
        \DateTimeImmutable $nextReminderDate,
        bool $isNextReminderDateExist,
        bool $isLockAcquired
    ): TokenManagerInterface {
        $tokenManager = $this->createMock(TokenManagerInterface::class);
        $tokenManager
            ->expects($isLockAcquired ? $this->once() : $this->never())
            ->method('findAllTokensWithEmptyDescription')
            ->willReturn([$this->mockToken($nextReminderDate, $isNextReminderDateExist, $isLockAcquired)]);

        return $tokenManager;
    }

    private function mockLock(bool $isLockAcquired): LockInterface
    {
        $lock = $this->createMock(LockInterface::class);
        $lock
            ->method('acquire')
            ->wilLReturn($isLockAcquired);
        $lock
            ->expects($isLockAcquired ? $this->once() : $this->never())
            ->method('release');

        return $lock;
    }

    private function mockLockFactory(bool $isLockAcquired): LockFactory
    {
        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory
            ->method('createLock')
            ->willReturn($this->mockLock($isLockAcquired));

        return $lockFactory;
    }

    private function mockEntityManager(bool $isLockAcquired): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($isLockAcquired ? $this->once() : $this->never())
            ->method('persist');
        $entityManager
            ->expects($isLockAcquired ? $this->once() : $this->never())
            ->method('flush');

        return $entityManager;
    }

    private function mockToken(
        \DateTimeImmutable $nextReminderDate,
        bool $isNextReminderDateExist,
        bool $isLockAcquired
    ): Token {
        $token = $this->createMock(Token::class);
        $token
            ->expects($isLockAcquired ? $this->once() : $this->never())
            ->method('setNextReminderDate')
            ->with($nextReminderDate);
        $token
            ->method('getNextReminderDate')
            ->willReturn($isNextReminderDateExist ? new \DateTimeImmutable() : null);
        $token
            ->method('getNumberOfReminder')
            ->willReturn(5);

        return $token;
    }
}
