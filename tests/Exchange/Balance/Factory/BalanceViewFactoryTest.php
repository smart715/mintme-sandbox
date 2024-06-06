<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Balance\Factory;

use App\Entity\Crypto;
use App\Entity\Token\LockIn;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Entity\User;
use App\Entity\UserToken;
use App\Exchange\Balance\Factory\BalanceView;
use App\Exchange\Balance\Factory\BalanceViewFactory;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserTokenManagerInterface;
use App\Utils\Converter\TokenNameConverterInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class BalanceViewFactoryTest extends TestCase
{
    private const TOKEN_SUBUNIT = 4;
    private const AVAILABLE_BALANCE = 1;
    private const REAL_AVAILABLE_BALANCE = 2;
    private const BONUS_BALANCE = 3;
    public function testCreate(): void
    {
        $factory = new BalanceViewFactory(
            $this->mockTokenManager(),
            $this->mockUserTokenManager(),
            $this->mockTokenNameConverter(),
            self::TOKEN_SUBUNIT
        );

        $tradables = [
            $this->mockCrypto(true),
            $this->mockToken(
                1,
                'TOK0001',
                true,
                true,
                true,
                true
            ),
            $this->mockToken(
                2,
                'TOK0002',
                false,
                false,
                false,
                false
            ),
        ];
        $balanceResultContainer = $this->mockBalanceResultContainer($tradables);
        $user = $this->createMock(User::class);

        $view = $factory->create(
            $tradables,
            $balanceResultContainer,
            $user
        );

        $this->assertEquals([
            'WEB' => ['1', '2', null, 'WEB', 'WEBBAR', 4, false, true, false],
            'TOK0001' => ['2', '1', 1, 'TOK0001', 'TOK0001BAR', 4, false, false, true],
            'TOK0002' => ['2', '1', null, 'TOK0002', 'TOK0002BAR', 4, false, false, false],
        ], array_map(function (BalanceView $view): array {
            return [
                $view->getAvailable()->getAmount(),
                $view->getFee()->getAmount(),
                $view->getFrozen() ? $view->getFrozen()->getAmount() : null,
                $view->getFullname(),
                $view->getIdentifier(),
                $view->getSubunit(),
                $view->isExchangeble(),
                $view->isTradable(),
                $view->isDeployed(),
            ];
        }, $view));
    }

    private function mockTokenManager(): TokenManagerInterface
    {
        $tm = $this->createMock(TokenManagerInterface::class);
        $tm->method('getRealBalance')
            ->willReturn($this->mockBalanceResult(true));

        return $tm;
    }

    private function mockUserTokenManager(): UserTokenManagerInterface
    {
        $userTokenManager = $this->createMock(UserTokenManagerInterface::class);
        $userTokenManager->method('findByUserToken')->willReturn($this->mockUserToken());

        return $userTokenManager;
    }

    private function mockUserToken(): UserToken
    {
        return $this->createMock(UserToken::class);
    }

    private function mockTokenNameConverter(): TokenNameConverterInterface
    {
        $converter = $this->createMock(TokenNameConverterInterface::class);
        $converter
            ->method('convert')
            ->willReturnCallback(function (TradableInterface $tradable): string {
                return $this->convertTokName($tradable);
            });

        return $converter;
    }

    private function convertTokName(TradableInterface $tradable): string
    {
        return $tradable instanceof Token
            ? $tradable->getName() .'BAR'
            : $tradable->getSymbol() .'BAR';
    }

    private function mockCrypto(bool $isHidden): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getName')->willReturn('WEB');
        $crypto->method('getSymbol')->willReturn('WEB');
        $crypto->method('getFee')->willReturn(new Money(2, new Currency('WEB')));
        $crypto->method('getShowSubunit')->willReturn(4);
        $crypto->method('isTradable')->willReturn(true);
        $crypto->method('isExchangeble')->willReturn($isHidden ? false : true);

        return $crypto;
    }

    private function mockToken(
        ?int $id,
        string $name,
        bool $hasCrypto,
        bool $hasLockIn,
        bool $isHidden,
        bool $isDeployed
    ): Token {
        $tok = $this->createMock(Token::class);
        $tok->method('getName')->willReturn($name);
        $tok->method('getSymbol')->willReturn($name);
        $tok->method('getCrypto')->willReturn(
            $hasCrypto ? $this->mockCrypto($isHidden) : null
        );
        $tok->method('getId')->willReturn($id);
        $tok->method('getFee')->willReturn(new Money(1, new Currency('FOO')));

        $lockIn = $this->createMock(LockIn::class);
        $lockIn->method('getFrozenAmount')->willReturn(new Money(1, new Currency('FOO')));

        $tok->method('getLockIn')->willReturn($hasLockIn ? $lockIn : null);

        $tok->method('isDeployed')->willReturn($isDeployed);

        return $tok;
    }

    private function mockBalanceResultContainer(array $tradables): BalanceResultContainer
    {
        $tokNames = array_map(
            function ($tradable): string {
                return $this->convertTokName($tradable);
            },
            $tradables
        );

        $brc = $this->createMock(BalanceResultContainer::class);
        $brc->method('getIterator')->willReturn(new \ArrayIterator(array_map(function (): BalanceResult {
            return $this->mockBalanceResult();
        }, array_flip($tokNames))));

        return $brc;
    }

    private function mockBalanceResult(bool $realBalance = false): BalanceResult
    {
        $br = $this->createMock(BalanceResult::class);
        $br->method('getAvailable')
            ->willReturn(new Money(
                $realBalance ? self::REAL_AVAILABLE_BALANCE : self::AVAILABLE_BALANCE,
                new Currency('FOO')
            ));
        $br->method('getBonus')
            ->willReturn(new Money(self::BONUS_BALANCE, new Currency('FOO')));

        return $br;
    }
}
