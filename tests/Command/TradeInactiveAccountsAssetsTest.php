<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\TradeInactiveAccountsAssets;
use App\Communications\CryptoRatesFetcherInterface;
use App\Entity\Crypto;
use App\Entity\InternalTransaction\CryptoInternalTransaction;
use App\Entity\User;
use App\Exchange\Balance\BalanceFetcherInterface;
use App\Exchange\Balance\Model\BalanceHistory;
use App\Exchange\Config\QuickTradeConfig;
use App\Exchange\ExchangerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Trade\TradeResult;
use App\Manager\CryptoManagerInterface;
use App\Manager\InactiveOrderManagerInterface;
use App\Manager\UserManagerInterface;
use App\Repository\CryptoInternalTransactionRepository;
use App\SmartContract\ContractHandler;
use App\SmartContract\ContractHandlerInterface;
use App\Tests\Mocks\MockMoneyWrapper;
use App\Utils\Symbols;
use Doctrine\ORM\AbstractQuery as Query;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class TradeInactiveAccountsAssetsTest extends KernelTestCase
{

    use MockMoneyWrapper;

    private Application $app;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->app = new Application($kernel);
    }

    public function testSuccessfulExecute(): void
    {
        $this->app->add(
            new TradeInactiveAccountsAssets(
                $this->mockCryptoManager(),
                $this->mockBalanceFetcher(1, 100),
                $this->mockMoneyWrapper(),
                $this->mockQuickTradeConfig(),
                $this->mockUserManager(),
                $this->mockExchanger(4, true),
                $this->mockMarketFactory(),
                $this->mockInactiveOrderManager(),
                $this->mockEntityManager(),
                $this->mockContractHandler(false),
                $this->mockCryptoInternalTransRep(false),
                $this->mockCryptoRatesFetcher(),
                $this->mockLogger(),
                '3 months',
                0.005,
                1,
                1
            )
        );

        $command = $this->app->find('app:trade-inactive-accounts-assets');

        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        self::assertStringContainsString('Successful orders: 1, failed orders: 0', $output);
        self::assertStringNotContainsString('Successful orders: 0, failed orders: 1', $output);
        self::assertStringContainsString('Done', $output);
    }

    public function testNoEligibleUsersSuccessfulDueToLowUserDidCryptoDeposit(): void
    {
        $this->app->add(
            new TradeInactiveAccountsAssets(
                $this->mockCryptoManager(),
                $this->mockBalanceFetcher(1, 100),
                $this->mockMoneyWrapper(),
                $this->mockQuickTradeConfig(),
                $this->mockUserManager(),
                $this->mockExchanger(0, true),
                $this->mockMarketFactory(),
                $this->mockInactiveOrderManager(),
                $this->mockEntityManager(),
                $this->mockContractHandler(),
                $this->mockCryptoInternalTransRep(),
                $this->mockCryptoRatesFetcher(),
                $this->mockLogger(),
                '3 months',
                0.005,
                1,
                1
            )
        );

        $command = $this->app->find('app:trade-inactive-accounts-assets');

        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        self::assertStringContainsString('Successful orders: 0, failed orders: 0', $output);
        self::assertStringNotContainsString('Successful orders: 0, failed orders: 1', $output);
        self::assertStringContainsString('Done', $output);
    }

    public function testNoEligibleUsersSuccessfulDueToLowBalance(): void
    {
        $this->app->add(
            new TradeInactiveAccountsAssets(
                $this->mockCryptoManager(),
                $this->mockBalanceFetcher(1, 0),
                $this->mockMoneyWrapper(),
                $this->mockQuickTradeConfig(),
                $this->mockUserManager(),
                $this->mockExchanger(),
                $this->mockMarketFactory(),
                $this->mockInactiveOrderManager(),
                $this->mockEntityManager(),
                $this->mockContractHandler(),
                $this->mockCryptoInternalTransRep(),
                $this->mockCryptoRatesFetcher(),
                $this->mockLogger(),
                '3 months',
                0.005,
                1,
                1
            )
        );

        $command = $this->app->find('app:trade-inactive-accounts-assets');

        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        self::assertStringContainsString('Successful orders: 0, failed orders: 0', $output);
    }

    public function testNoEligibleUsersDueToNoInactiveUsers(): void
    {
        $this->app->add(
            new TradeInactiveAccountsAssets(
                $this->mockCryptoManager(),
                $this->mockBalanceFetcher(1, 100, $this->mockBalanceHistory([['detail' => ['i' => 1]]])),
                $this->mockMoneyWrapper(),
                $this->mockQuickTradeConfig(),
                $this->mockUserManager(),
                $this->mockExchanger(),
                $this->mockMarketFactory(),
                $this->mockInactiveOrderManager(),
                $this->mockEntityManager(),
                $this->mockContractHandler(),
                $this->mockCryptoInternalTransRep(),
                $this->mockCryptoRatesFetcher(),
                $this->mockLogger(),
                '3 months',
                0.005,
                1,
                1
            )
        );

        $command = $this->app->find('app:trade-inactive-accounts-assets');

        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        self::assertStringContainsString('Successful orders: 0, failed orders: 0', $output);
    }

    public function testIncorrectCrypto(): void
    {
        $cryptoManager = $this->mockCryptoManager();
        $cryptoManager->method('findAll')->willReturn([$this->mockCrypto('MATIC')]);

        $this->app->add(
            new TradeInactiveAccountsAssets(
                $cryptoManager,
                $this->mockBalanceFetcher(1, 100, $this->mockBalanceHistory([['detail' => ['i' => 1]]])),
                $this->mockMoneyWrapper(),
                $this->mockQuickTradeConfig(),
                $this->mockUserManager(),
                $this->mockExchanger(),
                $this->mockMarketFactory(),
                $this->mockInactiveOrderManager(),
                $this->mockEntityManager(),
                $this->mockContractHandler(),
                $this->mockCryptoInternalTransRep(),
                $this->mockCryptoRatesFetcher(),
                $this->mockLogger("[trade-inactive-accounts-assets] Market for MATIC not found"),
                '3 months',
                0.005,
                1,
                1
            )
        );

        $command = $this->app->find('app:trade-inactive-accounts-assets');

        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        self::assertStringContainsString('Successful orders: 0, failed orders: 0', $output);
    }

    public function testUserDoesntExistFailure(): void
    {
        $this->app->add(
            new TradeInactiveAccountsAssets(
                $this->mockCryptoManager(),
                $this->mockBalanceFetcher(1, 100),
                $this->mockMoneyWrapper(),
                $this->mockQuickTradeConfig(),
                $this->mockUserManager(false),
                $this->mockExchanger(),
                $this->mockMarketFactory(),
                $this->mockInactiveOrderManager(),
                $this->mockEntityManager(),
                $this->createMock(ContractHandler::class),
                $this->createMock(CryptoInternalTransactionRepository::class),
                $this->mockCryptoRatesFetcher(),
                $this->mockLogger("[trade-inactive-accounts-assets] User id 1 not found"),
                '3 months',
                0.005,
                1,
                1
            )
        );

        $command = $this->app->find('app:trade-inactive-accounts-assets');

        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $output = $commandTester->getDisplay();

        self::assertStringContainsString('Successful orders: 0, failed orders: 0', $output);
    }

    /** @return MockObject|CryptoManagerInterface */
    private function mockCryptoManager(): CryptoManagerInterface
    {
        $cryptoManager = $this->createMock(CryptoManagerInterface::class);
        $cryptoManager->method('findAll')->willReturn([
            $this->mockCrypto(Symbols::BTC),
            $this->mockCrypto(Symbols::ETH),
            $this->mockCrypto(Symbols::USDC),
            $this->mockCrypto(Symbols::BNB),
        ]);

        return $cryptoManager;
    }

    private function mockContractHandler(bool $returnTransactions = true): ContractHandlerInterface
    {
        $contractHandler = $this->createMock(ContractHandlerInterface::class);

        $transactions = [
            [
                'type' => 'deposit',
                'status' => 'paid',
                'crypto' => 'BNB',
                'timestamp' => (new \DateTimeImmutable('-1 minute'))->getTimestamp(),
            ],
        ];

        $contractHandler
            ->method('getAllRawTransactions')
            ->willReturn($returnTransactions ? $transactions : []);

        return $contractHandler;
    }

    private function mockCryptoInternalTransRep(bool $transactionsExists = true): CryptoInternalTransactionRepository
    {
        $cryptoInternalTransRep = $this->createMock(CryptoInternalTransactionRepository::class);

        $cryptoInternalTransRep
            ->method('createQueryBuilder')
            ->willReturn($this->mockQueryBuilder($transactionsExists));

        return $cryptoInternalTransRep;
    }

    private function mockQueryBuilder(bool $transactionsExists = true): QueryBuilder
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->method('where')
            ->willReturn($queryBuilder);
        $queryBuilder
            ->method('andWhere')
            ->willReturn($queryBuilder);
        $queryBuilder
            ->method('setParameter')
            ->willReturn($queryBuilder);
        $queryBuilder
            ->method('getQuery')
            ->willReturn($this->mockQuery($transactionsExists));

        return $queryBuilder;
    }

    private function mockQuery(bool $transactionsExists = true): Query
    {
        $query = $this->createMock(Query::class);
        $query
            ->method('getArrayResult')
            ->willReturn(
                $transactionsExists
                    ? [$this->createMock(CryptoInternalTransaction::class)]
                    : []
            );

        return $query;
    }

    private function mockBalanceFetcher(
        int $usersCount,
        int $initBalance,
        ?BalanceHistory $history = null
    ): BalanceFetcherInterface {
        $balanceFetcher = $this->createMock(BalanceFetcherInterface::class);

        $balances = array_map(static function ($i) use ($usersCount, $initBalance) {
            $balance = (string)($i * ($usersCount - $i + $initBalance));

            return [$i, $balance, $balance];
        }, range(1, $usersCount));

        $balanceFetcher->method('topBalances')->willReturnCallback(
            static function (string $crypto, int $limit, int $offset) use ($balances) {
                return array_slice($balances, $offset, $offset + $limit);
            }
        );

        $balanceFetcher->method('history')->willReturn($history ?? $this->mockBalanceHistory());

        return $balanceFetcher;
    }

    private function mockBalanceHistory(array $records = []): BalanceHistory
    {
        $balanceHistory = $this->createMock(BalanceHistory::class);
        $balanceHistory->method('getRecords')->willReturn($records);

        return $balanceHistory;
    }

    private function mockQuickTradeConfig(): QuickTradeConfig
    {
        $quickTradeConfigMock = $this->createMock(QuickTradeConfig::class);
        $quickTradeConfigMock->method('getMinAmountBySymbol')
            ->willReturnCallback(static function (string $symbol) {
                    return new Money('1', new Currency($symbol));
            });

        return $quickTradeConfigMock;
    }

    private function mockUserManager(bool $usersExist = true): UserManagerInterface
    {
        $user = $this->createMock(UserManagerInterface::class);
        $user->method('find')->willReturn($usersExist ? $this->mockUser() : null);

        return $user;
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    private function mockExchanger(int $invokedCount = 0, bool $successFul = false): ExchangerInterface
    {
        $exchanger = $this->createMock(ExchangerInterface::class);
        $exchanger->expects(self::exactly($invokedCount))
            ->method('executeOrder')
            ->willReturn($this->mockTradeResult($successFul));

        return $exchanger;
    }

    private function mockTradeResult(bool $successful): TradeResult
    {
        $tradeResult = $this->createMock(TradeResult::class);
        $tradeResult->method('getResult')->willReturn($successful ? 1 : 2);
        $tradeResult->method('getId')->willReturn($successful ? 1 : null);

        return $tradeResult;
    }

    private function mockMarketFactory(): MarketFactoryInterface
    {
        $marketFactory = $this->createMock(MarketFactoryInterface::class);
        $marketFactory->method('getMintMeCoinMarkets')->willReturn([
            $this->mockMarket(Symbols::BTC),
            $this->mockMarket(Symbols::ETH),
            $this->mockMarket(Symbols::USDC),
            $this->mockMarket(Symbols::BNB),
        ]);

        return $marketFactory;
    }

    private function mockMarket(string $baseSymbol): Market
    {
        $market = $this->createMock(Market::class);
        $market->method('getBase')->willReturn($this->mockCrypto($baseSymbol));
        $market->method('getQuote')->willReturn($this->mockCrypto("WEB"));

        return $market;
    }

    private function mockCrypto(string $symbol): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getSymbol')->willReturn($symbol);

        return $crypto;
    }

    private function mockInactiveOrderManager(bool $orderExists = false): InactiveOrderManagerInterface
    {
        $inactiveOrderManager = $this->createMock(InactiveOrderManagerInterface::class);
        $inactiveOrderManager->method('exists')->willReturn($orderExists);

        return $inactiveOrderManager;
    }

    private function mockEntityManager(): EntityManagerInterface
    {
        return $this->createMock(EntityManagerInterface::class);
    }

    private function mockCryptoRatesFetcher(): CryptoRatesFetcherInterface
    {
        $cryptoRatesFetcher = $this->createMock(CryptoRatesFetcherInterface::class);
        $cryptoRatesFetcher->method('fetch')->willReturn([
            Symbols::BTC => ["USD" => 10000],
            Symbols::ETH => ["USD" => 1000],
            Symbols::USDC => ["USD" => 1],
            Symbols::BNB => ["USD" => 20],
        ]);

        return $cryptoRatesFetcher;
    }

    private function mockLogger(?string $errorMessage = null): LoggerInterface
    {
        $logger = $this->createMock(LoggerInterface::class);

        if ($errorMessage) {
            $logger->method('error')->with($errorMessage);
        }

        return $logger;
    }
}
