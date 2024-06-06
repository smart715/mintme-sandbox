<?php declare(strict_types = 1);

namespace App\Tests\Consumer;

use App\Communications\Exception\FetchException;
use App\Consumers\DepositConsumer;
use App\Entity\Crypto;
use App\Entity\DepositHash;
use App\Entity\Token\Token;
use App\Entity\Token\TokenDeploy;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use App\Repository\DepositHashRepository;
use App\Security\DisabledServicesVoter;
use App\Wallet\Deposit\DepositGatewayCommunicator;
use App\Wallet\Deposit\Model\DepositCredentials;
use App\Wallet\Deposit\Model\ValidDeposit;
use App\Wallet\Model\BlockchainTransaction;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\WalletInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Security;

class DepositConsumerTest extends TestCase
{
    private const USER_ID = 1;
    private const TRANSACTION_CRYPTO = 'WEB';
    private const TRANSACTION_TOKEN = 'Token';
    private const TRANSACTION_HASH = 'transaction_hash';
    private const TRANSACTION_ADDRESS = 'transaction_address';
    private const TRANSACTION_AMOUNT = '1000';
    private Crypto $crypto;
    private Token $token;

    protected function setUp(): void
    {
        $this->crypto = $this->mockCrypto();
        $this->token = $this->mockToken();
    }

    public function testDbConnectionFails(): void
    {
        $dc = new DepositConsumer(
            $this->mockBalanceHandler(false, false),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(0, 'couldn\'t connect to DB'),
            $this->mockMoneyWrapper(),
            $this->mockWallet(),
            $this->mockEntityManager(false),
            $this->mockEventDispatcher(),
            $this->mockContainer(),
            $this->mockSecurity(),
            $this->mockDepositGatewayCommunicator(),
            $this->mockDepositHashRepository()
        );

        $this->assertFalse(
            $dc->execute($this->mockMessage(''))
        );
    }

