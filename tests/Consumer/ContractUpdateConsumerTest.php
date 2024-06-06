<?php declare(strict_types = 1);

namespace App\Tests\Consumer;

use App\Consumers\ContractUpdateConsumer;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\Token\TokenDeploy;
use App\Entity\Token\TokenReleaseAddressHistory;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenDeployManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Repository\TokenReleaseAddressHistoryRepository;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ContractUpdateConsumerTest extends TestCase
{
    private const TOKEN = 'test_token';
    private const CRYPTO = 'WEB';
    private const TOKEN_ADDRESS = 'test_token_address';
    private const MINT_DESTINATION = 'test_mint_destination_address';
    public function testExecuteFailsIfDBConnectionFailed(): void
    {
        $consumer = new ContractUpdateConsumer(
            $this->mockLogger(0, null, 'couldn\'t connect to DB'),
            $this->mockEntityManager(false),
            $this->mockMoneyWrapper(),
            $this->mockTokenManager(),
            $this->mockTokenDeployManager(),
            $this->mockCryptoManager(),
            $this->mockTokenReleaseAddressHistoryRepository()
        );

        $this->assertFalse($consumer->execute($this->mockMessage((string)json_encode([]))));
    }

    public function testExecutionStopIfItFailedToParseTheMessage(): void
    {
        $consumer = new ContractUpdateConsumer(
            $this->mockLogger(1, 'Failed to parse incoming message'),
            $this->mockEntityManager(),
            $this->mockMoneyWrapper(),
            $this->mockTokenManager(),
            $this->mockTokenDeployManager(),
            $this->mockCryptoManager(),
            $this->mockTokenReleaseAddressHistoryRepository()
        );

        $this->assertTrue($consumer->execute($this->mockMessage('[]')));
    }

    public function testExecutionStopIfMethodIsChangeMintDestinationAndFailedToParseIt(): void
    {
        $consumer = new ContractUpdateConsumer(
            $this->mockLogger(1, '(change mint destination) Failed to parse incoming message'),
            $this->mockEntityManager(),
            $this->mockMoneyWrapper(),
            $this->mockTokenManager(),
            $this->mockTokenDeployManager(),
            $this->mockCryptoManager(),
            $this->mockTokenReleaseAddressHistoryRepository()
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            'method' => $consumer::CHANGE_MINT_DESTINATION,
            'message' => ['TEST'],
        ]))));
    }


    public function testExecutionStopIfMethodIsUpdateMintedAmountAndFailedToParseIt(): void
    {
        $consumer = new ContractUpdateConsumer(
            $this->mockLogger(1, '(update mint amount) Failed to parse incoming message'),
            $this->mockEntityManager(),
            $this->mockMoneyWrapper(),
            $this->mockTokenManager(),
            $this->mockTokenDeployManager(),
            $this->mockCryptoManager(),
            $this->mockTokenReleaseAddressHistoryRepository()
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            'method' => $consumer::UPDATE_MINTED_AMOUNT,
            'message' => ['TEST'],
        ]))));
    }

    public function testExecutionStopIfInvalidMethod(): void
    {
        $consumer = new ContractUpdateConsumer(
            $this->mockLogger(1, 'Invalid method'),
            $this->mockEntityManager(),
            $this->mockMoneyWrapper(),
            $this->mockTokenManager(),
            $this->mockTokenDeployManager(),
            $this->mockCryptoManager(),
            $this->mockTokenReleaseAddressHistoryRepository()
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            'method' => "wrong method",
            'message' => ['TEST'],
        ]))));
    }

    public function testExecutionStopIfMethodIsChangeMintDestinationAndTokenNotDeployed(): void
    {
        $consumer = new ContractUpdateConsumer(
            $this->mockLogger(1, 'Invalid token address'),
            $this->mockEntityManager(true, 1),
            $this->mockMoneyWrapper(),
            $this->mockTokenManager(),
            $this->mockTokenDeployManager(1),
            $this->mockCryptoManager(),
            $this->mockTokenReleaseAddressHistoryRepository()
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            'method' => $consumer::CHANGE_MINT_DESTINATION,
            'message' => [
                'tokenAddress' => 'wrong address',
                'mintDestination' => self::MINT_DESTINATION,
                'lock' => false,
            ],
        ]))));
    }

    public function testExecutionStopIfMethodIsChangeMintDestinationAndTokenNotMainDeploy(): void
    {
        $consumer = new ContractUpdateConsumer(
            $this->mockLogger(1, 'token address is not from main deploy'),
            $this->mockEntityManager(true, 1),
            $this->mockMoneyWrapper(),
            $this->mockTokenManager(),
            $this->mockTokenDeployManager(1, false),
            $this->mockCryptoManager(),
            $this->mockTokenReleaseAddressHistoryRepository()
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            'method' => $consumer::CHANGE_MINT_DESTINATION,
            'message' => [
                'tokenAddress' => self::TOKEN_ADDRESS,
                'mintDestination' => self::MINT_DESTINATION,
                'lock' => false,
            ],
        ]))));
    }

    public function testExecutionSuccessWithChangeMintDestinationMethod(): void
    {
        $consumer = new ContractUpdateConsumer(
            $this->mockLogger(1),
            $this->mockEntityManager(true, 1, 2, 1),
            $this->mockMoneyWrapper(),
            $this->mockTokenManager(),
            $this->mockTokenDeployManager(1, true, self::MINT_DESTINATION),
            $this->mockCryptoManager(),
            $this->mockTokenReleaseAddressHistoryRepository()
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            'method' => $consumer::CHANGE_MINT_DESTINATION,
            'message' => [
                'tokenAddress' => self::TOKEN_ADDRESS,
                'mintDestination' => self::MINT_DESTINATION,
                'lock' => false,
            ],
        ]))));
    }

    public function testExecutionSuccessWithChangeMintDestinationMethodWithoutAddressHistory(): void
    {
        $consumer = new ContractUpdateConsumer(
            $this->mockLogger(1, 'Could not find any pending TokenReleaseAddressHistory to update'),
            $this->mockEntityManager(true, 1, 1, 1),
            $this->mockMoneyWrapper(),
            $this->mockTokenManager(),
            $this->mockTokenDeployManager(1, true, self::MINT_DESTINATION),
            $this->mockCryptoManager(),
            $this->mockTokenReleaseAddressHistoryRepository(false)
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            'method' => $consumer::CHANGE_MINT_DESTINATION,
            'message' => [
                'tokenAddress' => self::TOKEN_ADDRESS,
                'mintDestination' => self::MINT_DESTINATION,
                'lock' => false,
            ],
        ]))));
    }

    public function testExecutionSuccessWithChangeMintDestinationMethodWithoutNewAddress(): void
    {
        $mintDestination = '';
        $consumer = new ContractUpdateConsumer(
            $this->mockLogger(1),
            $this->mockEntityManager(true, 1, 2, 1),
            $this->mockMoneyWrapper(),
            $this->mockTokenManager(),
            $this->mockTokenDeployManager(1, true, $mintDestination),
            $this->mockCryptoManager(),
            $this->mockTokenReleaseAddressHistoryRepository(true, false)
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            'method' => $consumer::CHANGE_MINT_DESTINATION,
            'message' => [
                'tokenAddress' => self::TOKEN_ADDRESS,
                'mintDestination' => $mintDestination,
                'lock' => false,
            ],
        ]))));
    }

    public function testExecutionSuccessWithChangeMintDestinationMethodWithOldAddress(): void
    {
        $consumer = new ContractUpdateConsumer(
            $this->mockLogger(1),
            $this->mockEntityManager(true, 1, 2, 1),
            $this->mockMoneyWrapper(),
            $this->mockTokenManager(),
            $this->mockTokenDeployManager(1, true, self::MINT_DESTINATION),
            $this->mockCryptoManager(),
            $this->mockTokenReleaseAddressHistoryRepository(
                true,
                false,
                self::MINT_DESTINATION
            )
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            'method' => $consumer::CHANGE_MINT_DESTINATION,
            'message' => [
                'tokenAddress' => self::TOKEN_ADDRESS,
                'mintDestination' => self::MINT_DESTINATION,
                'lock' => false,
            ],
        ]))));
    }

    public function testExecutionWithChangeMintDestinationResultingInException(): void
    {
        $tokenDeployManager = $this->createMock(TokenDeployManagerInterface::class);
        $tokenDeployManager->method('findByAddress')->willThrowException(new \Exception());

        $consumer = new ContractUpdateConsumer(
            $this->mockLogger(1, null, 'Failed to update token address'),
            $this->mockEntityManager(true, 1),
            $this->mockMoneyWrapper(),
            $this->mockTokenManager(),
            $tokenDeployManager,
            $this->mockCryptoManager(),
            $this->mockTokenReleaseAddressHistoryRepository()
        );

        $this->assertFalse($consumer->execute($this->mockMessage((string)json_encode([
            'method' => $consumer::CHANGE_MINT_DESTINATION,
            'message' => [
                'tokenAddress' => self::TOKEN_ADDRESS,
                'mintDestination' => self::MINT_DESTINATION,
                'lock' => false,
            ],
        ]))));
    }

    public function testExecutionStopIfMethodIsUpdateMintedAmountAndNoTokenFound(): void
    {
        $consumer = new ContractUpdateConsumer(
            $this->mockLogger(1, 'Invalid token name'),
            $this->mockEntityManager(true, 1),
            $this->mockMoneyWrapper(),
            $this->mockTokenManager(1),
            $this->mockTokenDeployManager(),
            $this->mockCryptoManager(),
            $this->mockTokenReleaseAddressHistoryRepository()
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            'method' => $consumer::UPDATE_MINTED_AMOUNT,
            'message' => [
                'token' => 'invalid token name',
                'crypto' => self::CRYPTO,
                'value' => 'TEST',
            ],
        ]))));
    }

    public function testExecutionStopIfMethodIsUpdateMintedAmountAndTokenNotDeployed(): void
    {
        $consumer = new ContractUpdateConsumer(
            $this->mockLogger(1, 'skipping minted amount update, / is not main deploy'),
            $this->mockEntityManager(true, 1),
            $this->mockMoneyWrapper(),
            $this->mockTokenManager(1, false),
            $this->mockTokenDeployManager(),
            $this->mockCryptoManager(1),
            $this->mockTokenReleaseAddressHistoryRepository()
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            'method' => $consumer::UPDATE_MINTED_AMOUNT,
            'message' => [
                'token' => self::TOKEN,
                'crypto' => self::CRYPTO,
                'value' => 'TEST',
            ],
        ]))));
    }

    public function testExecutionStopIfMethodIsUpdateMintedAmountAndTokenNotMainDeploy(): void
    {
        $consumer = new ContractUpdateConsumer(
            $this->mockLogger(1, 'skipping minted amount update, / is not main deploy'),
            $this->mockEntityManager(true, 1),
            $this->mockMoneyWrapper(),
            $this->mockTokenManager(1, true, false),
            $this->mockTokenDeployManager(),
            $this->mockCryptoManager(1),
            $this->mockTokenReleaseAddressHistoryRepository()
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            'method' => $consumer::UPDATE_MINTED_AMOUNT,
            'message' => [
                'token' => self::TOKEN,
                'crypto' => self::CRYPTO,
                'value' => 'TEST',
            ],
        ]))));
    }

    public function testExecutionSuccessWithUpdateMintedAmountMethod(): void
    {
        $consumer = new ContractUpdateConsumer(
            $this->mockLogger(1),
            $this->mockEntityManager(true, 1, 1, 1),
            $this->mockMoneyWrapper(1),
            $this->mockTokenManager(1, true, true, true),
            $this->mockTokenDeployManager(),
            $this->mockCryptoManager(1),
            $this->mockTokenReleaseAddressHistoryRepository()
        );

        $this->assertTrue($consumer->execute($this->mockMessage((string)json_encode([
            'method' => $consumer::UPDATE_MINTED_AMOUNT,
            'message' => [
                'token' => self::TOKEN,
                'crypto' => self::CRYPTO,
                'value' => 'TEST',
            ],
        ]))));
    }
    public function testExecutionExceptionWithUpdateMintedAmountMethod(): void
    {
        $tokenManager = $this->createMock(TokenManagerInterface::class);
        $tokenManager->method('findByName')->willThrowException(new \Exception());

        $consumer = new ContractUpdateConsumer(
            $this->mockLogger(1, null, 'Failed to update token minted amount'),
            $this->mockEntityManager(true, 1),
            $this->mockMoneyWrapper(),
            $tokenManager,
            $this->mockTokenDeployManager(),
            $this->mockCryptoManager(),
            $this->mockTokenReleaseAddressHistoryRepository()
        );

        $this->assertFalse($consumer->execute($this->mockMessage((string)json_encode([
            'method' => $consumer::UPDATE_MINTED_AMOUNT,
            'message' => [
                'token' => self::TOKEN,
                'crypto' => self::CRYPTO,
                'value' => 'TEST',
            ],
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


    private function mockCryptoManager(
        int $calledCount = 0
    ): CryptoManagerInterface {
        $cryptoManager = $this->createMock(CryptoManagerInterface::class);
        $cryptoManager
            ->expects($this->exactly($calledCount))
            ->method('findBySymbol')
            ->willReturnCallback(function ($cryptoSymbol): ?Crypto {
                return self::CRYPTO === $cryptoSymbol
                    ? $this->mockCrypto()
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

    private function mockMoneyWrapper(int $calledCount = 0): MoneyWrapperInterface
    {
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);

        $moneyWrapper->expects($this->exactly($calledCount))
            ->method('parse')
            ->willReturn(new Money(0, new Currency('TOK')));

        return $moneyWrapper;
    }

    private function mockTokenManager(
        int $calledCount = 0,
        bool $isDeploy = true,
        bool $isMainDeploy = true,
        bool $isSuccess = false
    ): TokenManagerInterface {
        $tokenManager = $this->createMock(TokenManagerInterface::class);
        $tokenManager
            ->expects($this->exactly($calledCount))
            ->method('findByName')
            ->willReturnCallback(function ($tokenName) use ($isDeploy, $isMainDeploy, $isSuccess): ?Token {
                return self::TOKEN === $tokenName
                    ? $this->mockTokenForCrypto($isDeploy, $isMainDeploy, $isSuccess)
                    : null;
            });

        return $tokenManager;
    }

    private function mockTokenDeployManager(
        int $calledCount = 0,
        bool $isMainDeploy = true,
        ?string $mintDestination = null
    ): TokenDeployManagerInterface {
        $tokenMainDeployId = 1;
        $tokenDeployId = $isMainDeploy
            ? 1
            : 2;

        $tokenDeployManager = $this->createMock(TokenDeployManagerInterface::class);
        $tokenDeployManager
            ->expects($this->exactly($calledCount))
            ->method('findByAddress')
            ->willReturnCallback(
                function ($tokenAddress) use ($tokenDeployId, $tokenMainDeployId, $mintDestination): ?TokenDeploy {
                    return self::TOKEN_ADDRESS === $tokenAddress
                        ? $this->mockTokenDeploy($tokenDeployId, $tokenMainDeployId, $mintDestination)
                        : null;
                }
            );

        return $tokenDeployManager;
    }

    private function mockTokenDeploy(
        int $id = 1,
        ?int $tokenMainDeployId = null,
        ?string $mintDestination = null
    ): TokenDeploy {
        $tokenDeploy = $this->createMock(TokenDeploy::class);

        $tokenDeploy->method('getToken')
            ->willReturn($this->mockToken($tokenMainDeployId, $mintDestination));

        $tokenDeploy->method('getId')
            ->willReturn($id);

        return $tokenDeploy;
    }

    private function mockToken(?int $id, ?string $mintDestination = null): Token
    {
        $token = $this->createMock(Token::class);

        $token->method('getMainDeploy')->willReturn($id ? $this->mocktokenDeploy($id) : null);

        $token->method('getDeployByCrypto')->willReturn($id ? $this->mocktokenDeploy($id) : null);

        $token->expects(null !== $mintDestination ? $this->once() : $this->never())
            ->method('setMintDestination')
            ->with($mintDestination);

        return $token;
    }

    private function mockCrypto(): Crypto
    {
        return $this->createMock(Crypto::class);
    }

    private function mockTokenForCrypto(bool $isDeploy, bool $isMainDeploy, bool $isSuccess): Token
    {
        $token = $this->createMock(Token::class);

        $token->method('getMainDeploy')
            ->willReturn($this->mockTokenDeploy($isMainDeploy ? 1 : 2));

        $token->method('getDeployByCrypto')->willReturn($isDeploy ? $this->mocktokenDeploy() : null);

        $token->expects($isSuccess ? $this->once() : $this->never())
            ->method('setMintedAmount');

        $token->expects($isSuccess ? $this->once() : $this->never())
            ->method('getMintedAmount')
            ->willReturn(new Money(0, new Currency('TOK')));

        return $token;
    }

    private function mockTokenReleaseAddressHistoryRepository(
        bool $historyExists = true,
        bool $setPaidStatus = true,
        string $oldAddress = ''
    ): TokenReleaseAddressHistoryRepository {
        $repo = $this->createMock(TokenReleaseAddressHistoryRepository::class);
        $repo
            ->method('findLatestPending')
            ->willReturnCallback(
                function () use ($historyExists, $setPaidStatus, $oldAddress): ?TokenReleaseAddressHistory {
                    if (!$historyExists) {
                        return null;
                    }

                    $entity = $this->createMock(TokenReleaseAddressHistory::class);
                    $entity
                        ->expects($setPaidStatus ? $this->once() : $this->never())
                        ->method('setPaidStatus');

                    $entity
                        ->expects(!$setPaidStatus ? $this->once() : $this->never())
                        ->method('setErrorStatus');

                    $entity
                        ->method('getOldAddress')
                        ->willReturn($oldAddress);

                    return $entity;
                }
            );

        return $repo;
    }
}
