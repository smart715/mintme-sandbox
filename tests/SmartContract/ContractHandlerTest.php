<?php declare(strict_types = 1);

namespace App\Tests\SmartContract;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Communications\JsonRpcResponse;
use App\Config\LimitHistoryConfig;
use App\Entity\Crypto;
use App\Entity\Profile;
use App\Entity\Token\LockIn;
use App\Entity\Token\Token;
use App\Entity\Token\TokenDeploy;
use App\Entity\User;
use App\Exchange\Config\TokenConfig;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\WrappedCryptoTokenManagerInterface;
use App\SmartContract\ContractHandler;
use App\SmartContract\Model\AddTokenResult;
use App\Utils\AssetType;
use App\Utils\Converter\TokenNameConverterInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currencies;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

class ContractHandlerTest extends TestCase
{
    public function testDeploy(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->once())->method('send')->with(
                'deploy',
                [
                    'name' => 'foo',
                    'decimals' => 4,
                    'releasedAtCreation' => '100000',
                    'releasePeriod' => 10,
                    'userId' => 1,
                    'crypto' => 'WEB',
                    'metadataUri' => null,
                ]
            )
            ->willReturn($this->mockResponse(false, []));

        $handler = new ContractHandler(
            $rpc,
            $this->mockLoggerInterface(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->mockLimitHistoryConfig(),
            $this->mockWrappedCryptoTokenManager(),
            $this->mockTokenNameConverter(),
            $this->mockRouter()
        );

        $deploy = (new TokenDeploy())
            ->setToken($this->mockToken(true))
            ->setCrypto($this->mockCrypto('WEB'));

        $handler->deploy($deploy, true);
    }

    public function testDeployThrowExceptionIfTokenHasNoReleasePeriod(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->never())->method('send');

        $handler = new ContractHandler(
            $rpc,
            $this->mockLoggerInterface(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->mockLimitHistoryConfig(),
            $this->mockWrappedCryptoTokenManager(),
            $this->mockTokenNameConverter(),
            $this->mockRouter()
        );

        $this->expectException(\Throwable::class);

        $deploy = (new TokenDeploy())
            ->setToken($this->mockToken(false))
            ->setCrypto($this->mockCrypto('WEB'));

        $handler->deploy($deploy, true);
    }

    public function testDeployThrowExceptionIfResponseError(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->once())->method('send')->with(
                'deploy',
                [
                    'name' => 'foo',
                    'decimals' => 4,
                    'releasedAtCreation' => '100000',
                    'releasePeriod' => 10,
                    'userId' => 1,
                    'crypto' => 'WEB',
                    'metadataUri' => null,
                ]
            )
            ->willReturn($this->mockResponse(true, []));

        $handler = new ContractHandler(
            $rpc,
            $this->mockLoggerInterface(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->mockLimitHistoryConfig(),
            $this->mockWrappedCryptoTokenManager(),
            $this->mockTokenNameConverter(),
            $this->mockRouter()
        );

        $this->expectException(\Throwable::class);

        $deploy = (new TokenDeploy())
            ->setToken($this->mockToken(true))
            ->setCrypto($this->mockCrypto('WEB'));

        $handler->deploy($deploy, true);
    }

    public function testAddToken(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->once())->method('send')->with(
                'add_token',
                [
                    'name' => 'foo',
                    'address' => '1',
                    'crypto' => 'WEB',
                    'minDeposit' => '1',
                    'isCrypto' => false,
                    'isPausable' => false,
                ]
            )
            ->willReturn($this->mockResponse(false, [
                'name' => 'foo',
                'decimals' => 4,
                'existed' => false,
                'isPausable' => false,
            ]));

        $handler = new ContractHandler(
            $rpc,
            $this->mockLoggerInterface(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->mockLimitHistoryConfig(),
            $this->mockWrappedCryptoTokenManager(),
            $this->mockTokenNameConverter(),
            $this->mockRouter()
        );

        $token = $this->mockToken(true, '1');

        $this->assertEquals(
            AddTokenResult::parse(['name' => 'foo', 'decimals' => 4, 'existed' => false, 'isPausable' => false]),
            $handler->addToken($token, $this->mockCrypto('WEB'), '1', '1')
        );
    }

