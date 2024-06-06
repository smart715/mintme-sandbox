<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Balance\Factory;

use App\Entity\Crypto;
use App\Entity\Image;
use App\Entity\Token\Token;
use App\Entity\TopHolder;
use App\Entity\User;
use App\Entity\UserToken;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Factory\TokensUserOwnsView;
use App\Exchange\Balance\Factory\TokensUserOwnsViewFactory;
use App\Exchange\Balance\Model\BalanceResult;
use App\Manager\TokenManagerInterface;
use App\Manager\TopHolderManagerInterface;
use App\Utils\Symbols;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class TokensUserOwnsViewFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $factory = new TokensUserOwnsViewFactory(
            $this->mockTokenManager(),
            $this->createMock(BalanceHandlerInterface::class),
            $this->createMock(TopHolderManagerInterface::class)
        );

        $view = $factory->create([$this->mockUserToken()]);

        $this->assertInstanceOf(TokensUserOwnsView::class, $view['TEST']);
    }

    public function testCreateWithNoToken(): void
    {
        $factory = new TokensUserOwnsViewFactory(
            $this->mockTokenManager(),
            $this->createMock(BalanceHandlerInterface::class),
            $this->createMock(TopHolderManagerInterface::class)
        );

        $view = $factory->create([]);

        $this->assertEquals([], $view);
    }

    public function testCreateWithMultipleTokens(): void
    {
        $tokens = [
            [
                'name' => 'foo',
                'available' => 3,
                'rank' => 5,
            ],
            [
                'name' => 'bar',
                'available' => 5,
                'rank' => null,
            ],
            [
                'name' => 'lok',
                'available' => 14,
                'rank' => 1,
            ],
        ];

        $topHolderManager = $this->createMock(TopHolderManagerInterface::class);

        $topHolderManager
            ->method('getTopHolderByUserAndToken')
            ->willReturnOnConsecutiveCalls(
                $this->mockTopHolder($tokens[0]['rank'], (string)$tokens[0]['available']),
                null,
                $this->mockTopHolder($tokens[2]['rank'], (string)$tokens[2]['available'])
            );

        $factory = new TokensUserOwnsViewFactory(
            $this->mockTokensArrayManager($tokens),
            $this->createMock(BalanceHandlerInterface::class),
            $topHolderManager
        );

        $userTokens = array_map(
            fn($token) => $this->mockUserToken($token['name']),
            $tokens
        );

        $view = $factory->create($userTokens);

        $this->assertEquals([
            0 => ['14', 'lok', 1],
            1 => ['5', 'bar', null],
            2 => ['3', 'foo', 5],
        ], array_map(function (TokensUserOwnsView $view): array {
            return [
                $view->getAvailable()->getAmount(),
                $view->getName(),
                $view->getRank(),
            ];
        }, $view));
    }

    private function mockTopHolder(int $rank, string $amount): TopHolder
    {
        $th = $this->createMock(TopHolder::class);

        $th
            ->method('getRank')
            ->willReturn($rank);

        $th
            ->method('getAmount')
            ->willReturn(new Money($amount, new Currency(Symbols::TOK)));

        return $th;
    }

    private function mockUserToken(string $tokenName = 'TEST'): UserToken
    {
        $userToken = $this->createMock(UserToken::class);
        $userToken
            ->method('getUser')
            ->willReturn($this->mockUser());
        $userToken
            ->method('getToken')
            ->willReturn($this->mockToken($tokenName));

        return $userToken;
    }

    private function mockTokenManager(): TokenManagerInterface
    {
        $manager = $this->createMock(TokenManagerInterface::class);
        $manager->method('getRealBalance')
            ->willReturn($this->mockBalanceResult());

        return $manager;
    }

    private function mockToken(string $name): Token
    {
        $token = $this->createMock(Token::class);
        $token->method('getName')->willReturn($name);
        $token->method('getImage')->willReturn($this->mockImage());
        $token->method('getCryptoSymbol')->willReturn('symbol');
        $token->method('getDecimals')->willReturn(10);
        $token->method('getDeployed')->willReturn(true);
        $token->method('isCreatedOnMintmeSite')->willReturn(true);
        $token->method('getCrypto')->willReturn($this->mockCrypto());

        return $token;
    }

    private function mockImage(): Image
    {
        return $this->createMock(Image::class);
    }

    private function mockCrypto(): Crypto
    {
        return $this->createMock(Crypto::class);
    }

    private function mockUser(): User
    {
        return $this->createMock(User::class);
    }

    private function mockBalanceResult(?Int $available = null): BalanceResult
    {
        $balanceResult = $this->createMock(BalanceResult::class);
        $balanceResult->method('getAvailable')->willReturn($this->dummyMoneyObject($available));

        return $balanceResult;
    }

    private function dummyMoneyObject(?int $available = null): Money
    {
        return new Money($available ?? 1, new Currency('TOK'));
    }

    private function mockTokensArrayManager(array $tokens): TokenManagerInterface
    {
        $tm = $this->createMock(TokenManagerInterface::class);

        $tm->method('getRealBalance')->willReturnCallback(function (Token $token) use ($tokens): BalanceResult {
            foreach ($tokens as $item) {
                if ($token->getName() === $item['name']) {
                    return $this->mockBalanceResult($item['available']);
                }
            }

            return $this->mockBalanceResult();
        });

        return $tm;
    }
}
