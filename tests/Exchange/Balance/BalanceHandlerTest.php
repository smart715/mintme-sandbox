<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Balance;

use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceFetcherInterface;
use App\Exchange\Balance\BalanceHandler;
use App\Exchange\Balance\Exception\BalanceException;
use App\Exchange\Balance\Factory\TraderBalanceViewFactoryInterface;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Utils\Converter\TokenNameConverterInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class BalanceHandlerTest extends TestCase
{
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
            $this->mockMoneyWrapper(),
            $this->mockTraderBalanceViewFactory(),
            $this->mockLogger()
        );

        $handler->balance(
            $this->mockUser(1),
            $this->mockToken('foo')
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
        $user->method('getRelatedTokens')->willReturn($toks);

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

    private function mockMoneyWrapper(): MoneyWrapperInterface
    {
        $wrapper = $this->createMock(MoneyWrapperInterface::class);

        $wrapper->method('parse')->willReturnCallback(function (string $amount, string $symbol) {
            return new Money($amount, new Currency($symbol));
        });

        $wrapper->method('format')->willReturnCallback(function (Money $money) {
            return $money->getAmount();
        });

        return $wrapper;
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
}
