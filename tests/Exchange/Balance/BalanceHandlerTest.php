<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Balance;

use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\UserToken;
use App\Exchange\Balance\BalanceFetcherInterface;
use App\Exchange\Balance\BalanceHandler;
use App\Exchange\Balance\Exception\BalanceException;
use App\Exchange\Balance\Factory\BalancesArrayFactoryInterface;
use App\Exchange\Balance\Factory\TraderBalanceViewFactoryInterface;
use App\Exchange\Balance\Factory\UpdateBalanceView;
use App\Exchange\Balance\Factory\UpdateBalanceViewFactoryInterface;
use App\Exchange\Balance\Model\BalanceHistory;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Manager\BonusBalanceTransactionManagerInterface;
use App\Manager\UserManagerInterface;
use App\Manager\UserTokenManagerInterface;
use App\Mercure\Publisher as MercurePublisher;
use App\Tests\Mocks\MockMoneyWrapper;
use App\Utils\Converter\TokenNameConverterInterface;
use App\Utils\RandomNumber;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use DateTimeImmutable;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class BalanceHandlerTest extends TestCase
{

    use MockMoneyWrapper;

    public function testBalances(): void
    {
        $converter = $this->mockTokenNameConverter();
        $converter->expects($this->exactly(3))->method('convert');

        $fetcher = $this->mockBalanceFetcher();
        $fetcher->expects($this->once())->method('balance')->with(1, [
            'fooFOO', 'barFOO', 'bazFOO',
        ]);

        $handler = $this->mockBalanceHandler(
            $converter,
            $fetcher
        );

        $handler->balances(
            $this->mockUser(1),
            [
                $this->mockToken('foo'),
                $this->mockToken('bar'),
                $this->mockToken('baz'),
            ]
        );
    }

    public function testHistory(): void
    {
        $converter = $this->mockTokenNameConverter();
        $converter->expects($this->exactly(0))->method('convert');

        $fetcher = $this->mockBalanceFetcher();
        $fetcher->expects($this->once())->method('history');

        $handler = $this->mockBalanceHandler(
            $converter,
            $fetcher
        );

        $result = $handler->history(1, 'foo', 'bar');

        $this->assertInstanceOf(BalanceHistory::class, $result);
    }

    public function testSummary(): void
    {
        $converter = $this->mockTokenNameConverter();
        $converter->expects($this->exactly(1))->method('convert');

        $fetcher = $this->mockBalanceFetcher();
        $fetcher->expects($this->once())->method('summary')->with('fooFOO');

        $handler = $this->mockBalanceHandler(
            $converter,
            $fetcher
        );

        $handler->summary(
            $this->mockToken('foo')
        );
    }

    public function testBalance(): void
    {
        $converter = $this->mockTokenNameConverter();
        $converter->expects($this->exactly(2))->method('convert');

        $fetcher = $this->mockBalanceFetcher();
        $fetcher->expects($this->once())->method('balance')->with(1, [
            'fooFOO',
        ]);

        $handler = $this->mockBalanceHandler(
            $converter,
            $fetcher
        );

        $handler->balance(
            $this->mockUser(1),
            $this->mockToken('foo')
        );
    }

    public function testGetReferralBalances(): void
    {
        $converter = $this->mockTokenNameConverter();
        $converter->expects($this->exactly(2))->method('convert');

        $fetcher = $this->mockBalanceFetcher();

        $handler = $this->mockBalanceHandler(
            $converter,
            $fetcher
        );

        /** @var Money[] $allBalances */
        $allBalances = $handler->getReferralBalances(
            $this->mockUser(1),
            [
                $this->mockToken('foo'),
            ]
        );

        $this->assertEquals('1', $allBalances['foo']->getAmount());
        $this->assertEquals('BTC', $allBalances['foo']->getCurrency());
    }

    public function testIndexedBalances(): void
    {
        $converter = $this->mockTokenNameConverter();
        $converter->expects($this->exactly(2))->method('convert');

        $fetcher = $this->mockBalanceFetcher();

        $handler = $this->mockBalanceHandler(
            $converter,
            $fetcher
        );

        /** @var BalanceResult[] $indexedBalances */
        $indexedBalances = $handler->indexedBalances(
            $this->mockUser(1),
            [
                $this->mockToken('foo'),
            ]
        );

        $this->assertEquals('1', $indexedBalances['foo']->getAvailable()->getAmount());
        $this->assertEquals('BTC', $indexedBalances['foo']->getAvailable()->getCurrency());
    }

    public function testTopHolders(): void
    {
        $converter = $this->mockTokenNameConverter();
        $converter->expects($this->once())->method('convert');

        $fetcher = $this->mockBalanceFetcher();
        $fetcher->expects($this->once())->method('topBalances')->with('fooFOO', 4);

        $handler = $this->mockBalanceHandler(
            $converter,
            $fetcher,
            $this->mockUserManager([$this->mockUserToken($this->mockUser(1), $this->mockDate())])
        );

        $handler->topHolders(
            $this->mockToken('foo'),
            3,
            4,
            1,
            5
        );
    }

    public function testTopHoldersWithManyFetches(): void
    {
        $converter = $this->mockTokenNameConverter();
        $converter->expects($this->exactly(2))->method('convert');

        $fetcher = $this->mockBalanceFetcher();
        $fetcher->expects($this->exactly(2))
            ->method('topBalances')
            ->willReturn([
                [1, '999', '999'],
                [2, '99', '99'],
                [3, '9', '9'],
            ]);

        $handler = $this->mockBalanceHandler(
            $converter,
            $fetcher,
            $this->mockUserManager([$this->mockUserToken($this->mockUser(1), $this->mockDate())])
        );

        $handler->topHolders(
            $this->mockToken('foo'),
            2,
            3,
            1,
            5
        );
    }

    public function testTopHoldersForMaxArgument(): void
    {
        $converter = $this->mockTokenNameConverter();
        $converter->expects($this->once())->method('convert');

        $fetcher = $this->mockBalanceFetcher();
        $fetcher->expects($this->once())
            ->method('topBalances')
            ->willReturn([
                [1, '999', '999'],
                [2, '99', '999'],
                [3, '9', '999'],
            ]);

        $handler = $this->mockBalanceHandler(
            $converter,
            $fetcher,
            $this->mockUserManager([$this->mockUserToken($this->mockUser(1), $this->mockDate())])
        );

        $handler->topHolders(
            $this->mockToken('foo'),
            2,
            3,
            1,
            3
        );
    }

    public function testIsExchanged(): void
    {
        $converter = $this->mockTokenNameConverter();
        $converter->expects($this->exactly(2))->method('convert');

        $res = $this->mockBalanceResult();
        $res->method('getAvailable')->willReturn(new Money(5, new Currency('FOO')));

        $resContainer = $this->mockBalanceResultContainer();
        $resContainer->method('get')->willReturn($res);

        $fetcher = $this->mockBalanceFetcher();
        $fetcher->expects($this->once())->method('balance')->with(5, [
            'fooFOO',
        ])->willReturn($resContainer);

        $handler = $this->mockBalanceHandler(
            $converter,
            $fetcher
        );

        $this->assertFalse(
            $handler->isNotExchanged(
                $this->mockToken('foo'),
                5
            )
        );
    }

    public function testIsNotExchanged(): void
    {
        $converter = $this->mockTokenNameConverter();
        $converter->expects($this->exactly(2))->method('convert');

        $res = $this->mockBalanceResult();
        $res->method('getAvailable')->willReturn(new Money(5, new Currency('FOO')));

        $resContainer = $this->mockBalanceResultContainer();
        $resContainer->method('get')->willReturn($res);

        $fetcher = $this->mockBalanceFetcher();
        $fetcher->expects($this->once())->method('balance')->with(5, [
            'fooFOO',
        ])->willReturn($resContainer);

        $handler = $this->mockBalanceHandler(
            $converter,
            $fetcher
        );

        $this->assertTrue(
            $handler->isNotExchanged(
                $this->mockToken('foo'),
                1
            )
        );
    }

    public function testDeposit(): void
    {
        $converter = $this->mockTokenNameConverter();
        $converter->expects($this->exactly(3))->method('convert');

        $fetcher = $this->mockBalanceFetcher();
        $fetcher->expects($this->once())
            ->method('update')
            ->with(1, 'fooFOO', 2, 'deposit');

        $handler = $this->mockBalanceHandler(
            $converter,
            $fetcher
        );

        $tok = $this->mockToken('foo');
        $handler->deposit(
            $this->mockUser(1, []),
            $tok,
            new Money(2, new Currency('FOO'))
        );
    }

    public function testDepositWithRelatedToken(): void
    {
        $converter = $this->mockTokenNameConverter();
        $converter->expects($this->exactly(3))->method('convert');

        $fetcher = $this->mockBalanceFetcher();
        $fetcher->expects($this->once())
            ->method('update')
            ->with(1, 'fooFOO', 2, 'deposit');

        $handler = $this->mockBalanceHandler(
            $converter,
            $fetcher
        );

        $tok = $this->mockToken('foo');
        $handler->deposit(
            $this->mockUser(1, [$tok]),
            $tok,
            new Money(2, new Currency('FOO'))
        );
    }

    public function testWithdraw(): void
    {
        $converter = $this->mockTokenNameConverter();
        $converter->expects($this->exactly(3))->method('convert');

        $fetcher = $this->mockBalanceFetcher();
        $fetcher->expects($this->once())
            ->method('update')
            ->with(1, 'fooFOO', -2, 'withdraw');

        $handler = $this->mockBalanceHandler(
            $converter,
            $fetcher
        );

        $handler->withdraw(
            $this->mockUser(1),
            $this->mockToken('foo'),
            new Money(2, new Currency('FOO'))
        );
    }

    public function testDepositWithException(): void
    {
        $converter = $this->mockTokenNameConverter();
        $converter->expects($this->exactly(1))->method('convert');

        $fetcher = $this->mockBalanceFetcher();
        $fetcher->expects($this->once())
            ->method('update')
            ->with(1, 'fooFOO', 2, 'deposit')
            ->willThrowException(new BalanceException());

        $handler = $this->mockBalanceHandler(
            $converter,
            $fetcher
        );

        $this->expectException(BalanceException::class);
        $handler->deposit(
            $this->mockUser(1),
            $this->mockToken('foo'),
            new Money(2, new Currency('FOO'))
        );
    }

    public function testWithdrawWithException(): void
    {
        $converter = $this->mockTokenNameConverter();
        $converter->expects($this->exactly(1))->method('convert');

        $fetcher = $this->mockBalanceFetcher();
        $fetcher->expects($this->once())
            ->method('update')
            ->with(1, 'fooFOO', -2, 'withdraw')
            ->willThrowException(new BalanceException());

        $handler = $this->mockBalanceHandler(
            $converter,
            $fetcher
        );

        $this->expectException(BalanceException::class);
        $handler->withdraw(
            $this->mockUser(1),
            $this->mockToken('foo'),
            new Money(2, new Currency('FOO'))
        );
    }

    public function testDepositBonus(): void
    {
        $bonusTransactionManager = $this->mockBonusBalanceTransactionManager();
        $bonusTransactionManager->method('getBalance')->willReturn(null);
        $bonusTransactionManager->expects($this->once())
            ->method('updateBalance')
            ->with(
                $this->mockUser(1),
                $this->mockToken('foo'),
                new Money(20, new Currency('FOO')),
                'deposit',
                'airdrop'
            );

        $handler = $this->mockBalanceHandler(
            null,
            null,
            null,
            $bonusTransactionManager
        );

        $handler->depositBonus(
            $this->mockUser(1),
            $this->mockToken('foo'),
            new Money(20, new Currency('FOO')),
            'airdrop'
        );
    }

    public function testWithdrawBonusEnough(): void
    {
        $bonusTransactionManager = $this->mockBonusBalanceTransactionManager();
        $bonusTransactionManager->method('getBalance')->willReturn(new Money(100, new Currency('FOO')));
        $bonusTransactionManager->expects($this->once())
            ->method('updateBalance')
            ->with(
                $this->mockUser(1),
                $this->mockToken('FOO'),
                new Money(100, new Currency('FOO')),
                'withdraw',
                'reward'
            );

        $handler = $this->mockBalanceHandler(
            null,
            null,
            null,
            $bonusTransactionManager
        );

        $response = $handler->withdrawBonus(
            $this->mockUser(1),
            $this->mockToken('FOO'),
            new Money(100, new Currency('FOO')),
            'reward'
        );

        $expectedResponse = new UpdateBalanceView(
            new Money(0, new Currency('FOO')),
            new Money(100, new Currency('FOO')),
        );

        $this->assertEquals($expectedResponse, $response);
    }

    public function testWithdrawBonusNotEnough(): void
    {
        $bonusTransactionManager = $this->mockBonusBalanceTransactionManager();
        $bonusTransactionManager->method('getBalance')->willReturn(new Money(100, new Currency('FOO')));
        $bonusTransactionManager->expects($this->once())
            ->method('updateBalance')
            ->with(
                $this->mockUser(1),
                $this->mockToken('foo'),
                new Money(100, new Currency('FOO')),
                'withdraw',
                'reward'
            );

        $fetcher = $this->mockBalanceFetcher();
        $fetcher->expects($this->once())
            ->method('update')
            ->with(1, 'fooFOO', -10, 'reward');

        $handler = $this->mockBalanceHandler(
            null,
            $fetcher,
            null,
            $bonusTransactionManager
        );

        $response = $handler->withdrawBonus(
            $this->mockUser(1),
            $this->mockToken('foo'),
            new Money(110, new Currency('FOO')),
            'reward'
        );

        $expectedResponse = new UpdateBalanceView(
            new Money(10, new Currency('FOO')),
            new Money(100, new Currency('FOO')),
        );

        $this->assertEquals($expectedResponse, $response);
    }

    private function mockToken(string $name): Token
    {
        $tok = $this->createMock(Token::class);
        $tok->method('getName')->willReturn($name);
        $tok->method('getSymbol')->willReturn($name);
        $tok->method('getId')->willReturn(1);
        $tok->method('getWithdrawn')->willReturn($this->mockMoney("1"));

        $profile = $this->createMock(Profile::class);
        $profile->method('getUser')->willReturn($this->mockUser(5));

        $tok->method('getProfile')->willReturn(
            $profile
        );

        return $tok;
    }

    private function mockUser(int $id, array $toks = []): User
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        $user->method('getTokens')->willReturn($toks);

        return $user;
    }

    private function mockLogger(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }

    /** @return MockObject|TraderBalanceViewFactoryInterface */
    private function mockTraderBalanceViewFactory(): TraderBalanceViewFactoryInterface
    {
        return $this->createMock(TraderBalanceViewFactoryInterface::class);
    }

    /** @return BalanceFetcherInterface|MockObject */
    private function mockBalanceFetcher(): BalanceFetcherInterface
    {
        $balanceFetcher = $this->createMock(BalanceFetcherInterface::class);
        $balanceFetcher->method('balance')->willReturn($this->mockBalanceResultContainer());

        return $balanceFetcher;
    }

    /** @return TokenNameConverterInterface|MockObject */
    private function mockTokenNameConverter(): TokenNameConverterInterface
    {
        $converter = $this->createMock(TokenNameConverterInterface::class);
        $converter->method('convert')->willReturnCallback(function (Token $token): string {
            return $token->getName().'FOO';
        });

        return $converter;
    }

    /** @return BalanceResultContainer|MockObject */
    private function mockBalanceResultContainer(): BalanceResultContainer
    {
        $balanceResultContainer = $this->createMock(BalanceResultContainer::class);
        $balanceResultContainer->method('get')->willReturn($this->mockBalanceResult());

        return $balanceResultContainer;
    }

    /** @return BalanceResult|MockObject */
    private function mockBalanceResult(): BalanceResult
    {
        $balanceResult = $this->createMock(BalanceResult::class);
        $balanceResult->method('getAvailable')->willReturn($this->mockMoney('1', Symbols::BTC));
        $balanceResult->method('getReferral')->willReturn($this->mockMoney('1', Symbols::BTC));

        return $balanceResult;
    }

    /**
     * @return UserManagerInterface|MockObject
     */
    private function mockUserManager(array $UsersTokens): UserManagerInterface
    {
        $manager = $this->createMock(UserManagerInterface::class);
        $manager->method('getUserToken')->willReturn($UsersTokens);

        return $manager;
    }

    /**
     * @return BonusBalanceTransactionManagerInterface|MockObject
     */
    private function mockBonusBalanceTransactionManager(): BonusBalanceTransactionManagerInterface
    {
        return $this->createMock(BonusBalanceTransactionManagerInterface::class);
    }

    /**
     * @return UserToken|MockObject
     */
    private function mockUserToken(User $user, DateTimeImmutable $date): UserToken
    {
        $userToken = $this->createMock(UserToken::class);
        $userToken->method('getUser')->willReturn($user);
        $userToken->method('getCreated')->willReturn($date);

        return $userToken;
    }

    /**
     * @return BalancesArrayFactoryInterface|MockObject
     */
    private function mockBalancesArrayFactory(): BalancesArrayFactoryInterface
    {
        $factory = $this->createMock(BalancesArrayFactoryInterface::class);
        $factory->method('create')->will(self::returnCallback(function (array $balances) {
            return $balances;
        }));

        return $factory;
    }

    private function mockDate(): DateTimeImmutable
    {
        return $this->createMock(DateTimeImmutable::class);
    }

    private function mockUserTokenManager(): UserTokenManagerInterface
    {
        return $this->createMock(UserTokenManagerInterface::class);
    }


    private function mockMoney(string $amount, string $currency = Symbols::TOK): Money
    {
        return new Money($amount, new Currency($currency));
    }

    private function mockUpdateBalanceViewFactory(): UpdateBalanceViewFactoryInterface
    {
        $updateBalanceViewFactory = $this->createMock(UpdateBalanceViewFactoryInterface::class);
        $updateBalanceViewFactory->method('createUpdateBalanceView')
            ->willReturn($this->mockUpdateBalanceView());

        return $updateBalanceViewFactory;
    }

    private function mockUpdateBalanceView(): UpdateBalanceView
    {
        $updateBalanceView = $this->createMock(UpdateBalanceView::class);
        $updateBalanceView->method('getChange')->willReturn($this->mockMoney('1', Symbols::BTC));

        return $updateBalanceView;
    }

    private function mockRandomNumber(): RandomNumber
    {
        return $this->createMock(RandomNumber::class);
    }

    private function mockMercurePublisher(): MercurePublisher
    {
        return $this->createMock(MercurePublisher::class);
    }

    private function mockBalanceHandler(
        ?TokenNameConverterInterface $converter = null,
        ?BalanceFetcherInterface $balanceFetcher = null,
        ?UserManagerInterface $userManager = null,
        ?BonusBalanceTransactionManagerInterface $bonusBalanceTransactionManager = null,
        ?BalancesArrayFactoryInterface $balanceArrayFactory = null,
        ?MoneyWrapperInterface $moneyWrapper = null,
        ?TraderBalanceViewFactoryInterface $traderBalanceViewFactory = null,
        ?LoggerInterface $logger = null,
        ?UserTokenManagerInterface $userTokenManager = null,
        ?MercurePublisher $mercurePublisher = null
    ): BalanceHandler {
        return new BalanceHandler(
            $converter ?? $this->mockTokenNameConverter(),
            $balanceFetcher ?? $this->mockBalanceFetcher(),
            $userManager ?? $this->mockUserManager([]),
            $bonusBalanceTransactionManager ?? $this->mockBonusBalanceTransactionManager(),
            $balanceArrayFactory ?? $this->mockBalancesArrayFactory(),
            $moneyWrapper ?? $this->mockMoneyWrapper(),
            $traderBalanceViewFactory ?? $this->mockTraderBalanceViewFactory(),
            $logger ?? $this->mockLogger(),
            $userTokenManager ?? $this->mockUserTokenManager(),
            $this->mockUpdateBalanceViewFactory(),
            $this->mockRandomNumber(),
            $mercurePublisher ?? $this->mockMercurePublisher()
        );
    }
}
