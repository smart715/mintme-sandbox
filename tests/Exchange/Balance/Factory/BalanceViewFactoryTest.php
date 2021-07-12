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
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Converter\TokenNameConverterInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class BalanceViewFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $tokens = [
            [
                'id' => null,
                'name' => 'foo',
                'hidden' => true,
                'crypto' => true,
                'lockIn' => true,
                'status' => 'not-deployed',
            ],
            [
                'id' => null,
                'name' => 'bar',
                'hidden' => false,
                'crypto' => true,
                'lockIn' => false,
                'status' => 'not-deployed',
            ],
            ['id' => 1, 'name' => 'baz', 'hidden' => false, 'crypto' => false, 'lockIn' => true, 'status' => 'pending'],
            ['id' => 2, 'name' => 'qux', 'hidden' => true, 'crypto' => false, 'lockIn' => false, 'status' => 'pending'],
            [
                'id' => null,
                'name' => 'empty',
                'hidden' => null,
                'crypto' => false,
                'lockIn' => false,
                'status' => 'deployed',
            ],
            [
                'id' => null,
                'name' => 'lok',
                'hidden' => false,
                'crypto' => true,
                'lockIn' => true,
                'status' => 'deployed',
            ],
        ];

        $factory = new BalanceViewFactory(
            $this->mockCryptoManager(),
            $this->mockTokenManager($tokens),
            $this->mockTokenNameConverter(),
            4
        );

        $user = $this->createMock(User::class);

        $view = $factory->create(
            $this->mockBalanceResultContainer(array_map(function (array $tok): string {
                return $tok['name'];
            }, $tokens)),
            $user
        );

        $this->assertEquals([
            'foo' => ['1', '1', '1', 'foo', 'fooBAR', 4, false, true, false],
            'bar' => ['1', '1', null, 'bar', 'barBAR', 4, true, true, false],
            'baz' => ['1', '1', '1', 'baz', 'bazBAR', 4, false, false, false],
            'qux' => ['1', '1', null, 'qux', 'quxBAR', 4, false, false, false],
            'lok' => ['1', '1', '1', 'lok', 'lokBAR', 4, true, true, true],
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

    private function mockBalanceResultContainer(array $names): BalanceResultContainer
    {
        $brc = $this->createMock(BalanceResultContainer::class);
        $brc->method('getIterator')->willReturn(new \ArrayIterator(array_map(function (): BalanceResult {
            return $this->createMock(BalanceResult::class);
        }, array_flip($names))));

        return $brc;
    }

    private function mockTokenManager(array $tokens): TokenManagerInterface
    {
        $tm = $this->createMock(TokenManagerInterface::class);

        $tm->method('findByName')->willReturnCallback(function ($name) use ($tokens): ?Token {
            foreach ($tokens as $token) {
                if (false === $token['hidden'] && $token['name'] === $name) {
                    return $this->mockToken(
                        $token['id'],
                        $name,
                        $token['crypto'],
                        $token['lockIn'],
                        $token['hidden'],
                        $token['status']
                    );
                }
            }

            return null;
        });

        $tm->method('findByHiddenName')->willReturnCallback(function ($name) use ($tokens): ?Token {
            foreach ($tokens as $token) {
                if (true === $token['hidden'] && $token['name'] === $name) {
                    return $this->mockToken(
                        $token['id'],
                        $name,
                        $token['crypto'],
                        $token['lockIn'],
                        $token['hidden'],
                        $token['status']
                    );
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

    private function mockToken(
        ?int $id,
        string $name,
        bool $hasCrypto,
        bool $hasLockIn,
        bool $isHidden,
        string $status
    ): Token {
        $tok = $this->createMock(Token::class);
        $tok->method('getName')->willReturn($name);
        $tok->method('getCrypto')->willReturn(
            $hasCrypto ? $this->mockCrypto($isHidden) : null
        );
        $tok->method('getId')->willReturn($id);
        $tok->method('getFee')->willReturn(new Money(1, new Currency('FOO')));

        $lockIn = $this->createMock(LockIn::class);
        $lockIn->method('getFrozenAmount')->willReturn(new Money(1, new Currency('FOO')));

        $tok->method('getLockIn')->willReturn($hasLockIn ? $lockIn : null);

        $tok->method('getDeploymentStatus')->willReturn($status);

        return $tok;
    }

    private function mockCrypto(bool $isHidden): Crypto
    {
        $crypto = $this->createMock(Crypto::class);
        $crypto->method('getName')->willReturn('FOO');
        $crypto->method('getFee')->willReturn(new Money(2, new Currency('FOO')));
        $crypto->method('getShowSubunit')->willReturn(4);
        $crypto->method('isTradable')->willReturn(true);
        $crypto->method('isExchangeble')->willReturn($isHidden ? false : true);

        return $crypto;
    }

    private function mockCryptoManager(): CryptoManagerInterface
    {
        $cm = $this->createMock(CryptoManagerInterface::class);

        $cm->method('findBySymbol')->willReturnCallback(mockCrypto(false));

        return $cm;
    }
}
