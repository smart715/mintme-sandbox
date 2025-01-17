<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\UpdatePendingWithdrawals;
use App\Entity\Crypto;
use App\Entity\PendingTokenWithdraw;
use App\Entity\PendingWithdraw;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Config\TokenConfig;
use App\Manager\CryptoManagerInterface;
use App\Repository\PendingTokenWithdrawRepository;
use App\Repository\PendingWithdrawRepository;
use App\Utils\DateTime;
use App\Utils\LockFactory;
use App\Utils\Symbols;
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
use Symfony\Component\Lock\LockInterface;

class UpdatePendingWithdrawalsTest extends KernelTestCase
{
    public function testExecute(): void
    {
        $kernel      = self::bootKernel();
        $application = new Application($kernel);
        $emCount     = 10;

        $handler = $this->createMock(BalanceHandlerInterface::class);
        $handler->expects($this->exactly($emCount * 4))
            ->method('deposit');

        $repoC = $this->createMock(CryptoManagerInterface::class);
        $repoC->expects($this->exactly(1))
            ->method('findBySymbol')
            ->willReturn($this->mockCrypto());

        $upw = new UpdatePendingWithdrawals(
            $this->createMock(LoggerInterface::class),
            $this->mockEm($emCount),
            $this->mockDate(new DateTimeImmutable()),
            $handler,
            $repoC,
            $this->mockLockFactory(),
            $this->createMock(TokenConfig::class)
        );

        $upw->withdrawExpirationTime = 1;
        $upw->viabtcResponseTimeout = 1;

        $application->add($upw);

        $command       = $application->find('app:update-pending-withdrawals');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->getDisplay();
    }

    public function testExecuteWithException(): void
    {
        $kernel      = self::bootKernel();
        $application = new Application($kernel);
        $emCount     = 10;

        $handler = $this->createMock(BalanceHandlerInterface::class);
        $handler->expects($this->exactly($emCount * 4))
            ->method('deposit')
            ->willThrowException(new Exception());

        $repoC = $this->createMock(CryptoManagerInterface::class);
        $repoC->expects($this->exactly(1))
            ->method('findBySymbol')
            ->willReturn($this->mockCrypto());

        $em = $this->mockEm($emCount);

        $upw = new UpdatePendingWithdrawals(
            $this->createMock(LoggerInterface::class),
            $em,
            $this->mockDate(new DateTimeImmutable()),
            $handler,
            $repoC,
            $this->mockLockFactory(),
            $this->createMock(TokenConfig::class)
        );

        $upw->withdrawExpirationTime = 1;
        $upw->viabtcResponseTimeout = 1;

        $application->add($upw);

        $command       = $application->find('app:update-pending-withdrawals');
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
        $repo->expects($this->exactly(1))
            ->method('findAll')
            ->willReturn(array_map(function () {
                return $this->mockPending();
            }, range(1, $lockCount)));

        $repoT = $this->createMock(PendingTokenWithdrawRepository::class);
        $repoT->expects($this->exactly(1))
            ->method('findAll')
            ->willReturn(array_map(function () {
                return $this->mockPendingToken();
            }, range(1, $lockCount)));

        $repoC = $this->createMock(CryptoManagerInterface::class);

        $em->method('getRepository')->willReturn($repo, $repoT, $repoC);

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

    private function mockPendingToken(): PendingTokenWithdraw
    {
        $lock = $this->createMock(PendingTokenWithdraw::class);

        $lock->method('getDate')->willReturn(new DateTimeImmutable('now - 1 day'));
        $lock->method('getUser')->willReturn($this->createMock(User::class));

        $amount = $this->createMock(Amount::class);
        $amount->method('getAmount')->willReturn(new Money(1, new Currency('FOO')));

        $lock->method('getAmount')->willReturn($amount);

        $token = $this->createMock(Token::class);

        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getFee')->willReturn(new Money(1, new Currency('FOO')));

        $token->method('getCrypto')->willReturn($crypto);
        $token->method('isMintmeToken')->willReturn(true);

        $lock->method('getToken')->willReturn($token);

        return $lock;
    }

    private function mockCrypto(): Crypto
    {
        $lock = $this->createMock(Crypto::class);

        $lock->method('getSymbol')->willReturn(Symbols::WEB);
        $lock->method('getName')->willReturn(Symbols::WEB);
        $lock->method('getFee')->willReturn(new Money('100', new Currency(Symbols::WEB)));

        return $lock;
    }

    private function mockLockFactory(): LockFactory
    {
        $lock = $this->createMock(LockInterface::class);
        $lock->method('acquire')->wilLReturn(true);

        $lf = $this->createMock(LockFactory::class);
        $lf->method('createLock')->willReturn($lock);

        return $lf;
    }
}
