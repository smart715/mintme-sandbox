<?php declare(strict_types = 1);

namespace App\Tests\Consumer;

use App\Consumers\PaymentConsumer;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PaymentConsumerTest extends TestCase
{
    private const CRYPTO_SYMBOL = 'WEB';
    private const TOKEN_SYMBOL = 'TOKEN';
    private const USER_ID = 1;
    private const STATUS_OK = 'ok';
    private const TH_HASH = 'x01234123';
    private const RETRIES = 0;
    private const AMOUNT = '10000';
    private const ADDRESS = '0x01';
    private const CRYPTO_NETWORK = 'ETH';

    public function testExecuteDbConnectionFailed(): void
    {
        $dc = new PaymentConsumer(
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(),
            $this->mockEM(false),
            $this->mockEventDispatcher(),
        );

        $this->assertFalse($dc->execute($this->mockMessage('')));
    }

    public function testExecuteFailedParseMessage(): void
    {
        $dc = new PaymentConsumer(
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(1, 'Failed to parse incoming message'),
            $this->mockEM(),
            $this->mockEventDispatcher()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode('invalid message')))
        );
    }

    public function testExecuteUserNotFound(): void
    {
        $dc = new PaymentConsumer(
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(1, 'User not found'),
            $this->mockEM(),
            $this->mockEventDispatcher()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'id' => -1,
                'status' => self::STATUS_OK,
                'tx_hash' => self::TH_HASH,
                'retries' => self::RETRIES,
                'crypto' => self::CRYPTO_SYMBOL,
                'amount' => self::AMOUNT,
                'address' => self::ADDRESS,
                'cryptoNetwork' => self::CRYPTO_NETWORK,
            ])))
        );
    }

    public function testExecuteTradableNotFound(): void
    {
        $dc = new PaymentConsumer(
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(1, 'Invalid crypto "INVALID TRADABLE" given'),
            $this->mockEM(),
            $this->mockEventDispatcher()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'id' => self::USER_ID,
                'status' => self::STATUS_OK,
                'tx_hash' => self::TH_HASH,
                'retries' => self::RETRIES,
                'crypto' => 'INVALID TRADABLE',
                'amount' => self::AMOUNT,
                'address' => self::ADDRESS,
                'cryptoNetwork' => self::CRYPTO_NETWORK,
            ])))
        );
    }

    public function testExecuteStatusNotOk(): void
    {
        $dc = new PaymentConsumer(
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(1),
            $this->mockEM(),
            $this->mockEventDispatcher()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'id' => self::USER_ID,
                'status' => 'NOT OK',
                'tx_hash' => self::TH_HASH,
                'retries' => self::RETRIES,
                'crypto' => self::CRYPTO_SYMBOL,
                'amount' => self::AMOUNT,
                'address' => self::ADDRESS,
                'cryptoNetwork' => self::CRYPTO_NETWORK,
            ])))
        );
    }

    public function testExecuteCryptoStatusOk(): void
    {
        $dc = new PaymentConsumer(
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(1),
            $this->mockEM(),
            $this->mockEventDispatcher(true)
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'id' => self::USER_ID,
                'status' => self::STATUS_OK,
                'tx_hash' => self::TH_HASH,
                'retries' => self::RETRIES,
                'crypto' => self::CRYPTO_SYMBOL,
                'amount' => self::AMOUNT,
                'address' => self::ADDRESS,
                'cryptoNetwork' => self::CRYPTO_NETWORK,
            ])))
        );
    }

    public function testExecuteTokenStatusOk(): void
    {
        $dc = new PaymentConsumer(
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(1),
            $this->mockEM(),
            $this->mockEventDispatcher(true)
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'id' => self::USER_ID,
                'status' => self::STATUS_OK,
                'tx_hash' => self::TH_HASH,
                'retries' => self::RETRIES,
                'crypto' => self::TOKEN_SYMBOL,
                'amount' => self::AMOUNT,
                'address' => self::ADDRESS,
                'cryptoNetwork' => self::CRYPTO_NETWORK,
            ])))
        );
    }

    private function mockCrypto(): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getSymbol')->willReturn(self::CRYPTO_SYMBOL);
        $crypto->method('getFee')->willReturn(new Money(1, new Currency(self::CRYPTO_SYMBOL)));

        return $crypto;
    }

    private function mockMessage(string $message): AMQPMessage
    {
        $msg = $this->createMock(AMQPMessage::class);
        $msg->body = $message;

        return $msg;
    }

    private function mockUserManager(): UserManagerInterface
    {
        $um = $this->createMock(UserManagerInterface::class);
        $um->method('find')->willReturnCallback(function ($id): ?User {
            return self::USER_ID === $id
                ? $this->createMock(User::class)
                : null;
        });

        return $um;
    }

    private function mockCryptoManager(): CryptoManagerInterface
    {
        $cm = $this->createMock(CryptoManagerInterface::class);
        $cm->method('findBySymbol')
            ->willReturnCallback(function ($symbol): ?Crypto {
                return self::CRYPTO_SYMBOL === $symbol
                    ? $this->mockCrypto()
                    : null;
            });

        return $cm;
    }

    private function mockTokenManager(): TokenManagerInterface
    {
        $tm = $this->createMock(TokenManagerInterface::class);
        $tm->method('findByName')->willReturnCallback(function ($name): ?Token {
            return self::TOKEN_SYMBOL === $name
                ? $this->mockToken()
                : null;
        });

        return $tm;
    }

    private function mockToken(): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getSymbol')->willReturn(self::TOKEN_SYMBOL);
        $token->method('getCryptoSymbol')->willReturn(self::CRYPTO_SYMBOL);

        return $token;
    }

    private function mockLogger(int $infoLogs = 0, ?string $warningMessage = null): LoggerInterface
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly($infoLogs))->method('info');
        $logger->expects($warningMessage ? $this->once() : $this->never())
            ->method('warning')
            ->willReturnCallback(function ($message) use ($warningMessage): void {
                $this->assertStringContainsString($warningMessage, $message);
            });

        return $logger;
    }

    private function mockEventDispatcher(bool $dispatchEvent = false): EventDispatcherInterface
    {
        $ed = $this->createMock(EventDispatcherInterface::class);
        $ed ->expects($dispatchEvent ? $this->once() : $this->never())
            ->method('dispatch');

        return $ed;
    }

    private function mockEM(bool $isConnected = true): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(($isConnected ? 0 : 2) + 1))
            ->method('getConnection')
            ->willReturn($this->mockConnection($isConnected));

        return $em;
    }

    private function mockConnection(bool $isConnected = true): Connection
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('ping')
            ->willReturn($isConnected);

        $connection->expects($isConnected ? $this->never() : $this->once())
            ->method('close');

        $connection->expects($isConnected ? $this->never() : $this->once())
            ->method('connect')
            ->willThrowException(new \Exception());

        return $connection;
    }
}
