<?php declare(strict_types = 1);

namespace App\Tests\EventSubscriber;

use App\Entity\Crypto;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Events\DepositCompletedEvent;
use App\Events\TransactionCompletedEvent;
use App\Events\WithdrawCompletedEvent;
use App\EventSubscriber\TransactionSubscriber;
use App\Mailer\MailerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Tests\Mocks\MockMoneyWrapper;
use App\Utils\Symbols;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TransactionSubscriberTest extends TestCase
{

    use MockMoneyWrapper;

    public function testSendTransactionCompletedMailWithCrypto(): void
    {
        $subscriber = new TransactionSubscriber(
            $this->mockMailer(true),
            $this->mockMoneyWrapper(),
            $this->mockLogger(),
            $this->mockEntityManager(),
            $this->mockUserNotificationManagerInterface()
        );

        $tradable = $this->createMock(Crypto::class);
        $tradable->method('getMoneySymbol')->willReturn('WEB');

        $subscriber->sendTransactionCompletedMail(
            $this->mockTransactionCompletedEvent($tradable, '1')
        );

        $this->assertTrue(true);
    }

    public function testSendTransactionCompletedMailWithToken(): void
    {
        $subscriber = new TransactionSubscriber(
            $this->mockMailer(true),
            $this->mockMoneyWrapper(),
            $this->mockLogger(),
            $this->mockEntityManager(),
            $this->mockUserNotificationManagerInterface()
        );

        $tradable = $this->createMock(Token::class);
        $user = $this->createMock(User::class);
        $profile = $this->createMock(Profile::class);

        $tradable->method('getProfile')->willReturn($profile);
        $tradable->method('getMoneySymbol')->willReturn("WEB");
        $profile->method('getUser')->willReturn($user);

        $subscriber->sendTransactionCompletedMail(
            $this->mockTransactionCompletedEvent($tradable, '1')
        );

        $this->assertTrue(true);
    }

    public function testUpdateTokenWithdrawForDeposit(): void
    {
        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->mockEntityManager();
        /** @var LoggerInterface|MockObject $logger */
        $logger = $this->mockLogger();

        $subscriber = new TransactionSubscriber(
            $this->mockMailer(),
            $this->mockMoneyWrapper(),
            $logger,
            $em,
            $this->mockUserNotificationManagerInterface()
        );

        $tradable = $this->createMock(Token::class);
        $user = $this->createMock(User::class);
        $profile = $this->createMock(Profile::class);

        $user->expects($this->exactly(2))->method('getId')->willReturn(1);
        $tradable->expects($this->once())->method('getProfile')->willReturn($profile);
        $tradable->expects($this->exactly(2))->method('getWithdrawn')->willReturn($this->mockMoney('0'));
        $tradable->expects($this->once())->method('setWithdrawn')->with('-1000');
        $profile->expects($this->once())->method('getUser')->willReturn($user);

        $em->expects($this->once())->method('persist')->with($tradable);
        $em->expects($this->once())->method('flush');

        $logger->expects($this->once())->method('info');

        $event = $this->createMock(DepositCompletedEvent::class);
        $event->expects($this->once())->method('getTradable')->willReturn($tradable);
        $event->expects($this->once())->method('getUser')->willReturn($user);
        $event->expects($this->once())->method('getAmount')->willReturn('1000');

        $subscriber->updateTokenWithdraw($event);

        $this->assertTrue(true);
    }

    public function testUpdateTokenWithdrawForWithdraw(): void
    {
        $amountValue = '5000';
        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->mockEntityManager();
        /** @var LoggerInterface|MockObject $logger */
        $logger = $this->mockLogger();

        $subscriber = new TransactionSubscriber(
            $this->mockMailer(),
            $this->mockMoneyWrapper(),
            $logger,
            $em,
            $this->mockUserNotificationManagerInterface()
        );

        $tradable = $this->createMock(Token::class);
        $user = $this->createMock(User::class);
        $profile = $this->createMock(Profile::class);

        $user->expects($this->exactly(2))->method('getId')->willReturn(1);
        $tradable->expects($this->once())->method('getProfile')->willReturn($profile);
        $tradable->expects($this->exactly(2))->method('getWithdrawn')->willReturn($this->mockMoney('0'));
        $tradable->expects($this->once())->method('setWithdrawn')->with($amountValue);
        $profile->expects($this->once())->method('getUser')->willReturn($user);

        $em->expects($this->once())->method('persist')->with($tradable);
        $em->expects($this->once())->method('flush');

        $logger->expects($this->once())->method('info');

        $event = $this->createMock(WithdrawCompletedEvent::class);
        $event->expects($this->once())->method('getTradable')->willReturn($tradable);
        $event->expects($this->once())->method('getUser')->willReturn($user);
        $event->expects($this->once())->method('getAmount')->willReturn($amountValue);

        $subscriber->updateTokenWithdraw($event);

        $this->assertTrue(true);
    }

    public function testUpdateTokenWithdrawForNotTokenOwner(): void
    {
        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->mockEntityManager();
        /** @var LoggerInterface|MockObject $logger */
        $logger = $this->mockLogger();

        $subscriber = new TransactionSubscriber(
            $this->mockMailer(),
            $this->mockMoneyWrapper(),
            $logger,
            $em,
            $this->mockUserNotificationManagerInterface()
        );

        $tradable = $this->createMock(Token::class);
        $eventUser = $this->createMock(User::class);
        $tokenOwner = $this->createMock(User::class);
        $profile = $this->createMock(Profile::class);

        $eventUser->expects($this->once())->method('getId')->willReturn(1);
        $tokenOwner->expects($this->once())->method('getId')->willReturn(3);

        $tradable->expects($this->once())->method('getProfile')->willReturn($profile);
        $tradable->expects($this->never())->method('getWithdrawn');
        $tradable->expects($this->never())->method('setWithdrawn');
        $profile->expects($this->once())->method('getUser')->willReturn($tokenOwner);

        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $logger->expects($this->never())->method('info');

        $event = $this->createMock(WithdrawCompletedEvent::class);
        $event->expects($this->once())->method('getTradable')->willReturn($tradable);
        $event->expects($this->once())->method('getUser')->willReturn($eventUser);
        $event->expects($this->once())->method('getAmount')->willReturn('2500');

        $subscriber->updateTokenWithdraw($event);

        $this->assertTrue(true);
    }

    public function testUpdateTokenWithdrawForZeroAmount(): void
    {
        /** @var EntityManagerInterface|MockObject $em */
        $em = $this->mockEntityManager();
        /** @var LoggerInterface|MockObject $logger */
        $logger = $this->mockLogger();

        $subscriber = new TransactionSubscriber(
            $this->mockMailer(),
            $this->mockMoneyWrapper(),
            $logger,
            $em,
            $this->mockUserNotificationManagerInterface()
        );

        $tradable = $this->createMock(Token::class);
        $user = $this->createMock(User::class);
        $profile = $this->createMock(Profile::class);

        $user->expects($this->exactly(2))->method('getId')->willReturn(1);
        $tradable->expects($this->once())->method('getProfile')->willReturn($profile);
        $tradable->expects($this->never())->method('getWithdrawn');
        $tradable->expects($this->never())->method('setWithdrawn');
        $profile->expects($this->once())->method('getUser')->willReturn($user);

        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $logger->expects($this->never())->method('info');

        $event = $this->createMock(WithdrawCompletedEvent::class);
        $event->expects($this->once())->method('getTradable')->willReturn($tradable);
        $event->expects($this->once())->method('getUser')->willReturn($user);
        $event->expects($this->once())->method('getAmount')->willReturn('0');

        $subscriber->updateTokenWithdraw($event);

        $this->assertTrue(true);
    }

    private function mockMailer(bool $isMailSend = false): MailerInterface
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($isMailSend ? $this->once() : $this->never())
            ->method('sendTransactionCompletedMail');

        return $mailer;
    }

    private function mockTransactionCompletedEvent(
        TradableInterface $tradable,
        string $amount
    ): TransactionCompletedEvent {
        $user = $this->createMock(User::class);

        $event = $this->createMock(TransactionCompletedEvent::class);
        $event->method('getTradable')->willReturn($tradable);
        $event->method('getUser')->willReturn($user);
        $event->method('getAmount')->willReturn($amount);

        return $event;
    }

    private function mockLogger(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }

    private function mockEntityManager(): EntityManagerInterface
    {
        return $this->createMock(EntityManagerInterface::class);
    }

    private function mockUserNotificationManagerInterface(): UserNotificationManagerInterface
    {
        $manager = $this->createMock(UserNotificationManagerInterface::class);
        $manager->method('isNotificationAvailable')->willReturn(true);

        return $manager;
    }

    private function mockMoney(string $amount, string $symbol = Symbols::TOK): Money
    {
        return new Money($amount, new Currency($symbol));
    }
}