    public function testAddTokenThrowExceptionIfResponseError(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->once())->method('send')->with(
                'add_token',
                [
                    'name' => 'foo',
                    'address' => '1',
                    'crypto' => 'WEB',
                    'minDeposit' => '1',
                    'isCrypto' => false,
                    'isPausable' => false,
                ]
            )
            ->willReturn($this->mockResponse(true, []));

        $handler = new ContractHandler(
            $rpc,
            $this->mockLoggerInterface(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->mockLimitHistoryConfig(),
            $this->mockWrappedCryptoTokenManager(),
            $this->mockTokenNameConverter(),
            $this->mockRouter()
        );

        $token = $this->mockToken(true, '1');

        $this->expectException(\Throwable::class);

        $handler->addToken($token, $this->mockCrypto('WEB'), '1', '1');
    }

    public function testUpdateMintDestination(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->once())->method('send')->with(
                'update_mint_destination',
                [
                    'name' => 'foo',
                    'crypto' => 'WEB',
                    'contractAddress' => '0x123',
                    'mintDestination' => '0x456',
                    'oldMintDestination' => '0x789',
                ]
            );

        $handler = new ContractHandler(
            $rpc,
            $this->mockLoggerInterface(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->mockLimitHistoryConfig(),
            $this->mockWrappedCryptoTokenManager(),
            $this->mockTokenNameConverter(),
            $this->mockRouter()
        );

        $handler->updateMintDestination(
            $this->mockToken(true, '0x123', 'deployed'),
            '0x456'
        );
    }

    public function testUpdateMintDestinationIfNotDeployed(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->never())->method('send');

        $handler = new ContractHandler(
            $rpc,
            $this->mockLoggerInterface(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->mockLimitHistoryConfig(),
            $this->mockWrappedCryptoTokenManager(),
            $this->mockTokenNameConverter(),
            $this->mockRouter()
        );

        $this->expectException(\Throwable::class);

        $handler->updateMintDestination(
            $this->mockToken(true, '0x123', 'not-deployed'),
            '0x456'
        );
    }

    public function testUpdateMintDestinationWithResponseError(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->once())->method('send')->with(
                'update_mint_destination',
                [
                    'name' => 'foo',
                    'crypto' => 'WEB',
                    'contractAddress' => '0x123',
                    'mintDestination' => '0x456',
                    'oldMintDestination' => '0x789',
                ]
            )
            ->willReturn($this->mockResponse(true));

        $handler = new ContractHandler(
            $rpc,
            $this->mockLoggerInterface(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->mockLimitHistoryConfig(),
            $this->mockWrappedCryptoTokenManager(),
            $this->mockTokenNameConverter(),
            $this->mockRouter()
        );

        $this->expectException(\Throwable::class);

        $handler->updateMintDestination(
            $this->mockToken(true, '0x123', 'deployed'),
            '0x456'
        );
    }

    public function testWithdraw(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->once())->method('send')->with(
                'transfer',
                [
                    'tokenName' => 'foo',
                    'to' => '0x123',
                    'value' => '1',
                    'userId' => 1,
                    'crypto' => 'WEB',
                    'tokenFee' => '1',
                    'tokenFeeCurrency' => 'TOK',
                ]
            );

        $handler = new ContractHandler(
            $rpc,
            $this->mockLoggerInterface(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->mockLimitHistoryConfig(),
            $this->mockWrappedCryptoTokenManager(),
            $this->mockTokenNameConverter(),
            $this->mockRouter()
        );

        $crypto = $this->mockCrypto('WEB');
        $token = $this->mockToken(true, '0x123', 'deployed');

        $deploy = (new TokenDeploy())
            ->setToken($token)
            ->setCrypto($crypto);

        $token->method('getDeployByCrypto')->willReturn($deploy);

        $handler->withdraw(
            $this->mockUser(1),
            new Money('1', new Currency(Symbols::WEB)),
            '0x123',
            $token,
            $crypto,
            new Money('1', new Currency(Symbols::TOK))
        );
    }

