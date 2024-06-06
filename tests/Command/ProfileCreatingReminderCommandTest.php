<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\ProfileCreatingReminderCommand;
use App\Entity\Profile;
use App\Mailer\MailerInterface;
use App\Manager\ProfileManagerInterface;
use App\Utils\LockFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Lock\LockInterface;

class ProfileCreatingReminderCommandTest extends KernelTestCase
{
    /** @dataProvider executeDataProvider */
    public function testExecute(
        bool $isLockAcquired,
        bool $isNextReminderDateExist,
        \DateTime $nextReminderDate,
        string $expected,
        int $statusCode
    ): void {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(
            new ProfileCreatingReminderCommand(
                $this->mockEntityManager($isLockAcquired),
                $this->mockMailer($isLockAcquired),
                $this->mockProfileManager($nextReminderDate, $isNextReminderDateExist, $isLockAcquired),
                $this->mockLockFactory($isLockAcquired),
            )
        );

        $command = $application->find('app:profile-creating-reminder');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertStringContainsString($expected, $commandTester->getDisplay());
        $this->assertEquals($statusCode, $commandTester->getStatusCode());
    }

    public function executeDataProvider(): array
    {
        return  [
            'lock acquired equals false' => [
                'isLockAcquired' => false,
                'isNextReminderDateExist' => false,
                'nextReminderDate' => new \DateTime('+1 month'),
                'expected' => '',
                'statusCode' => 0,
            ],
            'lock acquired equals true and next reminder date is not set' => [
                'isLockAcquired' => true,
                'isNextReminderDateExist' => false,
                'nextReminderDate' => new \DateTime('+1 month'),
                'expected' => 'Done.',
                'statusCode' => 0,
            ],
            'lock acquired equals true and next reminder date is set' => [
                'isLockAcquired' => true,
                'isNextReminderDateSExist' => true,
                'nextReminderDate' => new \DateTime('+12 months'),
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
            ->method('sendProfileFillingReminderMail');

        return $mailer;
    }

    private function mockProfileManager(
        \DateTime $nextReminderDate,
        bool $isNextReminderDateExist,
        bool $isLockAcquired
    ): ProfileManagerInterface {
        $profileManager = $this->createMock(ProfileManagerInterface::class);
        $profileManager
            ->expects($isLockAcquired ? $this->once() : $this->never())
            ->method('findAllProfileWithEmptyDescriptionAndNotAnonymous')
            ->willReturn([$this->mockProfile($nextReminderDate, $isNextReminderDateExist, $isLockAcquired)]);

        return $profileManager;
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

    private function mockProfile(
        \DateTime $nextReminderDate,
        bool $isNextReminderDateExist,
        bool $isLockAcquired
    ): Profile {
        $profile = $this->createMock(Profile::class);
        $profile
            ->expects($isLockAcquired ? $this->once() : $this->never())
            ->method('setNextReminderDate')
            ->willReturnCallback(function (\DateTime $date) use ($profile, $nextReminderDate): Profile {
                $this->assertEquals($nextReminderDate->format('Y-m-d'), $date->format('Y-m-d'));

                return $profile;
            });
        $profile
            ->method('getNextReminderDate')
            ->willReturn($isNextReminderDateExist ? new \DateTime() : null);
        $profile
            ->method('getNumberOfReminder')
            ->willReturn(5);

        return $profile;
    }
}