    public function testMessageParseError(): void
    {
        $dc = new DepositConsumer(
            $this->mockBalanceHandler(),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(1, 'Failed to parse incoming message'),
            $this->mockMoneyWrapper(),
            $this->mockWallet(),
            $this->mockEntityManager(true, 1),
            $this->mockEventDispatcher(),
            $this->mockContainer(),
            $this->mockSecurity(),
            $this->mockDepositGatewayCommunicator(),
            $this->mockDepositHashRepository()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage(''))
        );
    }

    public function testUndefinedUser(): void
    {
        $dc = new DepositConsumer(
            $this->mockBalanceHandler(),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(1, 'Received new message with undefined user.'),
            $this->mockMoneyWrapper(),
            $this->mockWallet(),
            $this->mockEntityManager(true, 1),
            $this->mockEventDispatcher(),
            $this->mockContainer(),
            $this->mockSecurity(),
            $this->mockDepositGatewayCommunicator(),
            $this->mockDepositHashRepository()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => -1,
                'hashes' => [self::TRANSACTION_HASH],
                'amount' => self::TRANSACTION_AMOUNT,
                'forwardedAmount' => self::TRANSACTION_AMOUNT,
                'address' => self::TRANSACTION_ADDRESS,
                'asset' => self::TRANSACTION_CRYPTO,
                'cryptoNetwork' => self::TRANSACTION_CRYPTO,
            ])))
        );
    }

    public function testAssetNotFound(): void
    {
        $dc = new DepositConsumer(
            $this->mockBalanceHandler(),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(1, 'Asset not found: INVALID_ASSET'),
            $this->mockMoneyWrapper(),
            $this->mockWallet(),
            $this->mockEntityManager(true, 1),
            $this->mockEventDispatcher(),
            $this->mockContainer(),
            $this->mockSecurity(),
            $this->mockDepositGatewayCommunicator(),
            $this->mockDepositHashRepository()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => self::USER_ID,
                'hashes' => [self::TRANSACTION_HASH],
                'amount' => self::TRANSACTION_AMOUNT,
                'forwardedAmount' => self::TRANSACTION_AMOUNT,
                'address' => self::TRANSACTION_ADDRESS,
                'asset' => 'INVALID_ASSET',
                'cryptoNetwork' => self::TRANSACTION_CRYPTO,
            ])))
        );
    }

    public function testInvalidCryptoNetwork(): void
    {
        $dc = new DepositConsumer(
            $this->mockBalanceHandler(),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(1, 'Invalid crypto network. INVALID_CRYPTO_NETWORK'),
            $this->mockMoneyWrapper(),
            $this->mockWallet(),
            $this->mockEntityManager(true, 1),
            $this->mockEventDispatcher(),
            $this->mockContainer(),
            $this->mockSecurity(),
            $this->mockDepositGatewayCommunicator(),
            $this->mockDepositHashRepository()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => self::USER_ID,
                'hashes' => [self::TRANSACTION_HASH],
                'amount' => self::TRANSACTION_AMOUNT,
                'forwardedAmount' => self::TRANSACTION_AMOUNT,
                'address' => self::TRANSACTION_ADDRESS,
                'asset' => self::TRANSACTION_CRYPTO,
                'cryptoNetwork' => 'INVALID_CRYPTO_NETWORK',
            ])))
        );
    }

    public function testTokenDepositsAreDisabled(): void
    {
        $dc = new DepositConsumer(
            $this->mockBalanceHandler(),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(1, 'Token deposits are disabled. Canceled.'),
            $this->mockMoneyWrapper(),
            $this->mockWallet(),
            $this->mockEntityManager(true, 1),
            $this->mockEventDispatcher(),
            $this->mockContainer(),
            $this->mockSecurity(false),
            $this->mockDepositGatewayCommunicator(),
            $this->mockDepositHashRepository()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => self::USER_ID,
                'hashes' => [self::TRANSACTION_HASH],
                'amount' => self::TRANSACTION_AMOUNT,
                'forwardedAmount' => self::TRANSACTION_AMOUNT,
                'address' => self::TRANSACTION_ADDRESS,
                'asset' => self::TRANSACTION_TOKEN,
                'cryptoNetwork' => self::TRANSACTION_CRYPTO,
            ])))
        );
    }

    public function testCryptoDepositsAreDisabled(): void
    {
        $dc = new DepositConsumer(
            $this->mockBalanceHandler(),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(1, 'Crypto deposits are disabled. Cancelled.'),
            $this->mockMoneyWrapper(),
            $this->mockWallet(),
            $this->mockEntityManager(true, 1),
            $this->mockEventDispatcher(),
            $this->mockContainer(),
            $this->mockSecurity(true, false),
            $this->mockDepositGatewayCommunicator(),
            $this->mockDepositHashRepository()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => self::USER_ID,
                'hashes' => [self::TRANSACTION_HASH],
                'amount' => self::TRANSACTION_AMOUNT,
                'forwardedAmount' => self::TRANSACTION_AMOUNT,
                'address' => self::TRANSACTION_ADDRESS,
                'asset' => self::TRANSACTION_CRYPTO,
                'cryptoNetwork' => self::TRANSACTION_CRYPTO,
            ])))
        );
    }

    public function testDepositForThisCryptoWasDisabled(): void
    {
        $dc = new DepositConsumer(
            $this->mockBalanceHandler(),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(1, 'Deposit for this crypto was disabled. Cancelled.'),
            $this->mockMoneyWrapper(),
            $this->mockWallet(),
            $this->mockEntityManager(true, 1),
            $this->mockEventDispatcher(),
            $this->mockContainer(),
            $this->mockSecurity(true, true, false),
            $this->mockDepositGatewayCommunicator(),
            $this->mockDepositHashRepository()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => self::USER_ID,
                'hashes' => [self::TRANSACTION_HASH],
                'amount' => self::TRANSACTION_AMOUNT,
                'forwardedAmount' => self::TRANSACTION_AMOUNT,
                'address' => self::TRANSACTION_ADDRESS,
                'asset' => self::TRANSACTION_CRYPTO,
                'cryptoNetwork' => self::TRANSACTION_CRYPTO,
            ])))
        );
    }

    public function testDepositIsInvalidInGatewaySide(): void
    {
        $dc = new DepositConsumer(
            $this->mockBalanceHandler(),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(1, 'Deposit is invalid in gateway side'),
            $this->mockMoneyWrapper(),
            $this->mockWallet(),
            $this->mockEntityManager(true, 1),
            $this->mockEventDispatcher(),
            $this->mockContainer(),
            $this->mockSecurity(),
            $this->mockDepositGatewayCommunicator(
                $this->never(),
                $this->never(),
                false
            ),
            $this->mockDepositHashRepository()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => self::USER_ID,
                'hashes' => [self::TRANSACTION_HASH],
                'amount' => self::TRANSACTION_AMOUNT,
                'forwardedAmount' => self::TRANSACTION_AMOUNT,
                'address' => self::TRANSACTION_ADDRESS,
                'asset' => self::TRANSACTION_CRYPTO,
                'cryptoNetwork' => self::TRANSACTION_CRYPTO,
            ])))
        );
    }

    public function testFetchException(): void
    {
        $dc = new DepositConsumer(
            $this->mockBalanceHandler(),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(1, 'Invalid message received and Transaction was rollback'),
            $this->mockMoneyWrapper(),
            $this->mockWallet(),
            $this->mockEntityManager(true, 1),
            $this->mockEventDispatcher(),
            $this->mockContainer(),
            $this->mockSecurity(),
            $this->mockDepositGatewayCommunicator(
                $this->never(),
                $this->never(),
                true,
                true
            ),
            $this->mockDepositHashRepository()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => self::USER_ID,
                'hashes' => [self::TRANSACTION_HASH],
                'amount' => self::TRANSACTION_AMOUNT,
                'forwardedAmount' => self::TRANSACTION_AMOUNT,
                'address' => self::TRANSACTION_ADDRESS,
                'asset' => self::TRANSACTION_CRYPTO,
                'cryptoNetwork' => self::TRANSACTION_CRYPTO,
            ])))
        );
    }

    public function testDepositHashIsDuplicated(): void
    {
        $dc = new DepositConsumer(
            $this->mockBalanceHandler(),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(1, 'Deposit hash is duplicated: transaction_hash'),
            $this->mockMoneyWrapper(),
            $this->mockWallet(),
            $this->mockEntityManager(true, 1),
            $this->mockEventDispatcher(),
            $this->mockContainer(),
            $this->mockSecurity(),
            $this->mockDepositGatewayCommunicator(),
            $this->mockDepositHashRepository(true)
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => self::USER_ID,
                'hashes' => [self::TRANSACTION_HASH],
                'amount' => self::TRANSACTION_AMOUNT,
                'forwardedAmount' => self::TRANSACTION_AMOUNT,
                'address' => self::TRANSACTION_ADDRESS,
                'asset' => self::TRANSACTION_CRYPTO,
                'cryptoNetwork' => self::TRANSACTION_CRYPTO,
            ])))
        );
    }

    public function testDepositAddressIsInvalid(): void
    {
        $dc = new DepositConsumer(
            $this->mockBalanceHandler(),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(1, 'Expect deposit address NOT_VALID_ADDRESS received transaction_address'),
            $this->mockMoneyWrapper(),
            $this->mockWallet(),
            $this->mockEntityManager(true, 1),
            $this->mockEventDispatcher(),
            $this->mockContainer(),
            $this->mockSecurity(),
            $this->mockDepositGatewayCommunicator($this->once()),
            $this->mockDepositHashRepository()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => self::USER_ID,
                'hashes' => [self::TRANSACTION_HASH],
                'amount' => self::TRANSACTION_AMOUNT,
                'forwardedAmount' => self::TRANSACTION_AMOUNT,
                'address' => 'NOT_VALID_ADDRESS',
                'asset' => self::TRANSACTION_CRYPTO,
                'cryptoNetwork' => self::TRANSACTION_CRYPTO,
            ])))
        );
    }

    public function testInvalidAmount(): void
    {
        $dc = new DepositConsumer(
            $this->mockBalanceHandler(),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(1, 'Expect Transaction amount 1000123 received 1000'),
            $this->mockMoneyWrapper(),
            $this->mockWallet(),
            $this->mockEntityManager(true, 1),
            $this->mockEventDispatcher(),
            $this->mockContainer(),
            $this->mockSecurity(),
            $this->mockDepositGatewayCommunicator($this->once()),
            $this->mockDepositHashRepository()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => self::USER_ID,
                'hashes' => [self::TRANSACTION_HASH],
                'amount' => self::TRANSACTION_AMOUNT . '123',
                'forwardedAmount' => self::TRANSACTION_AMOUNT . '123',
                'address' => self::TRANSACTION_ADDRESS,
                'asset' => self::TRANSACTION_CRYPTO,
                'cryptoNetwork' => self::TRANSACTION_CRYPTO,
            ])))
        );
    }

    public function testExpectedAmountMatchesForwardedAmount(): void
    {
        $dc = new DepositConsumer(
            $this->mockBalanceHandler(true, false),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(2),
            $this->mockMoneyWrapper(),
            $this->mockWallet(),
            $this->mockEntityManager(true, 1, 1, 1),
            $this->mockEventDispatcher($this->once()),
            $this->mockContainer(),
            $this->mockSecurity(),
            $this->mockDepositGatewayCommunicator($this->once(), $this->once()),
            $this->mockDepositHashRepository()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => self::USER_ID,
                'hashes' => [self::TRANSACTION_HASH],
                'amount' => self::TRANSACTION_AMOUNT . '123',
                'forwardedAmount' => self::TRANSACTION_AMOUNT,
                'address' => self::TRANSACTION_ADDRESS,
                'asset' => self::TRANSACTION_TOKEN,
                'cryptoNetwork' => self::TRANSACTION_CRYPTO,
            ])))
        );
    }

    public function testExpectedAmountMatchesAmountOnly(): void
    {
        $dc = new DepositConsumer(
            $this->mockBalanceHandler(true, false),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(2),
            $this->mockMoneyWrapper(),
            $this->mockWallet(),
            $this->mockEntityManager(true, 1, 1, 1),
            $this->mockEventDispatcher($this->once()),
            $this->mockContainer(),
            $this->mockSecurity(),
            $this->mockDepositGatewayCommunicator($this->once(), $this->once()),
            $this->mockDepositHashRepository()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => self::USER_ID,
                'hashes' => [self::TRANSACTION_HASH],
                'amount' => self::TRANSACTION_AMOUNT,
                'forwardedAmount' => self::TRANSACTION_AMOUNT . '123',
                'address' => self::TRANSACTION_ADDRESS,
                'asset' => self::TRANSACTION_TOKEN,
                'cryptoNetwork' => self::TRANSACTION_CRYPTO,
            ])))
        );
    }

    public function testProcessCryptoFully(): void
    {
        $dc = new DepositConsumer(
            $this->mockBalanceHandler(true, false),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(2),
            $this->mockMoneyWrapper(),
            $this->mockWallet(),
            $this->mockEntityManager(true, 1, 1, 1),
            $this->mockEventDispatcher($this->once()),
            $this->mockContainer(),
            $this->mockSecurity(),
            $this->mockDepositGatewayCommunicator($this->once(), $this->once()),
            $this->mockDepositHashRepository()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => self::USER_ID,
                'hashes' => [self::TRANSACTION_HASH],
                'amount' => self::TRANSACTION_AMOUNT,
                'forwardedAmount' => self::TRANSACTION_AMOUNT,
                'address' => self::TRANSACTION_ADDRESS,
                'asset' => self::TRANSACTION_CRYPTO,
                'cryptoNetwork' => self::TRANSACTION_CRYPTO,
            ])))
        );
    }

    public function testProcessTokenFully(): void
    {
        $dc = new DepositConsumer(
            $this->mockBalanceHandler(true, false),
            $this->mockUserManager(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockLogger(2),
            $this->mockMoneyWrapper(),
            $this->mockWallet(),
            $this->mockEntityManager(true, 1, 1, 1),
            $this->mockEventDispatcher($this->once()),
            $this->mockContainer(),
            $this->mockSecurity(),
            $this->mockDepositGatewayCommunicator($this->once(), $this->once()),
            $this->mockDepositHashRepository()
        );

        $this->assertTrue(
            $dc->execute($this->mockMessage((string)json_encode([
                'userId' => self::USER_ID,
                'hashes' => [self::TRANSACTION_HASH],
                'amount' => self::TRANSACTION_AMOUNT,
                'forwardedAmount' => self::TRANSACTION_AMOUNT,
                'address' => self::TRANSACTION_ADDRESS,
                'asset' => self::TRANSACTION_TOKEN,
                'cryptoNetwork' => self::TRANSACTION_CRYPTO,
            ])))
        );
    }

    private function mockEntityManager(
        bool $isConnected = true,
        int $clearCount = 0,
        int $persistCount = 0,
        int $flushCount = 0
    ): EntityManagerInterface {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(($isConnected ? 0 : 2) + 1))
            ->method('getConnection')
            ->willReturn($this->mockConnection($isConnected));

        $entityManager->expects($this->exactly($clearCount))
            ->method('clear');
        $entityManager->expects($this->exactly($persistCount))
            ->method('persist');
        $entityManager->expects($this->exactly($flushCount))
            ->method('flush');

        return $entityManager;
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

    private function mockWallet(): WalletInterface
    {
        return $this->createMock(WalletInterface::class);
    }

    private function mockCrypto(): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getSymbol')->willReturn(self::TRANSACTION_CRYPTO);
        $crypto->method('getMoneySymbol')->willReturn(self::TRANSACTION_CRYPTO);

        return $crypto;
    }

    private function mockToken(): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getSymbol')->willReturn(self::TRANSACTION_TOKEN);
        $token->method('getMoneySymbol')->willReturn(self::TRANSACTION_TOKEN);
        $token->method('getDeployByCrypto')->willReturn($this->createMock(TokenDeploy::class));

        return $token;
    }

    private function mockMessage(string $message): AMQPMessage
    {
        $msg = $this->createMock(AMQPMessage::class);
        $msg->body = $message;

        return $msg;
    }

    private function mockBalanceHandler(
        bool $transactionStarted = true,
        bool $transactionRollback = true
    ): BalanceHandlerInterface {
        $bh = $this->createMock(BalanceHandlerInterface::class);
        $bh
            ->expects($transactionStarted ? $this->once() : $this->never())
            ->method('beginTransaction');
        $bh
            ->method('isTransactionStarted')
            ->willReturn($transactionStarted);
        $bh
            ->expects($transactionRollback ? $this->once() : $this->never())
            ->method('rollback');

        return $bh;
    }

    private function mockUserManager(): UserManagerInterface
    {
        $um = $this->createMock(UserManagerInterface::class);
        $um->method('find')->willReturnCallback(function (int $userId): ?User {
            return self::USER_ID === $userId
                ? $this->createMock(User::class)
                : null;
        });

        return $um;
    }

    private function mockCryptoManager(): CryptoManagerInterface
    {
        $cm = $this->createMock(CryptoManagerInterface::class);
        $cm->method('findBySymbol')
            ->willReturnCallback(function (string $cryptoSymbol): ?Crypto {
                return self::TRANSACTION_CRYPTO === $cryptoSymbol
                    ? $this->crypto
                    : null;
            });

        return $cm;
    }

    private function mockTokenManager(): TokenManagerInterface
    {
        $tm = $this->createMock(TokenManagerInterface::class);
        $tm->method('findByName')
            ->willReturnCallback(function (string $tokenName): ?Token {
                return self::TRANSACTION_TOKEN === $tokenName
                    ? $this->token
                    : null;
            });

        return $tm;
    }

    private function mockLogger(int $infoLogs = 0, ?string $errorMsg = null): LoggerInterface
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock
            ->expects($this->exactly($infoLogs))
            ->method('info');
        $loggerMock
            ->expects($errorMsg ? $this->once() : $this->never())
            ->method('error')
            ->willReturnCallback(function (string $message) use ($errorMsg): void {
                $this->assertStringContainsString($errorMsg, $message);
            });

        return $loggerMock;
    }

    private function mockMoneyWrapper(): MoneyWrapperInterface
    {
        $mw = $this->createMock(MoneyWrapperInterface::class);
        $mw->method('parse')->willReturnCallback(function (string $amount, string $symbol): Money {
            return new Money($amount, new Currency($symbol));
        });
        $mw->method('format')->willReturnCallback(function (Money $amount): string {
            return $amount->getAmount();
        });

        return $mw;
    }

    private function mockEventDispatcher(?InvokedCount $dispatch = null): EventDispatcherInterface
    {
        $ed = $this->createMock(EventDispatcherInterface::class);
        $ed->expects($dispatch ?? $this->never())->method('dispatch');

        return $ed;
    }

    private function mockContainer(): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturn($this->createMock(TokenStorageInterface::class));

        return $container;
    }

    private function mockSecurity(
        bool $tokenDepositEnabled = true,
        bool $cryptoDepositEnabled = true,
        bool $specificCryptoDepositEnabled = true
    ): Security {
        $security = $this->createMock(Security::class);
        $map = [
            [DisabledServicesVoter::TOKEN_DEPOSIT, null, $tokenDepositEnabled],
            [DisabledServicesVoter::COIN_DEPOSIT, null, $cryptoDepositEnabled],
            ['not-disabled', $this->crypto, $specificCryptoDepositEnabled],
        ];

        $security->method('isGranted')->willReturnMap($map);

        return $security;
    }

    private function mockBlockchainTransaction(): BlockchainTransaction
    {
        $bt = $this->createMock(BlockchainTransaction::class);
        $bt->method('getHash')->willReturn(self::TRANSACTION_HASH);
        $bt->method('getFee')->willReturn(null);
        $bt->method('getToAmounts')->willReturn([self::TRANSACTION_ADDRESS => self::TRANSACTION_AMOUNT]);

        return $bt;
    }

    private function mockDepositGatewayCommunicator(
        ?InvokedCount $imGBT = null,
        ?InvokedCount $imCD = null,
        bool $validDeposit = true,
        bool $validDepositFetchException = false
    ): DepositGatewayCommunicator {
        $dc = $this->createMock(DepositGatewayCommunicator::class);

        $validDepositMock = $this->createMock(ValidDeposit::class);
        $validDepositMock->method('getStatus')->willReturn($validDeposit);
        $dc->method('validateDeposit')->willReturn($validDepositMock);

        if ($validDepositFetchException) {
            $dc->method('validateDeposit')->willThrowException(new FetchException());
        }

        $dc->expects($imGBT ?? $this->never())
            ->method('getBlockchainTransaction')
            ->willReturn($this->mockBlockchainTransaction());

        $dc->expects($imCD ?? $this->never())
            ->method('confirmDeposit');


        $depositCredentialsMock = $this->createMock(DepositCredentials::class);
        $depositCredentialsMock->method('getAddress')->willReturn(self::TRANSACTION_ADDRESS);
        $dc->method('getDepositCredentials')->willReturn($depositCredentialsMock);

        return $dc;
    }

    private function mockDepositHashRepository(bool $hasDuplicateHash = false): DepositHashRepository
    {
        $dhr = $this->createMock(DepositHashRepository::class);

        if ($hasDuplicateHash) {
            $dhr->method('findByHash')->willReturn($this->createMock(DepositHash::class));
        }

        return $dhr;
    }
}
