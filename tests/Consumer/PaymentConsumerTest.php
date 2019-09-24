<?php declare(strict_types = 1);

namespace App\Tests\Consumer;

use App\Consumers\PaymentConsumer;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use App\Utils\ClockInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\Matcher\Invocation;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PaymentConsumerTest extends TestCase
{
    public function testExecute(): void
    {
        $cryptoSymbol = 'FOO';
        $dc = new PaymentConsumer(
            $this->mockBalanceHandler($this->never()),
            $this->mockUserManager($this->createMock(User::class)),
            $this->mockCryptoManager($this->mockCrypto($cryptoSymbol), $this->once()),
            $this->mockTokenManager(),
            $this->mockLogger(),
            $this->mockMoneyWrapper(),
            $this->createMock(ClockInterface::class),
            $this->mockEventDispatcher()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'id' => 1,
                'status' => 'success',
                'tx_hash' => 'x01234123',
                'retries' => 0,
                'crypto' => $cryptoSymbol,
                'amount' => '10000',
            ])))
        );
    }

    public function testExecuteWithoutUser(): void
    {
        $cryptoSymbol = 'WEB';
        $dc = new PaymentConsumer(
            $this->mockBalanceHandler($this->never()),
            $this->mockUserManager(null),
            $this->mockCryptoManager($this->mockCrypto($cryptoSymbol), $this->never()),
            $this->mockTokenManager(),
            $this->mockLogger(),
            $this->mockMoneyWrapper(),
            $this->createMock(ClockInterface::class),
            $this->mockEventDispatcher()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'id' => 1,
                'status' => 'success',
                'tx_hash' => 'x01234123',
                'retries' => 0,
                'crypto' => $cryptoSymbol,
                'amount' => '10000',
            ])))
        );
    }

    public function testExecuteWithoutCrypto(): void
    {
        $cryptoSymbol = 'FOO';
        $dc = new PaymentConsumer(
            $this->mockBalanceHandler($this->never()),
            $this->mockUserManager($this->createMock(User::class)),
            $this->mockCryptoManager(null, $this->once()),
            $this->mockTokenManager(null, $this->once()),
            $this->mockLogger(),
            $this->mockMoneyWrapper(),
            $this->createMock(ClockInterface::class),
            $this->mockEventDispatcher()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'id' => 1,
                'status' => 'fail',
                'tx_hash' => 'x01234123',
                'retries' => 0,
                'crypto' => $cryptoSymbol,
                'amount' => '10000',
            ])))
        );
    }

    public function testExecuteFailedParse(): void
    {
        $cryptoSymbol = 'FOO';
        $dc = new PaymentConsumer(
            $this->mockBalanceHandler($this->never()),
            $this->mockUserManager($this->createMock(User::class)),
            $this->mockCryptoManager($this->mockCrypto($cryptoSymbol), $this->never()),
            $this->mockTokenManager(),
            $this->mockLogger(),
            $this->mockMoneyWrapper(),
            $this->createMock(ClockInterface::class),
            $this->mockEventDispatcher()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'id' => 1,
                'status' => 'success',
                'tx_hash' => 'x01234123',
                'retries' => 0,
                'crypto' => $cryptoSymbol,
            ])))
        );
    }

    public function testExecuteWithException(): void
    {
        $cryptoSymbol = 'FOO';

        $bh = $this->createMock(BalanceHandlerInterface::class);
        $bh->method('deposit')
            ->willThrowException(new \Exception());

        $dc = new PaymentConsumer(
            $bh,
            $this->mockUserManager($this->createMock(User::class)),
            $this->mockCryptoManager($this->mockCrypto($cryptoSymbol), $this->once()),
            $this->mockTokenManager(),
            $this->mockLogger(),
            $this->mockMoneyWrapper(),
            $this->createMock(ClockInterface::class),
            $this->mockEventDispatcher()
        );

        $this->assertFalse(
            $dc->execute($this->mockMessage((string)json_encode([
                'id' => 1,
                'status' => 'fail',
                'tx_hash' => 'x01234123',
                'retries' => 0,
                'crypto' => $cryptoSymbol,
                'amount' => '10000',
            ])))
        );
    }

    public function testExecuteFailedToDeposit(): void
    {
        $cryptoSymbol = 'FOO';

        $bh = $this->createMock(BalanceHandlerInterface::class);
        $bh->expects($this->once())->method('deposit');

        $dc = new PaymentConsumer(
            $bh,
            $this->mockUserManager($this->createMock(User::class)),
            $this->mockCryptoManager($this->mockCrypto($cryptoSymbol), $this->once()),
            $this->mockTokenManager(),
            $this->mockLogger(),
            $this->mockMoneyWrapper(),
            $this->createMock(ClockInterface::class),
            $this->mockEventDispatcher()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'id' => 1,
                'status' => 'fail',
                'tx_hash' => 'x01234123',
                'retries' => 0,
                'crypto' => $cryptoSymbol,
                'amount' => '10000',
            ])))
        );
    }

    public function testExecuteFailedToDepositWithToken(): void
    {
        $tokenName = 'tok1';

        $bh = $this->createMock(BalanceHandlerInterface::class);
        $bh->expects($this->exactly(2))->method('deposit');

        $dc = new PaymentConsumer(
            $bh,
            $this->mockUserManager($this->createMock(User::class)),
            $this->mockCryptoManager(null, $this->exactly(2)),
            $this->mockTokenManager($this->mockToken($tokenName), $this->once()),
            $this->mockLogger(),
            $this->mockMoneyWrapper(),
            $this->createMock(ClockInterface::class),
            $this->mockEventDispatcher()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'id' => 1,
                'status' => 'fail',
                'tx_hash' => 'x01234123',
                'retries' => 0,
                'crypto' => $tokenName,
                'amount' => '10000',
            ])))
        );
    }

    private function mockCrypto(string $symbol): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getSymbol')->willReturn($symbol);
        $crypto->method('getFee')->willReturn(new Money(1, new Currency($symbol)));

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

    private function mockCryptoManager(?Crypto $crypto, Invocation $invocation): CryptoManagerInterface
    {
        $cm = $this->createMock(CryptoManagerInterface::class);
        $cm->expects($invocation)->method('findBySymbol')->will($this->returnCallback(
            function ($symbol) use ($crypto) {
                return !$crypto && 'WEB' === $symbol
                    ? $this->mockCrypto('WEB')
                    : $crypto;
            }
        ));

        return $cm;
    }

    private function mockTokenManager(?Token $token = null, ?Invocation $invocation = null): TokenManagerInterface
    {
        $tm = $this->createMock(TokenManagerInterface::class);
        $tm->expects($invocation ?? $this->never())
            ->method('findByName')->willReturn($token);

        return $tm;
    }

    private function mockToken(string $name): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getSymbol')->willReturn($name);

        return $token;
    }

    private function mockLogger(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }

    private function mockMoneyWrapper(): MoneyWrapperInterface
    {
        $mw = $this->createMock(MoneyWrapperInterface::class);
        $mw->method('parse')->willReturnCallback(function (string $amount, string $symbol): Money {
            return new Money($amount, new Currency($symbol));
        });

        return $mw;
    }

    private function mockEventDispatcher(): EventDispatcherInterface
    {
        $ed = $this->createMock(EventDispatcherInterface::class);
        $ed->method('dispatch')->willReturnCallback(function ($eventName, $event) {
            return $event;
        });

        return $ed;
    }
}
