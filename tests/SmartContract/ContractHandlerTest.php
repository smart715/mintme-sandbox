<?php declare(strict_types = 1);

namespace App\Tests\SmartContract;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Communications\JsonRpcResponse;
use App\Entity\Crypto;
use App\Entity\Profile;
use App\Entity\Token\LockIn;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\SmartContract\Config\Config;
use App\SmartContract\ContractHandler;
use App\Utils\Symbols;
use App\Wallet\Model\DepositInfo;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use App\Wallet\WalletInterface;
use Money\Currencies;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

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
                    'crypto' => '',
                ]
            )
            ->willReturn($this->mockResponse(false, []));

        $handler = new ContractHandler(
            $rpc,
            $this->mockLoggerInterface(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockParameterBagInterface()
        );

        $handler->deploy($this->mockToken(true));
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
            $this->mockParameterBagInterface()
        );

        $this->expectException(\Throwable::class);

        $handler->deploy($this->mockToken(false));
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
                    'crypto' => '',
                ]
            )
            ->willReturn($this->mockResponse(true, []));

        $handler = new ContractHandler(
            $rpc,
            $this->mockLoggerInterface(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockParameterBagInterface()
        );

        $this->expectException(\Throwable::class);

        $handler->deploy($this->mockToken(true));
    }

    public function testUpdateMintDestination(): void
    {
        $rpc = $this->mockRpc();
        $rpc
            ->expects($this->once())->method('send')->with(
                'update_mint_destination',
                [
                    'name' => 'foo',
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
            $this->mockParameterBagInterface()
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
            $this->mockParameterBagInterface()
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
            $this->mockParameterBagInterface()
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
                    'crypto' => '',
                    'tokenFee' => '1',
                ]
            );

        $handler = new ContractHandler(
            $rpc,
            $this->mockLoggerInterface(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockParameterBagInterface()
        );

        $handler->withdraw(
            $this->mockUser(1),
            new Money('1', new Currency(Symbols::WEB)),
            '0x123',
            $this->mockToken(true, '0x123', 'deployed'),
            new Money('1', new Currency(Symbols::WEB))
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
            $this->mockParameterBagInterface()
        );

        $this->expectException(\Throwable::class);

        $handler->withdraw(
            $this->mockUser(1),
            new Money('1', new Currency(Symbols::WEB)),
            '0x123',
            $this->mockToken(true, '0x123', 'not-deployed'),
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
                    'crypto' => '',
                    'tokenFee' => '1',
                ]
            )->willReturn($this->mockResponse(true));

        $handler = new ContractHandler(
            $rpc,
            $this->mockLoggerInterface(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockParameterBagInterface()
        );

        $this->expectException(\Throwable::class);

        $handler->withdraw(
            $this->mockUser(1),
            new Money('1', new Currency(Symbols::WEB)),
            '0x123',
            $this->mockToken(true, '0x123', 'deployed'),
            new Money('1', new Currency(Symbols::WEB))
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
                    "offset" => 0,
                    "limit" => 50,
                ]
            )->willReturn($this->mockResponse(false, [
                [
                    'hash' => 'hash',
                    'from' => '0x123',
                    'to' => '0x456',
                    'amount' => '2000000000000',
                    'timestamp' => 1564566334,
                    'token' => 'foo',
                    'status' => 'paid',
                    'type' => 'withdraw',
                    'crypto' => 'WEB',
                    'tokenFee' => '1000000000000',
                ],
                [
                    'hash' => 'hash',
                    'from' => '0x123',
                    'to' => '0x456',
                    'amount' => '2000000000000',
                    'timestamp' => 1564566334,
                    'token' => 'bar',
                    'status' => 'paid',
                    'type' => 'deposit',
                    'crypto' => 'WEB',
                    'tokenFee' => '1000000000000',
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
            $this->mockParameterBagInterface()
        );

        $result = $handler->getTransactions(
            $this->mockWallet(),
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
            '2000000000000',
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
                    "offset" => 0,
                    "limit" => 50,
                ]
            )
            ->willReturn($this->mockResponse(true, []));

        $handler = new ContractHandler(
            $rpc,
            $this->mockLoggerInterface(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockParameterBagInterface()
        );

        $this->expectException(FetchException::class);

        $handler->getTransactions(
            $this->mockWallet(),
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
                    'tokenName' => 'AWESOME',
                ]
            )->willReturn($this->mockResponse(false, ['fee' => '0', 'minDeposit' => '1000000000000']));

        $handler = new ContractHandler(
            $rpc,
            $this->mockLoggerInterface(),
            $this->mockMoneyWrapper(),
            $this->mockCryptoManager(),
            $this->mockTokenManager(),
            $this->mockParameterBagInterface()
        );

        $expectedMinDeposit = new Money('1000000000000', new Currency(Symbols::TOK));
        $result = $handler->getDepositInfo('AWESOME');

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
            $this->mockParameterBagInterface()
        );

        $result = $handler->getDepositCredentials(
            $this->mockUser(1)
        );

        $this->assertEquals($result, '0x123');
    }

    private function mockWallet(): WalletInterface
    {
        $wallet = $this->createMock(WalletInterface::class);
        $depositInfo = $this->createMock(DepositInfo::class);

        $depositInfo->method('getFee')->willReturn(new Money('1000000000000', new Currency(Symbols::TOK)));
        $wallet->method('getDepositInfo')->willReturn($depositInfo);

        return $wallet;
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
        $token = $this->createMock(Token::class);
        $token->method('getName')->willReturn('foo');
        $token->method('getAddress')->willReturn($address);
        $token->method('getDeploymentStatus')->willReturn($status);
        $token->method('getMintDestination')->willReturn($mintDestination);

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

        return $token;
    }

    private function mockCrypto(): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getFee')->willReturn(new Money('1000000000000', new Currency(Symbols::TOK)));

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

    /** @return MockObject|JsonRpcResponse */
    private function mockResponse(bool $hasError, array $result = []): JsonRpcResponse
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

    public function mockParameterBagInterface(): ParameterBagInterface
    {
        $pb = $this->createMock(ParameterBagInterface::class);
        $pb->method('get')
            ->with('token_withdraw_fee')
            ->willReturn(0.01);

        return $pb;
    }
}
