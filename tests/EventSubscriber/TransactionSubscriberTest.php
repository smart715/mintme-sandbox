<?php declare(strict_types = 1);

namespace App\Tests\EventSubscriber;

use App\Entity\Crypto;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Events\TransactionCompletedEvent;
use App\EventSubscriber\TransactionSubscriber;
use App\Mailer\MailerInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TransactionSubscriberTest extends TestCase
{
    public function testSendTransactionCompletedMailWithCrypto(): void
    {
        $subscriber = new TransactionSubscriber(
            $this->mockMailer(),
            $this->mockMoneyWrapper(),
            $this->mockLogger(),
            $this->mockEntityManager()
        );

        $tradable = $this->createMock(Crypto::class);
        $tradable->method('getSymbol')->willReturn('WEB');

        $subscriber->sendTransactionCompletedMail(
            $this->mockTransactionCompletedEvent($tradable, '1')
        );

        $this->assertTrue(true);
    }

    public function testSendTransactionCompletedMailWithToken(): void
    {
        $subscriber = new TransactionSubscriber(
            $this->mockMailer(),
            $this->mockMoneyWrapper(),
            $this->mockLogger(),
            $this->mockEntityManager()
        );

        $tradable = $this->createMock(Token::class);
        $user = $this->createMock(User::class);
        $profile = $this->createMock(Profile::class);

        $tradable->method('getProfile')->willReturn($profile);
        $profile->method('getUser')->willReturn($user);

        $subscriber->sendTransactionCompletedMail(
            $this->mockTransactionCompletedEvent($tradable, '1')
        );

        $this->assertTrue(true);
    }

    private function mockMailer(): MailerInterface
    {
        return $this->createMock(MailerInterface::class);
    }

    private function mockMoneyWrapper(): MoneyWrapperInterface
    {
        $mw = $this->createMock(MoneyWrapperInterface::class);
        $mw->method('parse')->willReturnCallback(function (string $amount, string $symbol): Money {
            return new Money($amount, new Currency($symbol));
        });
        $mw->method('format')->willReturnCallback(function (Money $money): string {
            return $money->getAmount();
        });

        return $mw;
    }

    private function mockTransactionCompletedEvent(TradebleInterface $tradable, string $amount): TransactionCompletedEvent
    {
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
}
