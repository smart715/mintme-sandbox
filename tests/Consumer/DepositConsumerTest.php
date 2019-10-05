<?php declare(strict_types = 1);

namespace App\Tests\Consumer;

use App\Consumers\DepositConsumer;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use App\Utils\ClockInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\WalletInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
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
            $this->mockTokenManager(null),
            $this->mockLogger(),
            $this->mockMoneyWrapper(),
            $this->createMock(ClockInterface::class),
            $this->mockWallet(),
            $this->mockEntityManager()
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
            $this->mockTokenManager(null),
            $this->mockLogger(),
            $this->mockMoneyWrapper(),
            $this->createMock(ClockInterface::class),
            $this->mockWallet(),
            $this->mockEntityManager($this->never())
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
            $this->mockTokenManager(null),
            $this->mockLogger(),
            $this->mockMoneyWrapper(),
            $this->createMock(ClockInterface::class),
            $this->mockWallet(),
            $this->mockEntityManager()
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
            $this->mockTokenManager(null),
            $this->mockLogger(),
            $this->mockMoneyWrapper(),
            $this->createMock(ClockInterface::class),
            $this->mockWallet(),
            $this->mockEntityManager($this->never())
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => 1,
                'crypto' => $cryptoSymbol,
            ])))
        );
    }

    public function testExecuteWithToken(): void
    {
        $tokenName = 'TOK1';
        $dc = new DepositConsumer(
            $this->mockBalanceHandler($this->once(), $this->once()),
            $this->mockUserManager($this->createMock(User::class)),
            $this->mockCryptoManager(null),
            $this->mockTokenManager($this->mockToken($tokenName)),
            $this->mockLogger(),
            $this->mockMoneyWrapper(),
            $this->createMock(ClockInterface::class),
            $this->mockWallet(),
            $this->mockEntityManager($this->once())
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => 1,
                'crypto' => $tokenName,
                'amount' => '10000',
            ])))
        );
    }

    public function testExecuteWithTokenAndRelatedUser(): void
    {
        $tokenName = 'TOK1';
        $user = $this->createMock(User::class);
        $dc = new DepositConsumer(
            $this->mockBalanceHandler($this->once(), $this->once()),
            $this->mockUserManager($user),
            $this->mockCryptoManager(null),
            $this->mockTokenManager($this->mockToken($tokenName, [$user])),
            $this->mockLogger(),
            $this->mockMoneyWrapper(),
            $this->createMock(ClockInterface::class),
            $this->mockWallet(),
            $this->mockEntityManager($this->never())
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => 1,
                'crypto' => $tokenName,
                'amount' => '10000',
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
            $this->mockTokenManager(null),
            $this->mockLogger(),
            $this->mockMoneyWrapper(),
            $this->createMock(ClockInterface::class),
            $this->mockWallet(),
            $this->mockEntityManager()
        );

        $this->assertFalse(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => 1,
                'crypto' => $cryptoSymbol,
                'amount' => '10000',
            ])))
        );
    }

    private function mockEntityManager(?Invocation $invocation = null): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($invocation ?? $this->never())->method('flush');
        $em->method('getConnection')->willReturn(
            $this->createMock(Connection::class)
        );

        return $em;
    }

    private function mockWallet(): WalletInterface
    {
        $wallet = $this->createMock(WalletInterface::class);
        $wallet->method('getFee')->willReturn(
            new Money('10000000000', new Currency(MoneyWrapper::TOK_SYMBOL))
        );

        return $wallet;
    }

    private function mockCrypto(string $symbol): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getSymbol')->willReturn($symbol);

        return $crypto;
    }

    private function mockToken(string $name, array $users = []): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getSymbol')->willReturn($name);
        $token->method('getUsers')->willReturn($users);

        return $token;
    }

    private function mockMessage(string $message): AMQPMessage
    {
        $msg = $this->createMock(AMQPMessage::class);
        $msg->body = $message;

        return $msg;
    }

    private function mockBalanceHandler(Invocation $imDeposit, ?Invocation $imWithdraw = null): BalanceHandlerInterface
    {
        $bh = $this->createMock(BalanceHandlerInterface::class);
        $bh->expects($imDeposit)->method('deposit');
        $bh->expects($imWithdraw ?? $this->never())->method('withdraw');

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

    private function mockTokenManager(?Token $token): TokenManagerInterface
    {
        $tm = $this->createMock(TokenManagerInterface::class);
        $tm->method('findByName')->willReturn($token);

        return $tm;
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
