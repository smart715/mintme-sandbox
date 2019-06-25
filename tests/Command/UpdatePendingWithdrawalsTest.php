<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\UpdatePendingWithdrawals;
use App\Entity\Crypto;
use App\Entity\PendingWithdraw;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Repository\PendingWithdrawRepository;
use App\Utils\DateTime;
use App\Wallet\Model\Amount;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class UpdatePendingWithdrawalsTest extends KernelTestCase
{
    public function testExecute(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $lockCount = 10;

        $handler = $this->createMock(BalanceHandlerInterface::class);
        $handler->expects($this->exactly($lockCount))
            ->method('deposit');

        $application->add(new UpdatePendingWithdrawals(
            $this->createMock(LoggerInterface::class),
            $this->mockEm($lockCount),
            $this->mockDate(new DateTimeImmutable()),
            $handler
        ));

        $command = $application->find('app:update-pending-withdrawals');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->getDisplay();
    }

    public function testExecuteWithException(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $lockCount = 10;

        $handler = $this->createMock(BalanceHandlerInterface::class);
        $handler->expects($this->exactly($lockCount))
            ->method('deposit')
            ->willThrowException(new Exception());

        $em = $this->mockEm($lockCount);
        $em->expects($this->exactly($lockCount))->method('rollback');

        $application->add(new UpdatePendingWithdrawals(
            $this->createMock(LoggerInterface::class),
            $em,
            $this->mockDate(new DateTimeImmutable()),
            $handler
        ));

        $command = $application->find('app:update-pending-withdrawals');
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);
        $commandTester->getDisplay();
    }

    private function mockDate(DateTimeImmutable $dateTimeImmutable): DateTime
    {
        $date = $this->createMock(DateTime::class);
        $date->method('now')->willReturn($dateTimeImmutable);

        return $date;
    }

    /** @return EntityManagerInterface|MockObject */
    private function mockEm(int $lockCount): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $repo = $this->createMock(PendingWithdrawRepository::class);
        $repo->expects($this->once())
            ->method('findAll')
            ->willReturn(array_map(function () {
                return $this->mockPending();
            }, range(1, $lockCount)));

        $em->method('getRepository')->willReturn($repo);

        return $em;
    }

    private function mockPending(): PendingWithdraw
    {
        $lock = $this->createMock(PendingWithdraw::class);

        $lock->method('getDate')->willReturn(new DateTimeImmutable('now - 1 day'));
        $lock->method('getUser')->willReturn($this->createMock(User::class));

        $amount = $this->createMock(Amount::class);
        $amount->method('getAmount')->willReturn(new Money(1, new Currency('FOO')));

        $lock->method('getAmount')->willReturn($amount);

        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getFee')->willReturn(new Money(1, new Currency('FOO')));

        $lock->method('getCrypto')->willReturn($crypto);

        return $lock;
    }
}
