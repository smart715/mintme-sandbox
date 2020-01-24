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
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Exchange\Order;
use App\Manager\UserManagerInterface;
use App\Tests\MockMoneyWrapper;
use App\Utils\Converter\TokenNameConverterInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
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

        $handler = new BalanceHandler(
            $converter,
            $fetcher,
            $this->mockEm(),
            $this->mockUserManager([]),
            $this->mockBalancesArrayFactory(),
            $this->mockMoneyWrapper(),
            $this->mockTraderBalanceViewFactory(),
            $this->mockLogger()
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

    public function testSummary(): void
    {
        $converter = $this->mockTokenNameConverter();
        $converter->expects($this->exactly(1))->method('convert');

        $fetcher = $this->mockBalanceFetcher();
        $fetcher->expects($this->once())->method('summary')->with('fooFOO');

        $handler = new BalanceHandler(
            $converter,
            $fetcher,
            $this->mockEm(),
            $this->mockUserManager([]),
            $this->mockBalancesArrayFactory(),
            $this->mockMoneyWrapper(),
            $this->mockTraderBalanceViewFactory(),
            $this->mockLogger()
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

        $handler = new BalanceHandler(
            $converter,
            $fetcher,
            $this->mockEm(),
            $this->mockUserManager([]),
            $this->mockBalancesArrayFactory(),
            $this->mockMoneyWrapper(),
            $this->mockTraderBalanceViewFactory(),
            $this->mockLogger()
        );

        $handler->balance(
            $this->mockUser(1),
            $this->mockToken('foo')
        );
    }

    public function testTopHolders(): void
    {
        $converter = $this->mockTokenNameConverter();
        $converter->expects($this->once())->method('convert');

        $fetcher = $this->mockBalanceFetcher();
        $fetcher->expects($this->once())->method('topBalances')->with('fooFOO', 4);

        $handler = new BalanceHandler(
            $converter,
            $fetcher,
            $this->mockEm(),
            $this->mockUserManager([$this->mockUserToken($this->mockUser(1), $this->mockDate())]),
            $this->mockBalancesArrayFactory(),
            $this->mockMoneyWrapper(),
            $this->mockTraderBalanceViewFactory(),
            $this->mockLogger()
        );

        $handler->topHolders(
            $this->mockToken('foo'),
            3,
            4,
            1,
            5
        );
    }

    public function testTopHoldersWithManyFetchs(): void
    {
        $converter = $this->mockTokenNameConverter();
        $converter->expects($this->exactly(2))->method('convert');

        $fetcher = $this->mockBalanceFetcher();
        $fetcher->expects($this->exactly(2))
            ->method('topBalances')
            ->willReturn([
                [1, '999'],
                [2, '99'],
                [3, '9'],
            ]);

        $handler = new BalanceHandler(
            $converter,
            $fetcher,
            $this->mockEm(),
            $this->mockUserManager([$this->mockUserToken($this->mockUser(1), $this->mockDate())]),
            $this->mockBalancesArrayFactory(),
            $this->mockMoneyWrapper(),
            $this->mockTraderBalanceViewFactory(),
            $this->mockLogger()
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
                [1, '999'],
                [2, '99'],
                [3, '9'],
            ]);

        $handler = new BalanceHandler(
            $converter,
            $fetcher,
            $this->mockEm(),
            $this->mockUserManager([$this->mockUserToken($this->mockUser(1), $this->mockDate())]),
            $this->mockBalancesArrayFactory(),
            $this->mockMoneyWrapper(),
            $this->mockTraderBalanceViewFactory(),
            $this->mockLogger()
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
        $res->method('getAvailable')->willReturn(new Money(2, new Currency('FOO')));

        $resContainer = $this->mockBalanceResultContainer();
        $resContainer->method('get')->willReturn($res);

        $fetcher = $this->mockBalanceFetcher();
        $fetcher->expects($this->once())->method('balance')->with(5, [
            'fooFOO',
        ])->willReturn($resContainer);

        $handler = new BalanceHandler(
            $converter,
            $fetcher,
            $this->mockEm(),
            $this->mockUserManager([]),
            $this->mockBalancesArrayFactory(),
            $this->mockMoneyWrapper(),
            $this->mockTraderBalanceViewFactory(),
            $this->mockLogger()
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

        $handler = new BalanceHandler(
            $converter,
            $fetcher,
            $this->mockEm(),
            $this->mockUserManager([]),
            $this->mockBalancesArrayFactory(),
            $this->mockMoneyWrapper(),
            $this->mockTraderBalanceViewFactory(),
            $this->mockLogger()
        );

        $this->assertTrue(
            $handler->isNotExchanged(
                $this->mockToken('foo'),
                5
            )
        );
    }

    public function testSoldOnMarket(): void
    {
        $converter = $this->mockTokenNameConverter();
//        $converter->expects($this->exactly(2))->method('convert');

        $res = $this->mockBalanceResult();
        $res->method('getAvailable')->willReturn(new Money(2, new Currency('FOO')));

        $resContainer = $this->mockBalanceResultContainer();
        $resContainer->method('get')->willReturn($res);

        $fetcher = $this->mockBalanceFetcher();
        $fetcher->expects($this->once())->method('balance')->with(5, [
            'fooFOO',
        ])->willReturn($resContainer);

        $handler = new BalanceHandler(
            $converter,
            $fetcher,
            $this->mockEm(),
            $this->mockUserManager([]),
            $this->mockBalancesArrayFactory(),
            $this->mockMoneyWrapper(),
            $this->mockTraderBalanceViewFactory(),
            $this->mockLogger()
        );

        $ownPendingOrders = [$this->mockOrder(1, 1), $this->mockOrder(2, 1)];

        $this->assertEquals(
            $handler->soldOnMarket($this->mockToken('foo'), 5, $ownPendingOrders)->getAmount(),
            '2'
        );
    }

    public function testDeposit(): void
    {
        $converter = $this->mockTokenNameConverter();
        $converter->expects($this->exactly(1))->method('convert');

        $fetcher = $this->mockBalanceFetcher();
        $fetcher->expects($this->once())
            ->method('update')
            ->with(1, 'fooFOO', 2, 'deposit');

        $em = $this->mockEm();
        $em->expects($this->once())->method('flush');

        $handler = new BalanceHandler(
            $converter,
            $fetcher,
            $em,
            $this->mockUserManager([]),
            $this->mockBalancesArrayFactory(),
            $this->mockMoneyWrapper(),
            $this->mockTraderBalanceViewFactory(),
            $this->mockLogger()
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
        $converter->expects($this->exactly(1))->method('convert');

        $fetcher = $this->mockBalanceFetcher();
        $fetcher->expects($this->once())
            ->method('update')
            ->with(1, 'fooFOO', 2, 'deposit');

        $em = $this->mockEm();
        $em->expects($this->never())->method('flush');

        $handler = new BalanceHandler(
            $converter,
            $fetcher,
            $em,
            $this->mockUserManager([]),
            $this->mockBalancesArrayFactory(),
            $this->mockMoneyWrapper(),
            $this->mockTraderBalanceViewFactory(),
            $this->mockLogger()
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
        $converter->expects($this->exactly(1))->method('convert');

        $fetcher = $this->mockBalanceFetcher();
        $fetcher->expects($this->once())
            ->method('update')
            ->with(1, 'fooFOO', -2, 'withdraw');

        $handler = new BalanceHandler(
            $converter,
            $fetcher,
            $this->mockEm(),
            $this->mockUserManager([]),
            $this->mockBalancesArrayFactory(),
            $this->mockMoneyWrapper(),
            $this->mockTraderBalanceViewFactory(),
            $this->mockLogger()
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

        $handler = new BalanceHandler(
            $converter,
            $fetcher,
            $this->mockEm(),
            $this->mockUserManager([]),
            $this->mockBalancesArrayFactory(),
            $this->mockMoneyWrapper(),
            $this->mockTraderBalanceViewFactory(),
            $this->mockLogger()
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

        $handler = new BalanceHandler(
            $converter,
            $fetcher,
            $this->mockEm(),
            $this->mockUserManager([]),
            $this->mockBalancesArrayFactory(),
            $this->mockMoneyWrapper(),
            $this->mockTraderBalanceViewFactory(),
            $this->mockLogger()
        );

        $this->expectException(BalanceException::class);
        $handler->withdraw(
            $this->mockUser(1),
            $this->mockToken('foo'),
            new Money(2, new Currency('FOO'))
        );
    }

    private function mockToken(string $name): Token
    {
        $tok = $this->createMock(Token::class);
        $tok->method('getName')->willReturn($name);
        $tok->method('getId')->willReturn(1);

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

    /** @return EntityManagerInterface|MockObject */
    private function mockEm(): EntityManagerInterface
    {
        return $this->createMock(EntityManagerInterface::class);
    }

    /** @return BalanceFetcherInterface|MockObject */
    private function mockBalanceFetcher(): BalanceFetcherInterface
    {
        return $this->createMock(BalanceFetcherInterface::class);
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
        return $this->createMock(BalanceResultContainer::class);
    }

    /** @return BalanceResult|MockObject */
    private function mockBalanceResult(): BalanceResult
    {
        return $this->createMock(BalanceResult::class);
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

    private function mockOrder(int $side, int $amount): Order
    {
        $order = $this->createMock(Order::class);
        $order->method('getSide')->willReturn($side);
        $order->method('getAmount')->willReturn(new Money($amount, new Currency('FOO')));

        return $order;
    }
}