    public function testWithdrawIfNotDeployed(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->never())->method('send');

        $handler = new ContractHandler(
            $rpc,
            $this->mockLoggerInterface(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->mockLimitHistoryConfig(),
            $this->mockWrappedCryptoTokenManager(),
            $this->mockTokenNameConverter(),
            $this->mockRouter()
        );

        $crypto = $this->mockCrypto('WEB');
        $token = $this->mockToken(true, '0x123', 'not-deployed');

        $token->method('getDeployByCrypto')->willReturn(null);

        $this->expectException(\Throwable::class);

        $handler->withdraw(
            $this->mockUser(1),
            new Money('1', new Currency(Symbols::WEB)),
            '0x123',
            $token,
            $crypto,
            new Money('1', new Currency(Symbols::WEB))
        );
    }

    public function testWithdrawWithResponseError(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->once())->method('send')->with(
                'transfer',
                [
                    'tokenName' => 'foo',
                    'to' => '0x123',
                    'value' => '1',
                    'userId' => 1,
                    'crypto' => 'WEB',
                    'tokenFee' => '1',
                    'tokenFeeCurrency' => 'TOK',
                ]
            )->willReturn($this->mockResponse(true));

        $handler = new ContractHandler(
            $rpc,
            $this->mockLoggerInterface(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->mockLimitHistoryConfig(),
            $this->mockWrappedCryptoTokenManager(),
            $this->mockTokenNameConverter(),
            $this->mockRouter()
        );


        $crypto = $this->mockCrypto('WEB');
        $token = $this->mockToken(true, '0x123', 'deployed');

        $deploy = (new TokenDeploy())
            ->setToken($token)
            ->setCrypto($crypto);

        $token->method('getDeployByCrypto')->willReturn($deploy);

        $this->expectException(\Throwable::class);

        $handler->withdraw(
            $this->mockUser(1),
            new Money('1', new Currency(Symbols::WEB)),
            '0x123',
            $token,
            $crypto,
            new Money('1', new Currency(Symbols::TOK))
        );
    }

    public function testGetTransactions(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->once())->method('send')->with(
                'get_transactions',
                [
                    'userId' => 1,
                    'asset' => AssetType::TOKEN,
                    "offset" => 0,
                    "limit" => 50,
                    "fromTimestamp" => 1,
                ]
            )->willReturn($this->mockResponse(false, [
                [
                    'hash' => 'hash',
                    'from' => '0x123',
                    'to' => '0x456',
                    'amount' => '1000000000000',
                    'timestamp' => 1564566334,
                    'token' => 'foo',
                    'status' => 'paid',
                    'type' => 'withdraw',
                    'crypto' => 'WEB',
                    'tokenFee' => '1000000000000',
                    'tokenFeeCurrency' => 'TOK',
                ],
                [
                    'hash' => 'hash',
                    'from' => '0x123',
                    'to' => '0x456',
                    'amount' => '1000000000000',
                    'timestamp' => 1564566334,
                    'token' => 'bar',
                    'status' => 'paid',
                    'type' => 'deposit',
                    'crypto' => 'WEB',
                    'tokenFee' => '1000000000000',
                    'tokenFeeCurrency' => 'TOK',
                ],
            ]));

        $cryptoManager = $this->createMock(CryptoManagerInterface::class);
        $cryptoManager->method('findBySymbol')->willReturn($this->mockTokenCrypto());
        $cryptoManager->method('findAllIndexed')->willReturn([
            'WEB' => $this->mockCrypto(),
        ]);

        $handler = new ContractHandler(
            $rpc,
            $this->mockLoggerInterface(),
            $this->mockMoneyWrapper(),
            $cryptoManager,
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->mockLimitHistoryConfig(),
            $this->mockWrappedCryptoTokenManager(),
            $this->mockTokenNameConverter(),
            $this->mockRouter()
        );

