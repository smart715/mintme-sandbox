<?php declare(strict_types = 1);

namespace App\Tests\Consumer;

use App\Communications\DeployCostFetcherInterface;
use App\Consumers\DeployConsumer;
use App\Entity\Crypto;
use App\Entity\Profile;
use App\Entity\Token\LockIn;
use App\Entity\Token\Token;
use App\Entity\Token\TokenDeploy;
use App\Entity\TokenCrypto;
use App\Entity\User;
use App\Events\TokenEvents;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Repository\TokenRepository;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DeployConsumerTest extends TestCase
{
    private const COINBASE_API_TIMEOUT = -10;
    private const REWARD_CRYPTO = 'WEB';
    private const REWARD_AMOUNT = '10';

    public function testExecuteFailsIfDBConnectionFailed(): void
    {
        $consumer = new DeployConsumer(
            $this->mockLogger(0, null, 'couldn\'t connect to DB'),
            self::COINBASE_API_TIMEOUT,
            $this->mockEntityManager(false),
            $this->mockBalanceHandler(),
            $this->mockDeployCostFetcher(),
            $this->mockEventDispatcher(),
            $this->mockCryptoManager(),
            $this->mockMoneyWrapper(),
            $this->mockMarketFactory(),
            $this->mockMarketStatusManager()
        );

        $this->assertFalse($consumer->execute($this->mockMessage((string)json_encode([
            'tokenName' => "TEST",
            'crypto' => 'WEB',
            'address' => '10000',
            'txHash' => '0x0',
            'status' => 'ok',
        ]))));
    }

    public function testExecutionStopIfItFailedToParseTheMessage(): void
    {
        $consumer = new DeployConsumer(
            $this->mockLogger(1, 'Failed to parse incoming message'),
            self::COINBASE_API_TIMEOUT,
            $this->mockEntityManager(),
            $this->mockBalanceHandler(),
            $this->mockDeployCostFetcher(),
            $this->mockEventDispatcher(),
            $this->mockCryptoManager(),
            $this->mockMoneyWrapper(),
            $this->mockMarketFactory(),
            $this->mockMarketStatusManager()
        );

        $this->assertTrue($consumer->execute($this->mockMessage('{}')));
    }

    public function testExecutionStopIfTokenDoesntExist(): void
    {
        $consumer = new DeployConsumer(
            $this->mockLogger(1, 'Invalid token \'TEST\' given'),
            self::COINBASE_API_TIMEOUT,
            $this->mockEntityManager(true, false),
            $this->mockBalanceHandler(),
            $this->mockDeployCostFetcher(),
            $this->mockEventDispatcher(),
            $this->mockCryptoManager(),
            $this->mockMoneyWrapper(),
            $this->mockMarketFactory(),
            $this->mockMarketStatusManager()
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            'tokenName' => "TEST",
            'crypto' => 'WEB',
            'address' => '10000',
            'txHash' => '0x0',
            'status' => 'ok',
        ]))));
    }


    public function testExecutionStopIfInvalidTokenDeploy(): void
    {
        $consumer = new DeployConsumer(
            $this->mockLogger(1, 'Invalid token deploy TEST/WEB given'),
            self::COINBASE_API_TIMEOUT,
            $this->mockEntityManager(true, true, false),
            $this->mockBalanceHandler(),
            $this->mockDeployCostFetcher(),
            $this->mockEventDispatcher(),
            $this->mockCryptoManager(),
            $this->mockMoneyWrapper(),
            $this->mockMarketFactory(),
            $this->mockMarketStatusManager()
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            'tokenName' => "TEST",
            'crypto' => 'WEB',
            'address' => '10000',
            'txHash' => '0x0',
            'status' => 'ok',
        ]))));
    }

    public function testExecuteWithStatusFailAndNoDeployCostAndNonDeployedToken(): void
    {
        $consumer = new DeployConsumer(
            $this->mockLogger(1, null, 'deployment failed'),
            self::COINBASE_API_TIMEOUT,
            $this->mockEntityManager(true, true, true, false, 1, 1),
            $this->mockBalanceHandler(),
            $this->mockDeployCostFetcher(),
            $this->mockEventDispatcher(),
            $this->mockCryptoManager(),
            $this->mockMoneyWrapper(),
            $this->mockMarketFactory(),
            $this->mockMarketStatusManager()
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            'tokenName' => "TEST",
            'crypto' => 'WEB',
            'address' => '',
            'txHash' => '0x0',
            'status' => 'fail',
        ]))));
    }

    public function testExecuteWithStatusFailAndNoDeployCostAndDeployedToken(): void
    {
        $consumer = new DeployConsumer(
            $this->mockLogger(1, null, 'deployment failed'),
            self::COINBASE_API_TIMEOUT,
            $this->mockEntityManager(true, true, true, true, 1, 1),
            $this->mockBalanceHandler(),
            $this->mockDeployCostFetcher(),
            $this->mockEventDispatcher(),
            $this->mockCryptoManager(),
            $this->mockMoneyWrapper(),
            $this->mockMarketFactory(),
            $this->mockMarketStatusManager()
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            'tokenName' => "TEST",
            'crypto' => 'WEB',
            'address' => '',
            'txHash' => '0x0',
            'status' => 'fail',
        ]))));
    }

    public function testExecuteWithTheAddressAndTheMainDeployNoReferencer(): void
    {
        $entityManager = $this->mockEntityManager(
            true,
            true,
            true,
            true,
            3,
            1,
            true
        );


        $consumer = new DeployConsumer(
            $this->mockLogger(1),
            self::COINBASE_API_TIMEOUT,
            $entityManager,
            $this->mockBalanceHandler(),
            $this->mockDeployCostFetcher(),
            $this->mockEventDispatcher(true),
            $this->mockCryptoManager(),
            $this->mockMoneyWrapper(),
            $this->mockMarketFactory(1),
            $this->mockMarketStatusManager(1)
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            'tokenName' => "TEST",
            'crypto' => 'WEB',
            'address' => '10000',
            'txHash' => '0x0',
            'status' => 'ok',
        ]))));
    }

    public function testExecuteWithTheAddressAndTheMainDeployWithReferencerWithPositiveReward(): void
    {
        $entityManager = $this->mockEntityManager(
            true,
            true,
            true,
            true,
            5,
            1,
            true,
            true
        );

        $consumer = new DeployConsumer(
            $this->mockLogger(2),
            self::COINBASE_API_TIMEOUT,
            $entityManager,
            $this->mockBalanceHandler(2),
            $this->mockDeployCostFetcher(true, true),
            $this->mockEventDispatcher(true),
            $this->mockCryptoManager(),
            $this->mockMoneyWrapper(),
            $this->mockMarketFactory(1),
            $this->mockMarketStatusManager(1)
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            'tokenName' => "TEST",
            'crypto' => 'WEB',
            'address' => '10000',
            'txHash' => '0x0',
            'status' => 'ok',
        ]))));
    }

    public function testExecuteReferralRewardWithNoWEBDeployCrypto(): void
    {
        $entityManager = $this->mockEntityManager(
            true,
            true,
            true,
            true,
            5,
            1,
            true,
            true
        );

        $consumer = new DeployConsumer(
            $this->mockLogger(2),
            self::COINBASE_API_TIMEOUT,
            $entityManager,
            $this->mockBalanceHandler(2),
            $this->mockDeployCostFetcher(true, true),
            $this->mockEventDispatcher(true),
            $this->mockCryptoManager(),
            $this->mockMoneyWrapper(),
            $this->mockMarketFactory(1),
            $this->mockMarketStatusManager(1)
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            'tokenName' => "TEST",
            'crypto' => 'ETH',
            'address' => '10000',
            'txHash' => '0x0',
            'status' => 'ok',
        ]))));
    }

    public function testExecuteWithTheAddressAndTheMainDeployWithReferencerWithNegativeReward(): void
    {
        $entityManager = $this->mockEntityManager(
            true,
            true,
            true,
            true,
            3,
            1,
            true,
            true
        );

        $consumer = new DeployConsumer(
            $this->mockLogger(1),
            self::COINBASE_API_TIMEOUT,
            $entityManager,
            $this->mockBalanceHandler(),
            $this->mockDeployCostFetcher(true, false),
            $this->mockEventDispatcher(true),
            $this->mockCryptoManager(),
            $this->mockMoneyWrapper(),
            $this->mockMarketFactory(1),
            $this->mockMarketStatusManager(1)
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            'tokenName' => "TEST",
            'crypto' => 'WEB',
            'address' => '10000',
            'txHash' => '0x0',
            'status' => 'ok',
        ]))));
    }

    public function testExecuteWithTheAddressAndNotMainDeploy(): void
    {
        $entityManager = $this->mockEntityManager(
            true,
            true,
            true,
            true,
            2,
            1,
            false,
            true
        );

        $consumer = new DeployConsumer(
            $this->mockLogger(1),
            self::COINBASE_API_TIMEOUT,
            $entityManager,
            $this->mockBalanceHandler(),
            $this->mockDeployCostFetcher(),
            $this->mockEventDispatcher(false, true),
            $this->mockCryptoManager(),
            $this->mockMoneyWrapper(),
            $this->mockMarketFactory(1),
            $this->mockMarketStatusManager(1)
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            'tokenName' => "TEST",
            'crypto' => 'WEB',
            'address' => '10000',
            'txHash' => '0x0',
            'status' => 'ok',
        ]))));
    }

    public function testProcessDeploymentMessageException(): void
    {
        $consumer = new DeployConsumer(
            $this->mockLogger(1, null, 'Failed to update token address. Retry operation.'),
            self::COINBASE_API_TIMEOUT,
            $this->mockEntityManager(),
            $this->mockBalanceHandler(0, true),
            $this->mockDeployCostFetcher(),
            $this->mockEventDispatcher(),
            $this->mockCryptoManager(),
            $this->mockMoneyWrapper(),
            $this->mockMarketFactory(),
            $this->mockMarketStatusManager()
        );

        $this->assertFalse($consumer->execute($this->mockMessage((string)json_encode([
            'tokenName' => "TEST",
            'crypto' => 'WEB',
            'address' => '10000',
            'txHash' => '0x0',
            'status' => 'ok',
        ]))));
    }

    private function mockLogger(
        int $infoLoggedCount,
        ?string $warningMessage = null,
        ?string $errorMessage = null
    ): LoggerInterface {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly($infoLoggedCount))
            ->method('info');

        $logger->expects($warningMessage ? $this->once() : $this->never())
            ->method('warning')
            ->willReturnCallback(function ($message) use ($warningMessage): void {
                $this->assertStringContainsString($warningMessage, $message);
            });

        $logger->expects($errorMessage ? $this->once() : $this->never())
            ->method('error')
            ->willReturnCallback(function ($message) use ($errorMessage): void {
                $this->assertStringContainsString($errorMessage, $message);
            });

        return $logger;
    }

    private function mockEntityManager(
        bool $isConnected = true,
        bool $tokenExist = true,
        bool $tokenDeployExist = true,
        bool $isTokenDeploy = true,
        int $persistCount = 0,
        int $flushCount = 0,
        bool $isMainDeploy = false,
        bool $isReferencer = false
    ): EntityManagerInterface {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(($isConnected ? 0 : 2) + 1))
            ->method('getConnection')
            ->willReturn($this->mockConnection($isConnected));
        $entityManager->method('getRepository')->with(Token::class)
            ->willReturn(
                $this->mockTokenRepository(
                    $tokenExist,
                    $tokenDeployExist,
                    $isTokenDeploy,
                    $isMainDeploy,
                    $isReferencer
                )
            );
        $entityManager->expects($this->exactly($persistCount))
            ->method('persist');
        $entityManager->expects($this->exactly($flushCount))
            ->method('flush');

        return $entityManager;
    }

    private function mockBalanceHandler(
        int $depositCount = 0,
        bool $beginTransactionException = false
    ): BalanceHandlerInterface {
        $balanceHandler = $this->createMock(BalanceHandlerInterface::class);
        $balanceHandler->expects($this->exactly($depositCount))
            ->method('deposit')
            ->willReturnCallback(function (User $user, Crypto $crypto, Money $amount): void {
                $this->assertEquals(self::REWARD_CRYPTO, $crypto->getSymbol());
                $this->assertEquals(self::REWARD_AMOUNT, $amount->getAmount());
            });

        if ($beginTransactionException) {
            $balanceHandler
                ->method('beginTransaction')
                ->willThrowException(new \Exception());
            $balanceHandler
                ->expects($this->once())
                ->method('rollback');
        }

        return $balanceHandler;
    }

    private function mockDeployCostFetcher(
        bool $isReferencer = false,
        bool $isPositive = false
    ): DeployCostFetcherInterface {
        $deployCostFetcher = $this->createMock(DeployCostFetcherInterface::class);
        $deployCostFetcher->expects($isReferencer ? $this->once() : $this->never())
            ->method('getDeployCostReferralReward')
            ->willReturn(new Money(
                $isPositive ? self::REWARD_AMOUNT : self::COINBASE_API_TIMEOUT,
                new Currency(self::REWARD_CRYPTO)
            ));

        $deployCostFetcher->method('getCost')->willReturn(new Money(10, new Currency('WEB')));

        return $deployCostFetcher;
    }

    private function mockEventDispatcher(
        bool $deployCompleted = false,
        bool $connectCompleted = false
    ): EventDispatcherInterface {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($deployCompleted || $connectCompleted ? $this->once() : $this->never())
            ->method('dispatch')
            ->willReturnCallback(function ($event, $eventName) use ($deployCompleted): void {
                $expectedEventName = $deployCompleted
                    ? TokenEvents::DEPLOYED
                    : TokenEvents::CONNECTED;

                $this->assertSame($expectedEventName, $eventName);
            });

        return $eventDispatcher;
    }

    private function mockCryptoManager(): CryptoManagerInterface
    {
        $cryptoManager = $this->createMock(CryptoManagerInterface::class);
        $cryptoManager->method('findBySymbol')
            ->willReturnCallback(function (string $symbol): Crypto {
                return $this->mockCrypto($symbol);
            });

        return $cryptoManager;
    }

    private function mockMarketFactory(int $count = 0): MarketFactoryInterface
    {
        $factory = $this->createMock(MarketFactoryInterface::class);
        $factory->expects($this->exactly($count))
            ->method('create')
            ->willReturn(new Market($this->mockCrypto(), $this->mockToken(false, false, false, false)));

        return $factory;
    }

    private function mockMarketStatusManager(int $count = 0): MarketStatusManagerInterface
    {
        $manager = $this->createMock(MarketStatusManagerInterface::class);
        $manager->expects($this->exactly($count))
            ->method('updateMarketStatusNetworks');

        return $manager;
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

    private function mockTokenRepository(
        bool $tokenExist,
        bool $tokenDeployExist,
        bool $isTokenDeploy,
        bool $isMainDeploy,
        bool $isReferencer
    ): TokenRepository {
        $tokenRepository = $this->createMock(TokenRepository::class);
        $tokenRepository->method('findOneBy')
            ->willReturn($tokenExist ? $this->mockToken(
                $tokenDeployExist,
                $isTokenDeploy,
                $isMainDeploy,
                $isReferencer
            ) : null);

        return $tokenRepository;
    }

    private function mockToken(
        bool $isTokenDeployExist,
        bool $isTokenDeploy,
        bool $isMainDeploy,
        bool $isReferencer
    ): Token {
        $tokenCryptoId = 1;
        $mainDeployId = $isMainDeploy
            ? 1
            : 2;
        $token = $this->createMock(Token::class);
        $token->method('getDeployByCrypto')
            ->willReturnCallback(function (Crypto $crypto) use ($isTokenDeployExist, $tokenCryptoId): ?TokenDeploy {
                return $isTokenDeployExist
                    ? $this->mockTokenDeploy($tokenCryptoId, $crypto->getSymbol())
                    : null;
            });

        $token->method('getMainDeploy')
            ->willReturn($isTokenDeployExist ? $this->mockTokenDeploy($mainDeployId) : null);

        $token->method('getProfile')
            ->willReturn($this->mockTokenProfile($isMainDeploy, $isReferencer));

        $token->method('getDeployed')
            ->willReturn($isTokenDeploy);

        $token->method('getLastDeploy')
            ->willReturn($isTokenDeployExist ? $this->mockTokenDeploy($tokenCryptoId) : null);

        $token->method('getLockIn')->willReturn($this->mockLockIn($isMainDeploy));

        $token->expects($isMainDeploy ? $this->once() : $this->never())
            ->method('setShowDeployedModal');

        $tokenCrypto = (new TokenCrypto())
            ->setCrypto($this->mockCrypto())
            ->setToken($token);

        $token->method('getExchangeCryptos')
            ->willReturn(new ArrayCollection([ $tokenCrypto ]));

        return $token;
    }

    private function mockTokenDeploy(int $id, string $cryptoSymbol = 'WEB'): TokenDeploy
    {
        $tokenDeploy = $this->createMock(TokenDeploy::class);
        $tokenDeploy->method('getId')
            ->willReturn($id);

        $tokenDeploy->method('getCrypto')
            ->willReturn($this->mockCrypto($cryptoSymbol));

        $tokenDeploy->method('getToken')
            ->willReturn($this->mockToken(false, false, false, false));

        return $tokenDeploy;
    }

    private function mockCrypto(string $symbol = 'WEB'): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto
            ->method('getMoneySymbol')
            ->willReturn($symbol);
        $crypto
            ->method("getSymbol")
            ->willReturn($symbol);

        return $crypto;
    }

    private function mockTokenProfile(bool $isMainDeploy, bool $isReferencer): Profile
    {
        $profile = $this->createMock(Profile::class);
        $profile->method('getUser')
            ->willReturn($this->mockUser($isMainDeploy, $isReferencer));

        return $profile;
    }

    private function mockUser(bool $isMainDeploy, bool $isReferencer): User
    {
        $user = $this->createMock(User::class);
        $user->expects($isMainDeploy ? $this->once() : $this->never())
            ->method('getReferencer')
            ->willReturn($isReferencer ? $this->mockUser(false, false) : null);

        return $user;
    }

    private function mockLockIn(bool $isMainDeploy): LockIn
    {
        $lockIn = $this->createMock(LockIn::class);
        $lockIn->expects($isMainDeploy ? $this->once() : $this->never())
            ->method('setReleasedAtStart');
        $lockIn->expects($isMainDeploy ? $this->once() : $this->never())
            ->method('setAmountToRelease');
        $lockIn->expects($isMainDeploy ? $this->once() : $this->never())
            ->method('getReleasedAmount')
            ->willReturn(new Money(0, new Currency('WEB')));
        $lockIn->expects($isMainDeploy ? $this->once() : $this->never())
            ->method('getFrozenAmount')
            ->willReturn(new Money(0, new Currency('WEB')));

        return $lockIn;
    }

    private function mockMoneyWrapper(): MoneyWrapperInterface
    {
        return $this->createMock(MoneyWrapperInterface::class);
    }
}
