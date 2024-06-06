<?php declare(strict_types = 1);

namespace App\Tests\Consumer;

use App\Communications\AMQP\MarketAMQPInterface;
use App\Consumers\MarketConsumer;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\TopHolderManagerInterface;
use App\Manager\UserManagerInterface;
use App\Utils\LockFactory;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockInterface;

class MarketConsumerTest extends TestCase
{
    private const USER_ID = 1;
    private const CRYPTO_1 = 'WEB';
    private const CRYPTO_2 = "BTC";
    private const TOKEN = 'TOKEN';
    public function testExecute(): void
    {
        $consumer = new MarketConsumer(
            $this->mockLogger(1),
            $this->mockMarketStatusManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockMarketAMQP(),
            $this->mockEntityManager(),
            $this->mockLockFactory(true, true),
            $this->mockTopHolderManager(),
            $this->mockUserManager()
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            "quote" => self::CRYPTO_1,
            "base" => self::CRYPTO_2,
            'retried' => 0,
            'user_id' => null,
        ]))));
    }

    public function testExecuteFailsIfDBConnectionFailed(): void
    {
        $consumer = new MarketConsumer(
            $this->mockLogger(0, null, 'couldn\'t connect to DB'),
            $this->mockMarketStatusManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockMarketAMQP(),
            $this->mockEntityManager(false),
            $this->mockLockFactory(),
            $this->mockTopHolderManager(),
            $this->mockUserManager()
        );

        $this->assertFalse($consumer->execute($this->mockMessage((string)json_encode([]))));
    }

    public function testExecutionStopIfItFailedToParseTheMessage(): void
    {
        $consumer = new MarketConsumer(
            $this->mockLogger(1, 'Failed to parse incoming message'),
            $this->mockMarketStatusManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockMarketAMQP(),
            $this->mockEntityManager(),
            $this->mockLockFactory(),
            $this->mockTopHolderManager(),
            $this->mockUserManager()
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([]))));
    }

    public function testExecutionStopIfQuoteDoesntExist(): void
    {
        $consumer = new MarketConsumer(
            $this->mockLogger(1, 'base or quote not found'),
            $this->mockMarketStatusManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockMarketAMQP(),
            $this->mockEntityManager(),
            $this->mockLockFactory(),
            $this->mockTopHolderManager(),
            $this->mockUserManager()
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            "quote" => 'wrong symbol',
            "base" => self::CRYPTO_2,
            'retried' => 0,
            'user_id' => null,
        ]))));
    }

    public function testExecutionStopIfBaseDoesntExist(): void
    {
        $consumer = new MarketConsumer(
            $this->mockLogger(1, 'base or quote not found'),
            $this->mockMarketStatusManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockMarketAMQP(),
            $this->mockEntityManager(),
            $this->mockLockFactory(),
            $this->mockTopHolderManager(),
            $this->mockUserManager()
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            "quote" => self::CRYPTO_1,
            "base" => 'wrong symbol',
            'retried' => 0,
            'user_id' => null,
        ]))));
    }


    public function testExecutionStopIfLockCantBeAcquired(): void
    {
        $consumer = new MarketConsumer(
            $this->mockLogger(2),
            $this->mockMarketStatusManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockMarketAMQP(),
            $this->mockEntityManager(),
            $this->mockLockFactory(false),
            $this->mockTopHolderManager(),
            $this->mockUserManager()
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            "quote" => self::CRYPTO_1,
            "base" => self::CRYPTO_2,
            'retried' => 0,
            'user_id' => null,
        ]))));
    }

    public function testExecuteUpdateMarketThrowsExceptionAndWillProceed(): void
    {
        $consumer = new MarketConsumer(
            $this->mockLogger(
                1,
                null,
                'Can not update the market. Trying again. Reason: '
            ),
            $this->mockMarketStatusManager(true),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockMarketAMQP(1),
            $this->mockEntityManager(),
            $this->mockLockFactory(true, true),
            $this->mockTopHolderManager(),
            $this->mockUserManager()
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            "quote" => self::CRYPTO_1,
            "base" => self::CRYPTO_2,
            'retried' => 0,
            'user_id' => null,
        ]))));
    }

    public function testExecuteWithUserAndQuoteTokenButNotUpdateHolders(): void
    {
        $consumer = new MarketConsumer(
            $this->mockLogger(1),
            $this->mockMarketStatusManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockMarketAMQP(),
            $this->mockEntityManager(),
            $this->mockLockFactory(true, true),
            $this->mockTopHolderManager(),
            $this->mockUserManager()
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            "quote" => self::TOKEN,
            "base" => self::CRYPTO_2,
            'retried' => 0,
            'user_id' => self::USER_ID,
        ]))));
    }

    public function testExecuteWithUserAndTokenAndUpdateHolders(): void
    {
        $consumer = new MarketConsumer(
            $this->mockLogger(1),
            $this->mockMarketStatusManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockMarketAMQP(),
            $this->mockEntityManager(),
            $this->mockLockFactory(true, true),
            $this->mockTopHolderManager(true),
            $this->mockUserManager()
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            "quote" => self::TOKEN,
            "base" => self::CRYPTO_2,
            'retried' => 0,
            'user_id' => self::USER_ID,
        ]))));
    }

    public function testExecuteWithUserAndTokenAndUpdateHoldersThrowsError(): void
    {
        $consumer = new MarketConsumer(
            $this->mockLogger(1, null, 'Can not update TopHolders. Reason: '),
            $this->mockMarketStatusManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockMarketAMQP(),
            $this->mockEntityManager(),
            $this->mockLockFactory(true, true),
            $this->mockTopHolderManager(true, true),
            $this->mockUserManager()
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            "quote" => self::TOKEN,
            "base" => self::CRYPTO_2,
            'retried' => 0,
            'user_id' => self::USER_ID,
        ]))));
    }

    private function mockLogger(
        int $infoLoggedCount = 0,
        ?string $warningMessage = null,
        ?string $errorMessage = null
    ): LoggerInterface {
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->exactly($infoLoggedCount))
            ->method('info');

        $logger->expects($warningMessage ? $this->once() : $this->never())
            ->method('warning')
            ->willReturnCallback(function (string $message) use ($warningMessage): void {
                $this->assertStringContainsString($warningMessage, $message);
            });

        $logger->expects($errorMessage ? $this->once() : $this->never())
            ->method('error')
            ->willReturnCallback(function (string $message) use ($errorMessage): void {
                $this->assertStringContainsString($errorMessage, $message);
            });

        return $logger;
    }

    private function mockEntityManager(bool $isConnected = true): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(($isConnected ? 0 : 2) + 1))
            ->method('getConnection')
            ->willReturn($this->mockConnection($isConnected));

        return $entityManager;
    }

    private function mockCryptoManager(): CryptoManagerInterface
    {
        $cryptoManager = $this->createMock(CryptoManagerInterface::class);
        $cryptoManager
            ->method('findBySymbol')
            ->willReturnCallback(function ($cryptoSymbol): ?Crypto {
                return self::CRYPTO_1 === $cryptoSymbol || self::CRYPTO_2 === $cryptoSymbol
                    ? $this->mockCrypto($cryptoSymbol)
                    : null;
            });

        return $cryptoManager;
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

    private function mockMessage(string $body): AMQPMessage
    {
        $message = $this->createMock(AMQPMessage::class);
        $message->body = $body;

        return $message;
    }

    private function mockToken(): Token
    {
        return $this->createMock(Token::class);
    }

    private function mockCrypto(string $symbol): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getMoneySymbol')
            ->willReturn($symbol);

        return $crypto;
    }

    private function mockMarketStatusManager(?bool $throwsException = null): MarketStatusManagerInterface
    {
        $marketStatusManager = $this->createMock(MarketStatusManagerInterface::class);
        $marketStatusManager->method('updateMarketStatus')
            ->willReturnCallback(function () use ($throwsException): void {
                if ($throwsException) {
                    throw new \Exception();
                }
            });

        return $marketStatusManager;
    }

    private function mockTokenManager(bool $tradableExist = true): TokenManagerInterface
    {
        $tokenManager = $this->createMock(TokenManagerInterface::class);
        $tokenManager->method('findByName')
            ->willReturnCallback(function ($tokenName): ?Token {
                return self::TOKEN === $tokenName
                    ? $this->mockToken()
                    : null;
            });

        return $tokenManager;
    }

    private function mockMarketAMQP(int $invocationCount = 0): MarketAMQPInterface
    {
        $marketAMQP = $this->createMock(MarketAMQPInterface::class);
        $marketAMQP->expects($this->exactly($invocationCount))
            ->method('send');

        return $marketAMQP;
    }

    private function mockLockFactory(bool $isLockAcquired = true, bool $willLockGetReleased = false): LockFactory
    {
        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->method('createLock')
            ->willReturn($this->mockLock($isLockAcquired, $willLockGetReleased));

        return $lockFactory;
    }

    private function mockLock(bool $isLockAcquired, bool $willLockGetReleased): LockInterface
    {
        $lock = $this->createMock(LockInterface::class);
        $lock->method('acquire')->willReturn($isLockAcquired);
        $lock->expects($willLockGetReleased ? $this->once() : $this->never())->method('release');

        return $lock;
    }

    private function mockTopHolderManager(
        bool $shouldUpdateHolders = false,
        bool $updateTrowException = false
    ): TopHolderManagerInterface {
        $topHolderManager = $this->createMock(TopHolderManagerInterface::class);
        $topHolderManager
            ->method('shouldUpdateTopHolders')
            ->willReturn($shouldUpdateHolders);

        $topHolderManager
            ->expects($shouldUpdateHolders ? $this->once() : $this->never())
            ->method('updateTopHolders')
            ->willReturnCallback(function () use ($updateTrowException): void {
                if ($updateTrowException) {
                    throw new \Exception();
                }
            });

        return $topHolderManager;
    }

    private function mockUserManager(): UserManagerInterface
    {
        $userManager = $this->createMock(UserManagerInterface::class);
        $userManager
            ->method('find')
            ->willReturnCallback(function ($id): ?User {
                return self::USER_ID === $id
                    ? $this->createMock(User::class)
                    : null;
            });

        return $userManager;
    }
}
