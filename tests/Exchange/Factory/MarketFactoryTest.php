<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Factory;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Factory\MarketFactory;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use PHPUnit\Framework\MockObject\Matcher\Invocation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MarketFactoryTest extends TestCase
{
    public function testCreateUserRelated(): void
    {
        $crypto = $this->mockCrypto('WEB', true);

        $tokens = [
            $this->mockToken(123, 'tok1'),
            $this->mockToken(456, 'tok2'),
            $this->mockToken(789, 'tok3'),
            $crypto,
        ];

        $cryptoManager = $this->mockCryptoManager($crypto);
        $tokenManager = $this->mockTokenManager([], $this->never());

        /** @var MarketFactoryInterface $marketManager */
        $marketManager = new MarketFactory($cryptoManager, $tokenManager);
        $markets = $marketManager->createUserRelated(
            $this->mockUser($tokens)
        );

        $this->assertCount(3, $markets);
        $this->assertEquals([['tok1', 'WEB'], ['tok2', 'WEB'], ['tok3', 'WEB']], [
            [$markets[0]->getQuote()->getSymbol(), $markets[0]->getBase()->getSymbol()],
            [$markets[1]->getQuote()->getSymbol(), $markets[1]->getBase()->getSymbol()],
            [$markets[2]->getQuote()->getSymbol(), $markets[2]->getBase()->getSymbol()],
        ]);
    }

    public function testGetAllMarkets(): void
    {
        $crypto = $this->mockCrypto('WEB', true);

        $tokens = [
            $this->mockToken(123, 'tok1'),
            $this->mockToken(456, 'tok2'),
            $this->mockToken(789, 'tok3'),
            $crypto,
        ];

        $cryptoManager = $this->mockCryptoManager($crypto);
        $tokenManager = $this->mockTokenManager($tokens, $this->exactly(1));

        /** @var MarketFactoryInterface $marketManager */
        $marketManager = new MarketFactory($cryptoManager, $tokenManager);
        $markets = $marketManager->createAll();

        $this->assertCount(3, $markets);
        $this->assertEquals([['tok1', 'WEB'], ['tok2', 'WEB'], ['tok3', 'WEB']], [
            [$markets[0]->getQuote()->getSymbol(), $markets[0]->getBase()->getSymbol()],
            [$markets[1]->getQuote()->getSymbol(), $markets[1]->getBase()->getSymbol()],
            [$markets[2]->getQuote()->getSymbol(), $markets[2]->getBase()->getSymbol()],
        ]);
    }

    public function testGetAllMarketsWithoutExchangableCryptos(): void
    {
        $tokens = [$this->mockToken(123, 'tok1'),];

        $cryptoManager = $this->mockCryptoManager($this->mockCrypto('WEB', false));
        $tokenManager = $this->mockTokenManager($tokens, $this->exactly(1));

        /** @var MarketFactoryInterface $marketManager */
        $marketManager = new MarketFactory($cryptoManager, $tokenManager);
        $markets = $marketManager->createAll();

        $this->assertEmpty($markets);
    }

    private function mockUser(array $toks): User
    {
        $user = $this->createMock(User::class);
        $user->method('getTokens')->willReturn($toks);

        return $user;
    }

    /** @return MockObject|CryptoManagerInterface */
    private function mockCryptoManager(Crypto $crypto): CryptoManagerInterface
    {
        $cryptoManagerMock = $this->createMock(CryptoManagerInterface::class);
        $cryptoManagerMock
            ->expects($this->exactly(3))
            ->method('findAll')
            ->willReturn([$crypto])
        ;

        return $cryptoManagerMock;
    }

    /**
     * @return MockObject|TokenManagerInterface
     */
    private function mockTokenManager(array $tokens, Invocation $invocation): TokenManagerInterface
    {
        $tokenManagerMock = $this->createMock(TokenManagerInterface::class);
        $tokenManagerMock
            ->expects($invocation)
            ->method('findAll')
            ->willReturn($tokens)
        ;

        return $tokenManagerMock;
    }

    /** @return MockObject|Crypto */
    private function mockCrypto(string $symbol, bool $exchangable): Crypto
    {
        $cryptoMock = $this->createMock(Crypto::class);

        $cryptoMock->method('getSymbol')->willReturn($symbol);
        $cryptoMock->method('isExchangeble')->willReturn($exchangable);

        return $cryptoMock;
    }

    /** @return MockObject|Token */
    private function mockToken(int $id, string $name): Token
    {
        $tokenMock = $this->createMock(Token::class);

        $tokenMock->method('getId')->willReturn($id);
        $tokenMock->method('getSymbol')->willReturn($name);

        return $tokenMock;
    }
}
