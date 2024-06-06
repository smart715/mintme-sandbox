<?php declare(strict_types = 1);

namespace App\Tests\Wallet;

use App\Config\HideFeaturesConfig;
use App\Entity\Crypto;
use App\Entity\PendingTokenWithdraw;
use App\Entity\PendingWithdraw;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Entity\WrappedCryptoToken;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Factory\UpdateBalanceView;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Config\TokenConfig;
use App\Logger\WithdrawLogger;
use App\Manager\CryptoManager;
use App\Manager\InternalTransactionManagerInterface;
use App\Manager\PendingManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\WrappedCryptoTokenManagerInterface;
use App\Repository\UserRepository;
use App\SmartContract\ContractHandlerInterface;
use App\Tests\Mocks\MockMoneyWrapper;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Symbols;
use App\Utils\ValidatorFactoryInterface;
use App\Wallet\Deposit\DepositGatewayCommunicator;
use App\Wallet\Exception\NotEnoughAmountException;
use App\Wallet\Exception\NotEnoughUserAmountException;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use App\Wallet\Model\Transaction;
use App\Wallet\Model\Type;
use App\Wallet\Model\WithdrawInfo;
use App\Wallet\Wallet;
use App\Wallet\Withdraw\WithdrawGatewayInterface;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WalletTest extends TestCase
{

    use MockMoneyWrapper;

    public function testGetWithdrawDepositHistory(): void
    {
        $depositTransactions = [
            $this->mockTransaction(1, 'WEB', 'deposit'),
            $this->mockTransaction(4, 'BTC', 'deposit'),
        ];
        $withdrawTransactions = [
            $this->mockTransaction(3, 'ETH', 'withdraw'),
            $this->mockTransaction(6, 'XMR', 'withdraw'),
        ];
        $withdrawTokenPendingTransaction = [
            $this->mockTransaction(7, 'goo', 'withdraw'),
        ];
        $withdrawCryptoPendingTransaction = [
            $this->mockTransaction(8, 'doo', 'withdraw'),
        ];
        $tokenTransactions = [
            $this->mockTransaction(5, 'foo', 'withdraw'),
            $this->mockTransaction(2, 'bar', 'deposit'),
        ];
        $internalTransactions = [
            $this->mockTransaction(9, 'moo', 'withdraw'),
            $this->mockTransaction(10, 'loo', 'deposit'),
        ];

        $expectedHistory = [
            $internalTransactions[1],
            $internalTransactions[0],
            $withdrawCryptoPendingTransaction[0],
            $withdrawTokenPendingTransaction[0],
            $withdrawTransactions[1],
            $tokenTransactions[0],
            $depositTransactions[1],
            $withdrawTransactions[0],
            $tokenTransactions[1],
            $depositTransactions[0],
        ];

        $pendingManager = $this->createMock(PendingManagerInterface::class);
        $pendingManager
            ->method('getPendingTokenWithdraw')
            ->willReturn($withdrawTokenPendingTransaction);

        $pendingManager
            ->method('getPendingCryptoWithdraw')
            ->willReturn($withdrawCryptoPendingTransaction);

        $wallet = new Wallet(
            $this->mockWithdrawGatewayInterface($withdrawTransactions),
            $this->mockBalanceHandler(),
            $this->mockDepositCommunicator($depositTransactions),
            $pendingManager,
            $this->createMock(EntityManagerInterface::class),
            $this->mockContractHandler($tokenTransactions),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->createMock(RebrandingConverterInterface::class),
            $this->createMock(ValidatorFactoryInterface::class),
            $this->createMock(UserRepository::class),
            $this->mockMoneyWrapper(),
            $this->createMock(CryptoManager::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->mockInternalTransactionManager($internalTransactions),
            $this->mockHideFeaturesConfig(),
            $this->createMock(WithdrawLogger::class),
            $this->mockWrappedCryptoTokenManager()
        );

        $expectedHistory = array_map(static fn(Transaction $transaction) => [
            $transaction->getDate()->getTimestamp(),
            $transaction->getTradable()->getSymbol(),
            $transaction->getType()->getTypeCode(),
        ], $expectedHistory);

        $history = $wallet->getWithdrawDepositHistory($this->mockUser(), 0, 10);
        $history = array_map(static fn(Transaction $transaction) => [
            $transaction->getDate()->getTimestamp(),
            $transaction->getTradable()->getSymbol(),
            $transaction->getType()->getTypeCode(),
        ], $history);

        $this->assertEquals($expectedHistory, $history);
    }

    public function testWithdrawInitCrypto(): void
    {
        $wallet = new Wallet(
            $this->mockWithdrawGatewayInterface([], '3000000000000000000', Symbols::WEB),
            $this->mockBalanceHandler($this->once(), '3000000000000000000', '0', "WEB"),
            $this->mockDepositCommunicator([]),
            $this->mockPendingManager($this->once()),
            $this->createMock(EntityManagerInterface::class),
            $this->mockContractHandler([]),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->createMock(RebrandingConverterInterface::class),
            $this->createMock(ValidatorFactoryInterface::class),
            $this->createMock(UserRepository::class),
            $this->mockMoneyWrapper(),
            $this->createMock(CryptoManager::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(InternalTransactionManagerInterface::class),
            $this->mockHideFeaturesConfig(),
            $this->createMock(WithdrawLogger::class),
            $this->mockWrappedCryptoTokenManager()
        );

        $wallet->withdrawInit(
            $this->mockUser(),
            $this->mockAddress('0xfB6916095ca1df60bB79Ce92cE3Ea74c37c5d359'),
            $this->mockAmount(new Money('1000000000000000000', new Currency(Symbols::WEB))),
            $this->mockCrypto(Symbols::WEB),
            $this->mockCrypto(Symbols::WEB)
        );
    }

    public function testWithdrawInitCryptoWrapped(): void
    {
        $wallet = new Wallet(
            $this->mockWithdrawGatewayInterface([], '3000000000000000000', Symbols::WEB),
            $this->mockBalanceHandler($this->once(), '3000000000000000000', '0', "WEB"),
            $this->mockDepositCommunicator([]),
            $this->mockPendingManager($this->once()),
            $this->createMock(EntityManagerInterface::class),
            $this->mockContractHandler([], Symbols::ETH),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->createMock(RebrandingConverterInterface::class),
            $this->createMock(ValidatorFactoryInterface::class),
            $this->createMock(UserRepository::class),
            $this->mockMoneyWrapper(),
            $this->createMock(CryptoManager::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(InternalTransactionManagerInterface::class),
            $this->mockHideFeaturesConfig(),
            $this->createMock(WithdrawLogger::class),
            $this->mockWrappedCryptoTokenManager()
        );

        $wallet->withdrawInit(
            $this->mockUser(),
            $this->mockAddress('0xfB6916095ca1df60bB79Ce92cE3Ea74c37c5d359'),
            $this->mockAmount(new Money('1000000000000000000', new Currency(Symbols::WEB))),
            $this->mockCrypto(Symbols::WEB, Symbols::ETH),
            $this->mockCrypto(Symbols::ETH)
        );
    }

    public function testWithdrawInitCryptoWithLowBalance(): void
    {
        $wallet = new Wallet(
            $this->mockWithdrawGatewayInterface([], '1000000000000000000', Symbols::WEB),
            $this->mockBalanceHandler($this->never(), '1000000000000000000'),
            $this->mockDepositCommunicator([]),
            $this->mockPendingManager($this->never()),
            $this->createMock(EntityManagerInterface::class),
            $this->mockContractHandler([]),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->createMock(RebrandingConverterInterface::class),
            $this->createMock(ValidatorFactoryInterface::class),
            $this->createMock(UserRepository::class),
            $this->mockMoneyWrapper(),
            $this->createMock(CryptoManager::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(InternalTransactionManagerInterface::class),
            $this->mockHideFeaturesConfig(),
            $this->createMock(WithdrawLogger::class),
            $this->mockWrappedCryptoTokenManager()
        );

        $this->expectException(NotEnoughUserAmountException::class);

        $wallet->withdrawInit(
            $this->mockUser(),
            $this->mockAddress('0xfB6916095ca1df60bB79Ce92cE3Ea74c37c5d359'),
            $this->mockAmount(new Money('1000000000000000000', new Currency(Symbols::WEB))),
            $this->mockCrypto(Symbols::WEB),
            $this->mockCrypto(Symbols::WEB)
        );
    }

    public function testWithdrawInitToken(): void
    {
        $wallet = new Wallet(
            $this->mockWithdrawGatewayInterface([], '3000000000000', Symbols::TOK),
            $this->mockBalanceHandler(
                $this->exactly(2), // token balance, crypto fee balance
                '3000000000000000000',
                '3000000000000'
            ),
            $this->mockDepositCommunicator([]),
            $this->mockPendingManager($this->once()),
            $this->createMock(EntityManagerInterface::class),
            $this->mockContractHandler([]),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->createMock(RebrandingConverterInterface::class),
            $this->createMock(ValidatorFactoryInterface::class),
            $this->createMock(UserRepository::class),
            $this->mockMoneyWrapper(),
            $this->createMock(CryptoManager::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(InternalTransactionManagerInterface::class),
            $this->mockHideFeaturesConfig(),
            $this->createMock(WithdrawLogger::class),
            $this->mockWrappedCryptoTokenManager()
        );

        $wallet->withdrawInit(
            $this->mockUser(),
            $this->mockAddress('0xfB6916095ca1df60bB79Ce92cE3Ea74c37c5d359'),
            $this->mockAmount(new Money('1000000000000', new Currency(Symbols::TOK))),
            $this->mockToken(),
            $this->mockCrypto(Symbols::WEB)
        );
    }

    public function testWithdrawInitTokenWithOwnFee(): void
    {
        $wallet = new Wallet(
            $this->mockWithdrawGatewayInterface([], '3000000000000', Symbols::TOK),
            $this->mockBalanceHandler(
                $this->exactly(1),
                '3000000000000000000',
                '3000000000000'
            ),
            $this->mockDepositCommunicator([]),
            $this->mockPendingManager($this->once()),
            $this->createMock(EntityManagerInterface::class),
            $this->mockContractHandler([]),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->createMock(RebrandingConverterInterface::class),
            $this->createMock(ValidatorFactoryInterface::class),
            $this->createMock(UserRepository::class),
            $this->mockMoneyWrapper(),
            $this->createMock(CryptoManager::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(InternalTransactionManagerInterface::class),
            $this->mockHideFeaturesConfig(),
            $this->createMock(WithdrawLogger::class),
            $this->mockWrappedCryptoTokenManager()
        );

        $wallet->withdrawInit(
            $this->mockUser(),
            $this->mockAddress('0xfB6916095ca1df60bB79Ce92cE3Ea74c37c5d359'),
            $this->mockAmount(new Money('1000000000000', new Currency(Symbols::TOK))),
            $this->mockToken(Symbols::TOK),
            $this->mockCrypto(Symbols::WEB)
        );
    }

    public function testWithdrawInitTokenWithLowBalance(): void
    {
        $wallet = new Wallet(
            $this->mockWithdrawGatewayInterface([], '100000000000', Symbols::TOK),
            $this->mockBalanceHandler(
                $this->never(),
                '3000000000000000000',
                '100000000000'
            ),
            $this->mockDepositCommunicator([]),
            $this->mockPendingManager($this->never()),
            $this->createMock(EntityManagerInterface::class),
            $this->mockContractHandler([]),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->createMock(RebrandingConverterInterface::class),
            $this->createMock(ValidatorFactoryInterface::class),
            $this->createMock(UserRepository::class),
            $this->mockMoneyWrapper(),
            $this->createMock(CryptoManager::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(InternalTransactionManagerInterface::class),
            $this->mockHideFeaturesConfig(),
            $this->createMock(WithdrawLogger::class),
            $this->mockWrappedCryptoTokenManager()
        );

        $this->expectException(NotEnoughUserAmountException::class);

        $wallet->withdrawInit(
            $this->mockUser(),
            $this->mockAddress('0xfB6916095ca1df60bB79Ce92cE3Ea74c37c5d359'),
            $this->mockAmount(new Money('1000000000000', new Currency(Symbols::TOK))),
            $this->mockToken(),
            $this->mockCrypto(Symbols::WEB)
        );
    }

    public function testWithdrawInitTokenWithNoEnoughFee(): void
    {
        $wallet = new Wallet(
            $this->mockWithdrawGatewayInterface([], '3000000000000', Symbols::TOK),
            $this->mockBalanceHandler(
                null,
                '1000000000000000',
                '3000000000000'
            ),
            $this->mockDepositCommunicator([]),
            $this->mockPendingManager($this->never()),
            $this->createMock(EntityManagerInterface::class),
            $this->mockContractHandler([]),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->createMock(RebrandingConverterInterface::class),
            $this->createMock(ValidatorFactoryInterface::class),
            $this->createMock(UserRepository::class),
            $this->mockMoneyWrapper(),
            $this->createMock(CryptoManager::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(InternalTransactionManagerInterface::class),
            $this->mockHideFeaturesConfig(),
            $this->createMock(WithdrawLogger::class),
            $this->mockWrappedCryptoTokenManager()
        );

        $this->expectException(NotEnoughAmountException::class);

        $wallet->withdrawInit(
            $this->mockUser(),
            $this->mockAddress('0xfB6916095ca1df60bB79Ce92cE3Ea74c37c5d359'),
            $this->mockAmount(new Money('1000000000000', new Currency(Symbols::TOK))),
            $this->mockToken(),
            $this->mockCrypto(Symbols::WEB)
        );
    }

    public function testWithdrawCommitCrypto(): void
    {
        $wallet = new Wallet(
            $this->mockWithdrawGatewayInterface([], '3000000000000000000', Symbols::WEB, $this->once()),
            $this->mockBalanceHandler(),
            $this->mockDepositCommunicator([]),
            $this->mockPendingManager(),
            $this->createMock(EntityManagerInterface::class),
            $this->mockContractHandler([]),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->createMock(RebrandingConverterInterface::class),
            $this->createMock(ValidatorFactoryInterface::class),
            $this->createMock(UserRepository::class),
            $this->mockMoneyWrapper(),
            $this->createMock(CryptoManager::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(InternalTransactionManagerInterface::class),
            $this->mockHideFeaturesConfig(),
            $this->createMock(WithdrawLogger::class),
            $this->mockWrappedCryptoTokenManager()
        );

        $wallet->withdrawCommit($this->mockPendingWithdraw('1000000000000000000'));
    }

    public function testWithdrawCommitCryptoWithLowBalance(): void
    {
        $wallet = new Wallet(
            $this->mockWithdrawGatewayInterface([], '10000000000000000', Symbols::WEB, $this->once()),
            $this->mockBalanceHandler(),
            $this->mockDepositCommunicator([]),
            $this->mockPendingManager(),
            $this->createMock(EntityManagerInterface::class),
            $this->mockContractHandler([]),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->createMock(RebrandingConverterInterface::class),
            $this->createMock(ValidatorFactoryInterface::class),
            $this->createMock(UserRepository::class),
            $this->mockMoneyWrapper(),
            $this->createMock(CryptoManager::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(InternalTransactionManagerInterface::class),
            $this->mockHideFeaturesConfig(),
            $this->createMock(WithdrawLogger::class),
            $this->mockWrappedCryptoTokenManager()
        );

        $wallet->withdrawCommit($this->mockPendingWithdraw('1000000000000000000'));
    }

    public function testWithdrawCommitToken(): void
    {
        $wallet = new Wallet(
            $this->mockWithdrawGatewayInterface([], '1000000000000', Symbols::TOK),
            $this->mockBalanceHandler(),
            $this->mockDepositCommunicator([]),
            $this->mockPendingManager(),
            $this->createMock(EntityManagerInterface::class),
            $this->mockContractHandler([], Symbols::WEB, $this->once()),
            $this->mockTokenManager(),
            $this->mockTokenConfig(),
            $this->createMock(RebrandingConverterInterface::class),
            $this->createMock(ValidatorFactoryInterface::class),
            $this->createMock(UserRepository::class),
            $this->mockMoneyWrapper(),
            $this->createMock(CryptoManager::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(InternalTransactionManagerInterface::class),
            $this->mockHideFeaturesConfig(),
            $this->createMock(WithdrawLogger::class),
            $this->mockWrappedCryptoTokenManager()
        );

        $wallet->withdrawCommit($this->mockPendingTokenWithdraw('1000000000000'));
    }

    private function mockPendingWithdraw(string $amount): PendingWithdraw
    {
        $amountMock = $this->createMock(Amount::class);
        $amountMock->method('getAmount')->willReturn(
            new Money($amount, new Currency(Symbols::WEB))
        );

        $pending = $this->createMock(PendingWithdraw::class);
        $pending->method('getCrypto')->willReturn($this->mockCrypto(Symbols::WEB));
        $pending->method('getCryptoNetwork')->willReturn($this->mockCrypto(Symbols::WEB));
        $pending->method('getAmount')->willReturn($amountMock);
        $pending->method('getFee')->willReturn(new Money('0', new Currency(Symbols::WEB)));

        return $pending;
    }

    private function mockPendingTokenWithdraw(string $amount): PendingTokenWithdraw
    {
        $amountMock = $this->createMock(Amount::class);
        $amountMock->method('getAmount')->willReturn(
            new Money($amount, new Currency(Symbols::TOK))
        );

        $pending = $this->createMock(PendingTokenWithdraw::class);
        $pending->method('getToken')->willReturn($this->mockToken());
        $pending->method('getAmount')->willReturn($amountMock);
        $pending->method('getFee')->willReturn(new Money('0', new Currency(Symbols::WEB)));

        return $pending;
    }

    private function mockPendingManager(?InvokedCount $inv = null): PendingManagerInterface
    {
        $manager = $this->createMock(PendingManagerInterface::class);
        $manager->expects($inv ?? $this->never())->method('create');

        return $manager;
    }

    private function mockAmount(Money $money): Amount
    {
        $amount = $this->createMock(Amount::class);
        $amount->method('getAmount')->willReturn($money);

        return $amount;
    }

    private function mockDepositCommunicator(array $transactions): DepositGatewayCommunicator
    {
        $depositCommunicatorMock = $this->createMock(DepositGatewayCommunicator::class);
        $depositCommunicatorMock
            ->method('getTransactions')
            ->willReturn($transactions);

        return $depositCommunicatorMock;
    }

    private function mockContractHandler(
        array $transactions,
        string $symbol = 'WEB',
        ?InvokedCount $withdrawInv = null
    ): ContractHandlerInterface {
        $contractHandler = $this->createMock(ContractHandlerInterface::class);
        $contractHandler->expects($withdrawInv ?? $this->never())->method('withdraw');
        $contractHandler
            ->method('getTransactions')
            ->willReturn($transactions);

        $contractHandler
            ->method('getWithdrawInfo')
            ->willReturn($this->mockWithdrawInfo('0', $symbol, false));

        return $contractHandler;
    }

    private function mockWithdrawInfo(
        string $minFee,
        string $symbol,
        bool $isPaused
    ): WithdrawInfo {
        $withdrawInfo = $this->createMock(WithdrawInfo::class);
        $withdrawInfo
            ->method('getMinFee')
            ->willReturn(new Money($minFee, new Currency($symbol)));
        $withdrawInfo
            ->method('isPaused')
            ->willReturn($isPaused);

        return $withdrawInfo;
    }

    private function mockWithdrawGatewayInterface(
        array $history,
        string $available = '0',
        string $symbol = 'WEB',
        ?InvokedCount $withdrawInv = null
    ): WithdrawGatewayInterface {
        $withdrawGatewayMock = $this->createMock(WithdrawGatewayInterface::class);
        $withdrawGatewayMock
            ->method('getHistory')
            ->willReturn($history);
        $withdrawGatewayMock
            ->method('getBalance')
            ->willReturn(new Money($available, new Currency($symbol)));
        $withdrawGatewayMock
            ->method('isContractAddress')
            ->willReturn(false);

        $withdrawGatewayMock
            ->expects($withdrawInv ?? $this->never())
            ->method('withdraw');

        return $withdrawGatewayMock;
    }

    private function mockBalanceHandler(
        ?InvokedCount $withdrawInv = null,
        string $available = '0',
        string $availableToken = '0',
        string $changeSymbol = "TOK"
    ): BalanceHandlerInterface {
        $handler = $this->createMock(BalanceHandlerInterface::class);

        $handler->method('balance')
            ->will($this->returnCallback(
                function (User $user, TradableInterface $tradable) use ($available, $availableToken) {
                    $balanceResult = $this->createMock(BalanceResult::class);

                    $balanceResult
                        ->method('getAvailable')
                        ->willReturn(new Money(
                            Symbols::WEB === $tradable->getSymbol() ? $available : $availableToken,
                            new Currency($tradable->getMoneySymbol())
                        ));

                    return $balanceResult;
                }
            ));

        $ubv = $this->createMock(UpdateBalanceView::class);
        $ubv->method('getChange')->willReturn(new Money('10000000000000000', new Currency($changeSymbol)));
        $handler->expects($withdrawInv ?? $this->never())
            ->method('withdraw')
            ->willReturn($ubv);

        return $handler;
    }

    private function mockTransaction(int $timestamp, string $crypto, string $type): Transaction
    {
        $transactionMock = $this->createMock(Transaction::class);
        $transactionMock
            ->method('getDate')
            ->willReturn($this->mockDateTime($timestamp));
        $transactionMock
            ->method('getTradable')
            ->willReturn($this->mockCrypto($crypto));
        $transactionMock
            ->method('getType')
            ->willReturn($this->mockType($type));

        return $transactionMock;
    }

    private function mockType(string $type): Type
    {
        $typeMock = $this->createMock(Type::class);
        $typeMock
            ->method('getTypeCode')
            ->willReturn($type);

        return $typeMock;
    }

    private function mockCrypto(string $symbol, ?string $wrappedTo = null): Crypto
    {
        $cryptoMock = $this->createMock(Crypto::class);
        $cryptoMock
            ->method('getFee')
            ->willReturn(
                new Money('3000000000000000', new Currency($symbol))
            );
        $cryptoMock
            ->method('getSymbol')
            ->willReturn($symbol);

        $cryptoMock
            ->method('getMoneySymbol')
            ->willReturn($symbol);

        if ($wrappedTo) {
            $wrappedTo = $this->mockCrypto($wrappedTo);
            $wrappedToken = $this->mockWrappedCryptoToken($cryptoMock, $wrappedTo);

            $cryptoMock
                ->method('getWrappedCryptoTokens')
                ->willReturn([$wrappedToken]);

            $cryptoMock
                ->method('getWrappedTokenByCrypto')
                ->with($wrappedTo)
                ->willReturn($wrappedToken);

            $cryptoMock
                ->method('canBeWithdrawnTo')
                ->with($wrappedTo)
                ->willReturn(true);
        }

        return $cryptoMock;
    }

    private function mockWrappedCryptoToken(Crypto $whom, Crypto $to): WrappedCryptoToken
    {
        $wrapped = $this->createMock(WrappedCryptoToken::class);
        $wrapped->method('getCrypto')->willReturn($whom);
        $wrapped->method('getCryptoDeploy')->willReturn($to);
        $wrapped->method('getFee')->willReturn(new Money('0', new Currency($to->getSymbol())));

        return $wrapped;
    }

    private function mockToken(?string $symbol = null): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getCryptoSymbol')->willReturn('WEB');
        $token->method('getFee')->willReturn(
            $symbol ?
                new Money('30000000', new Currency($symbol)) :
                null
        );

        $token
            ->method('getMoneySymbol')
            ->willReturn(Symbols::TOK);

        return $token;
    }

    private function mockDateTime(int $timestamp): DateTime
    {
        $dateMock = $this->createMock(DateTime::class);
        $dateMock
            ->method('getTimestamp')
            ->willReturn($timestamp);

        return $dateMock;
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    private function mockAddress(string $str): Address
    {
        $address = $this->createMock(Address::class);

        $address->method('getAddress')->willReturn($str);

        return $address;
    }

    private function mockTokenManager(): TokenManagerInterface
    {
        $tm = $this->createMock(TokenManagerInterface::class);
        $tm->method('getRealBalance')->willReturnCallback(
            function (Token $token, BalanceResult $balanceResult) {
                return $balanceResult;
            }
        );

        return $tm;
    }

    private function mockTokenConfig(): TokenConfig
    {
        return $this->createMock(TokenConfig::class);
    }

    private function mockHideFeaturesConfig(): HideFeaturesConfig
    {
        $config = $this->createMock(HideFeaturesConfig::class);
        $config
            ->method('isCryptoEnabled')
            ->willReturn(true);

        return $config;
    }

    private function mockInternalTransactionManager(array $transactions = []): InternalTransactionManagerInterface
    {
        $itm = $this->createMock(InternalTransactionManagerInterface::class);
        $itm->method('getLatest')->willReturn($transactions);

        return $itm;
    }

    private function mockWrappedCryptoTokenManager(): WrappedCryptoTokenManagerInterface
    {
        return $this->createMock(WrappedCryptoTokenManagerInterface::class);
    }
}
