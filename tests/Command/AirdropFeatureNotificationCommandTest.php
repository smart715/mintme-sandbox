<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\AirdropFeatureNotificationCommand;
use App\Entity\ScheduledNotification;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Manager\ScheduledNotificationManagerInterface;
use App\Manager\TokenManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class AirdropFeatureNotificationCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;

    public function setUp(): void
    {
        $kernel = self::bootKernel();
        $app = new Application($kernel);
        $app->add(new AirdropFeatureNotificationCommand(
            $this->mockEntityManager(),
            $this->mockTokenManager(),
            $this->mockScheduledNotificationManager(
                $this->mockScheduledNotification()
            )
        ));

        $command = $app->find('app:airdrop:notification');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecute(): void
    {
        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString("Finished", $output);
    }

    private function mockEntityManager(): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(2))->method('persist');
        $em->expects($this->once())->method('flush');

        return $em;
    }

    private function mockTokenManager(): TokenManagerInterface
    {
        $tokenManager = $this->createMock(TokenManagerInterface::class);
        $tokenManager
            ->expects($this->once())
            ->method('getTokensWithoutAirdrops')
            ->willReturn([$this->mockToken(), $this->mockToken()]);

        return $tokenManager;
    }

    private function mockToken(): Token
    {
        $token = $this->createMock(Token::class);
        $token
            ->expects($this->once())
            ->method('getOwner')
            ->willReturn($this->mockUser());

        $token
            ->expects($this->once())
            ->method('getCreated')
            ->willReturn(new \DateTimeImmutable());

        return $token;
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    private function mockScheduledNotification(): ScheduledNotification
    {
        $scheduledNotification = $this->createMock(ScheduledNotification::class);
        $scheduledNotification
            ->expects($this->exactly(2))
            ->method('getTimeInterval')
            ->willReturn('1 day');

        return $scheduledNotification;
    }

    private function mockScheduledNotificationManager(
        ScheduledNotification $scheduledNotification
    ): ScheduledNotificationManagerInterface {
        $scheduledNotificationManager = $this->createMock(
            ScheduledNotificationManagerInterface::class
        );

        $scheduledNotificationManager
            ->expects($this->exactly(2))
            ->method('createScheduledNotification')
            ->willReturn($scheduledNotification);

        return $scheduledNotificationManager;
    }
}