        $result = $handler->getTransactions(
            $this->mockUser(1),
            0,
            50
        );

        $this->assertCount(2, $result);
        $this->assertEquals([
            $result[0]->getHash(),
            $result[0]->getFromAddress(),
            $result[0]->getToAddress(),
            $result[0]->getAmount()->getAmount(),
            $result[0]->getFee()->getAmount(),
            $result[0]->getStatus()->getStatusCode(),
            $result[0]->getType()->getTypeCode(),
        ], [
            'hash',
            '0x123',
            '0x456',
            '1000000000000',
            '1000000000000',
            'paid',
            'withdraw',
        ]);
    }

    public function testGetTransactionsWithResponseError(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->once())->method('send')->with(
                'get_transactions',
                [
                    'userId' => 1,
                    'asset' => AssetType::TOKEN,
                    "offset" => 0,
                    "limit" => 50,
                    "fromTimestamp" => 1,
                ]
            )
            ->willReturn($this->mockResponse(true, []));

        $handler = new ContractHandler(
            $rpc,
            $this->mockLoggerInterface(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->mockLimitHistoryConfig(),
            $this->mockWrappedCryptoTokenManager(),
            $this->mockTokenNameConverter(),
            $this->mockRouter()
        );

        $this->expectException(FetchException::class);

        $handler->getTransactions(
            $this->mockUser(1),
            0,
            50
        );
    }

    public function testGetDepositInfo(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->once())->method('send')->with(
                'get_deposit_info',
                [
                    'tokenName' => 'foo',
                    'crypto' => 'WEB',
                ]
            )->willReturn($this->mockResponse(false, ['fee' => '0', 'minDeposit' => '1000000000000']));

        $handler = new ContractHandler(
            $rpc,
            $this->mockLoggerInterface(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->mockLimitHistoryConfig(),
            $this->mockWrappedCryptoTokenManager(),
            $this->mockTokenNameConverter(),
            $this->mockRouter()
        );

        $expectedMinDeposit = new Money('1000000000000', new Currency(Symbols::TOK));
        $result = $handler->getDepositInfo($this->mockToken(true), $this->mockCrypto('WEB'));

        $this->assertEquals($expectedMinDeposit, $result->getMinDeposit());
    }

    public function getDepositCredentials(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->once())->method('send')->with(
                'get_deposit_credential',
                [
                    'userId' => 1,
                ]
            )->willReturn($this->mockResponse(false, ['address' => '0x123']));

        $handler = new ContractHandler(
            $rpc,
            $this->mockLoggerInterface(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->mockLimitHistoryConfig(),
            $this->mockWrappedCryptoTokenManager(),
            $this->mockTokenNameConverter(),
            $this->mockRouter()
        );

        $result = $handler->getDepositCredentials(
            $this->mockUser(1)
        );

        $this->assertEquals($result, '0x123');
    }

    private function mockUser(int $id): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);

        return $user;
    }

    /** @return MoneyWrapperInterface|MockObject */
    private function mockMoneyWrapper(): MockObject
    {
        $currencies = $this->createMock(Currencies::class);
        $currencies->method('subunitFor')->willReturn(4);
        $moneyWrapper = $this->createMock(MoneyWrapperInterface::class);
        $moneyWrapper->method('getRepository')->willReturn($currencies);
        $moneyWrapper->method('parse')->willReturnCallback(function () {
            return new Money('1000000000000', new Currency(Symbols::TOK));
        });
        $moneyWrapper->method('format')->willReturnCallback(function ($money) {
            return $money->getAmount();
        });

        return $moneyWrapper;
    }

    /** @return Token|MockObject */
    private function mockToken(
        bool $hasReleasePeriod,
        string $address = '0x123',
        string $status = 'not-deployed',
        string $mintDestination = '0x789'
    ): Token {
        $deploy = $this->createMock(TokenDeploy::class);
        $deploy->method('getAddress')->willReturn($address);

        $token = $this->createMock(Token::class);
        $token->method('getMainDeploy')->willReturn($deploy);
        $token->method('getName')->willReturn('foo');
        $token->method('getSymbol')->willReturn('foo');
        $token->method('getMoneySymbol')->willReturn(Symbols::TOK);
        $token->method('getDeploymentStatus')->willReturn($status);
        $token->method('getMintDestination')->willReturn($mintDestination);
        $token->method('getCryptoSymbol')->willReturn('WEB');

        if (!$hasReleasePeriod) {
            $token->method('getLockIn')->willReturn(null);
        }

        $lockIn = $this->createMock(LockIn::class);
        $lockIn->method('getReleasePeriod')->willReturn(10);
        $lockIn->method('getReleasedAmount')->willReturn(new Money('100000', new Currency(Symbols::TOK)));
        $token->method('getLockIn')->willReturn($lockIn);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $profile = $this->createMock(Profile::class);
        $profile->method('getUser')->willReturn($user);
        $token->method('getProfile')->willReturn($profile);
        $token->method('setDecimals')->willReturn($token);

        return $token;
    }

    private function mockCrypto(string $symbol = 'WEB'): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getFee')->willReturn(new Money('1000000000000', new Currency(Symbols::TOK)));
        $crypto->method('getSymbol')->willReturn($symbol);
        $crypto->method('getMoneySymbol')->willReturn($symbol);

        return $crypto;
    }

    private function mockTokenCrypto(): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getFee')->willReturn(new Money('1000000000000', new Currency(Symbols::USDC)));
        $crypto->method('getSymbol')->willReturn('USDC');
        $crypto->method('getSubunit')->willReturn(6);

        return $crypto;
    }

    /** @return LoggerInterface|MockObject */
    private function mockLoggerInterface(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }


    /**
     * @param bool $hasError
     * @param mixed $result
     * @return JsonRpcResponse
     */
    private function mockResponse(bool $hasError, $result = []): JsonRpcResponse
    {
        $response = $this->createMock(JsonRpcResponse::class);
        $response->method('hasError')->willReturn($hasError);
        $response->method('getResult')->willReturn($result);

        return $response;
    }

    /** @return MockObject|JsonRpcInterface */
    private function mockRpc(): JsonRpcInterface
    {
        return $this->createMock(JsonRpcInterface::class);
    }

    private function mockCryptoManager(): CryptoManagerInterface
    {
        $manager = $this->createMock(CryptoManagerInterface::class);
        $manager->method('findBySymbol')->willReturn($this->mockCrypto());
        $manager->method('findAllIndexed')->willReturn([
            'WEB' => $this->mockCrypto(),
        ]);

        return $manager;
    }

    private function mockTokenManager(): TokenManagerInterface
    {
        $tm = $this->createMock(TokenManagerInterface::class);
        $token = $this->createMock(Token::class);
        $token->method('getSymbol')->willReturn('USDC');
        $token->method('getUsers')->willReturn([$this->createMock(User::class)]);
        $tm->method('findByName')->willReturn($token);

        return $tm;
    }

    private function mockTokenConfig(): TokenConfig
    {
        return $this->createMock(TokenConfig::class);
    }

    private function mockLimitHistoryConfig(): LimitHistoryConfig
    {
        $config = $this->createMock(LimitHistoryConfig::class);
        $date = (new \DateTimeImmutable())->setTimestamp(1);

        $config
            ->method('getFromDate')
            ->willReturn($date);

        return $config;
    }

    private function mockWrappedCryptoTokenManager(): WrappedCryptoTokenManagerInterface
    {
        return $this->createMock(WrappedCryptoTokenManagerInterface::class);
    }

    private function mockRouter(): RouterInterface
    {
        return $this->createMock(RouterInterface::class);
    }

    private function mockTokenNameConverter(): TokenNameConverterInterface
    {
        return $this->createMock(TokenNameConverterInterface::class);
    }
}
