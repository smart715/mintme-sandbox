<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Balance\Factory;

use App\Entity\Crypto;
use App\Entity\Token\LockIn;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\Factory\BalanceView;
use App\Exchange\Balance\Factory\BalanceViewFactory;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Manager\TokenManagerInterface;
use App\Utils\Converter\TokenNameConverterInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class BalanceViewFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $tokens = [
            ['name' => 'foo', 'hidden' => true, 'crypto' => true, 'lockIn' => true],
            ['name' => 'bar', 'hidden' => false, 'crypto' => true, 'lockIn' => false],
            ['name' => 'baz', 'hidden' => false, 'crypto' => false, 'lockIn' => true],
            ['name' => 'qux', 'hidden' => true, 'crypto' => false, 'lockIn' => false],
            ['name' => 'empty', 'hidden' => null, 'crypto' => false, 'lockIn' => false],
        ];

        $factory = new BalanceViewFactory(
            $this->mockTokenStorage(true, true),
            $this->mockTokenManager($tokens),
            $this->mockTokenNameConverter(),
            4
        );

        $view = $factory->create(
            $this->mockBalanceResultContainer(array_map(function (array $tok): string {
                return $tok['name'];
            }, $tokens))
        );

        $this->assertEquals([
            'foo' => ['1', '1', '1', 'FOO', 'fooBAR', 4, false, false],
            'bar' => ['1', '1', null, 'FOO', 'barBAR', 4, false, false],
            'baz' => ['1', null, '1', 'baz', 'bazBAR', 4, false, false],
            'qux' => ['1', null, null, 'qux', 'quxBAR', 4, false, false],
        ], array_map(function (BalanceView $view): array {
            return [
                $view->getAvailable()->getAmount(),
                $view->getFee() ? $view->getFee()->getAmount(): null,
                $view->getFrozen() ? $view->getFrozen()->getAmount(): null,
                $view->getFullname(),
                $view->getIdentifier(),
                $view->getSubunit(),
                $view->isOwner(),
                $view->isNotExchanged(),
            ];
        }, $view));
    }

    private function mockBalanceResultContainer(array $names): BalanceResultContainer
    {
        $brc = $this->createMock(BalanceResultContainer::class);
        $brc->method('getIterator')->willReturn(new \ArrayIterator(array_map(function (): BalanceResult {
            return $this->createMock(BalanceResult::class);
        }, array_flip($names))));

        return $brc;
    }

    private function mockTokenStorage(bool $hasToken, bool $hasUser): TokenStorageInterface
    {
        $ts = $this->createMock(TokenStorageInterface::class);
        $tok = $this->createMock(TokenInterface::class);

        $tok->method('getUser')->willReturn($hasUser ? $this->createMock(User::class) : null);
        $ts->method('getToken')->willReturn($hasToken ? $tok : null);

        return $ts;
    }

    private function mockTokenManager(array $tokens): TokenManagerInterface
    {
        $tm = $this->createMock(TokenManagerInterface::class);

        $tm->method('findByName')->willReturnCallback(function ($name) use ($tokens): ?Token {
            foreach ($tokens as $token) {
                if (false === $token['hidden'] && $token['name'] === $name) {
                    return $this->mockToken($name, $token['crypto'], $token['lockIn']);
                }
            }

            return null;
        });

        $tm->method('findByHiddenName')->willReturnCallback(function ($name) use ($tokens): ?Token {
            foreach ($tokens as $token) {
                if (true === $token['hidden'] && $token['name'] === $name) {
                    return $this->mockToken($name, $token['crypto'], $token['lockIn']);
                }
            }

            return null;
        });

        $res = $this->createMock(BalanceResult::class);
        $res->method('getAvailable')->willReturn(new Money(1, new Currency('FOO')));

        $tm->method('getRealBalance')->willReturn($res);

        return $tm;
    }

    private function mockTokenNameConverter(): TokenNameConverterInterface
    {
        $converter = $this->createMock(TokenNameConverterInterface::class);
        $converter->method('convert')->willReturnCallback(function (Token $token): string {
            return $token->getName().'BAR';
        });

        return $converter;
    }

    private function mockToken(string $name, bool $hasCrypto, bool $hasLockIn): Token
    {
        $tok = $this->createMock(Token::class);
        $tok->method('getName')->willReturn($name);
        $tok->method('getCrypto')->willReturn(
            $hasCrypto ? $this->mockCrypto() : null
        );

        $lockIn = $this->createMock(LockIn::class);
        $lockIn->method('getFrozenAmount')->willReturn(new Money(1, new Currency('FOO')));

        $tok->method('getLockIn')->willReturn($hasLockIn ? $lockIn : null);

        return $tok;
    }

    private function mockCrypto(): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getName')->willReturn('FOO');
        $crypto->method('getFee')->willReturn(new Money(1, new Currency('FOO')));
        $crypto->method('getShowSubunit')->willReturn(4);

        return $crypto;
    }
}
