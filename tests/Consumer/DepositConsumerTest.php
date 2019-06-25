<?php declare(strict_types = 1);

namespace App\Tests\Consumer;

use App\Consumers\DepositConsumer;
use App\Entity\Crypto;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\UserManagerInterface;
use App\Utils\ClockInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\Matcher\Invocation;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DepositConsumerTest extends TestCase
{
    public function testExecute(): void
    {
        $cryptoSymbol = 'WEB';
        $dc = new DepositConsumer(
            $this->mockBalanceHandler($this->once()),
            $this->mockUserManager($this->createMock(User::class)),
            $this->mockCryptoManager($this->mockCrypto($cryptoSymbol)),
            $this->mockLogger(),
            $this->mockMoneyWrapper(),
            $this->createMock(ClockInterface::class)
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => 1,
                'crypto' => $cryptoSymbol,
                'amount' => '10000',
            ])))
        );
    }

    public function testExecuteWithoutUser(): void
    {
        $cryptoSymbol = 'WEB';
        $dc = new DepositConsumer(
            $this->mockBalanceHandler($this->never()),
            $this->mockUserManager(null),
            $this->mockCryptoManager($this->mockCrypto($cryptoSymbol)),
            $this->mockLogger(),
            $this->mockMoneyWrapper(),
            $this->createMock(ClockInterface::class)
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => 1,
                'crypto' => $cryptoSymbol,
                'amount' => '10000',
            ])))
        );
    }

    public function testExecuteWithoutCrypto(): void
    {
        $cryptoSymbol = 'WEB';
        $dc = new DepositConsumer(
            $this->mockBalanceHandler($this->never()),
            $this->mockUserManager($this->createMock(User::class)),
            $this->mockCryptoManager(null),
            $this->mockLogger(),
            $this->mockMoneyWrapper(),
            $this->createMock(ClockInterface::class)
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => 1,
                'crypto' => $cryptoSymbol,
                'amount' => '10000',
            ])))
        );
    }

    public function testExecuteFailedParse(): void
    {
        $cryptoSymbol = 'WEB';
        $dc = new DepositConsumer(
            $this->mockBalanceHandler($this->never()),
            $this->mockUserManager($this->createMock(User::class)),
            $this->mockCryptoManager($this->mockCrypto($cryptoSymbol)),
            $this->mockLogger(),
            $this->mockMoneyWrapper(),
            $this->createMock(ClockInterface::class)
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => 1,
                'crypto' => $cryptoSymbol,
            ])))
        );
    }

    public function testExecuteWithException(): void
    {
        $cryptoSymbol = 'WEB';

        $bh = $this->createMock(BalanceHandlerInterface::class);
        $bh->expects($this->once())
            ->method('deposit')
            ->willThrowException(new \Exception());

        $dc = new DepositConsumer(
            $bh,
            $this->mockUserManager($this->createMock(User::class)),
            $this->mockCryptoManager($this->mockCrypto($cryptoSymbol)),
            $this->mockLogger(),
            $this->mockMoneyWrapper(),
            $this->createMock(ClockInterface::class)
        );

        $this->assertFalse(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => 1,
                'crypto' => $cryptoSymbol,
                'amount' => '10000',
            ])))
        );
    }

    private function mockCrypto(string $symbol): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getSymbol')->willReturn($symbol);

        return $crypto;
    }

    private function mockMessage(string $message): AMQPMessage
    {
        $msg = $this->createMock(AMQPMessage::class);
        $msg->body = $message;

        return $msg;
    }

    private function mockBalanceHandler(Invocation $im): BalanceHandlerInterface
    {
        $bh = $this->createMock(BalanceHandlerInterface::class);
        $bh->expects($im)->method('deposit');

        return $bh;
    }

    private function mockUserManager(?User $user): UserManagerInterface
    {
        $um = $this->createMock(UserManagerInterface::class);
        $um->method('find')->willReturn($user);

        return $um;
    }

    private function mockCryptoManager(?Crypto $crypto): CryptoManagerInterface
    {
        $cm = $this->createMock(CryptoManagerInterface::class);
        $cm->method('findBySymbol')->willReturn($crypto);

        return $cm;
    }

    private function mockLogger(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }

    private function mockMoneyWrapper(): MoneyWrapperInterface
    {
        $mw = $this->createMock(MoneyWrapperInterface::class);
        $mw->method('parse')->willReturnCallback(function (int $amount, string $symbol): Money {
            return new Money($amount, new Currency($symbol));
        });

        return $mw;
    }
}
