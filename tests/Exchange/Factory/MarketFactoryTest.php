<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Factory;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TokenCrypto;
use App\Entity\User;
use App\Exchange\Config\MarketPairsConfig;
use App\Exchange\Factory\MarketFactory;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;

class MarketFactoryTest extends TestCase
{
    public function testCreateUserRelated(): void
    {
        $crypto = $this->mockCrypto('WEB', true, true);

        $tokens = [
            $this->mockToken(123, 'tok1', $crypto),
            $this->mockToken(456, 'tok2', $crypto),
            $this->mockToken(789, 'tok3', $crypto),
        ];

        $cryptoManager = $this->mockCryptoManager([$crypto]);
        $tokenManager = $this->mockTokenManager([], $this->never());
        $marketPairsConfig = $this->mockMarketPairsConfig();

        $marketManager = new MarketFactory($cryptoManager, $tokenManager, $marketPairsConfig);
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
        $crypto = $this->mockCrypto('WEB', true, true);

        $tokens = [
            $this->mockToken(123, 'tok1', $crypto),
            $this->mockToken(456, 'tok2', $crypto),
            $this->mockToken(789, 'tok3', $crypto),
        ];

        $cryptoManager = $this->mockCryptoManager([$crypto]);
        $tokenManager = $this->mockTokenManager($tokens, $this->exactly(1));
        $marketPairsConfig = $this->mockMarketPairsConfig();

        $marketManager = new MarketFactory($cryptoManager, $tokenManager, $marketPairsConfig);
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
        $tokens = [$this->mockToken(123, 'tok1')];

        $cryptoManager = $this->mockCryptoManager(
            [$this->mockCrypto('WEB', false)],
            $this->exactly(2)
        );
        $tokenManager = $this->mockTokenManager($tokens, $this->exactly(1));
        $marketPairsConfig = $this->mockMarketPairsConfig();

        $marketManager = new MarketFactory($cryptoManager, $tokenManager, $marketPairsConfig);
        $markets = $marketManager->createAll();

        $this->assertEmpty($markets);
    }

    public function testGetCoinMarketsWhenTradableMarketsEmpty(): void
    {
        $tokens = [$this->mockToken(123, 'tok1'),];

        $cryptoManager = $this->mockCryptoManager(
            [$this->mockCrypto('WEB', true, true)],
            $this->exactly(1)
        );
        $tokenManager = $this->mockTokenManager($tokens, $this->never());
        $marketPairsConfig = $this->mockMarketPairsConfig();

        $marketManager = new MarketFactory($cryptoManager, $tokenManager, $marketPairsConfig);
        $markets = $marketManager->getCoinMarkets();

        $this->assertEmpty($markets);
    }

    public function testGetCoinMarketsWhenConfigPairsEmpty(): void
    {
        $tokens = [$this->mockToken(123, 'tok1'),];

        $cryptoManager = $this->mockCryptoManager(
            [$this->mockCrypto('WEB', true, true), $this->mockCrypto('BTC', true, true)],
            $this->exactly(1)
        );
        $tokenManager = $this->mockTokenManager($tokens, $this->never());
        $marketPairsConfig = $this->mockMarketPairsConfig([]);

        $marketManager = new MarketFactory($cryptoManager, $tokenManager, $marketPairsConfig);
        $markets = $marketManager->getCoinMarkets();

        $this->assertEmpty($markets);
    }

    public function testGetCoinMarkets(): void
    {
        $tokens = [$this->mockToken(123, 'tok1'),];

        $cryptoManager = $this->mockCryptoManager(
            [$this->mockCrypto('WEB', true, true), $this->mockCrypto('BTC', true, true)],
            $this->exactly(1)
        );
        $tokenManager = $this->mockTokenManager($tokens, $this->never());
        $marketPairsConfig = $this->mockMarketPairsConfig();

        $marketManager = new MarketFactory($cryptoManager, $tokenManager, $marketPairsConfig);
        $markets = $marketManager->getCoinMarkets();

        $this->assertEquals(
            ['WEB', 'BTC'],
            [$markets[0]->getQuote()->getSymbol(), $markets[0]->getBase()->getSymbol()],
        );
    }

    public function testGetCoinMarketsCustomPairs(): void
    {
        $tokens = [$this->mockToken(123, 'tok1'),];

        $cryptoManager = $this->mockCryptoManager(
            [$this->mockCrypto('BTC', true, true), $this->mockCrypto('USDC', true, true)],
            $this->exactly(1)
        );
        $tokenManager = $this->mockTokenManager($tokens, $this->never());
        $marketPairsConfig = $this->mockMarketPairsConfig();

        $marketManager = new MarketFactory($cryptoManager, $tokenManager, $marketPairsConfig);
        $markets = $marketManager->getCoinMarkets();

        $this->assertEquals(
            ['BTC', 'USDC'],
            [$markets[0]->getQuote()->getSymbol(), $markets[0]->getBase()->getSymbol()],
        );
    }

    private function mockUser(array $toks): User
    {
        $user = $this->createMock(User::class);
        $user->method('getTokens')->willReturn($toks);

        return $user;
    }

    /** @return MockObject|CryptoManagerInterface */
    private function mockCryptoManager(array $cryptos, ?InvokedCount $invocatio = null): CryptoManagerInterface
    {
        $cryptoManagerMock = $this->createMock(CryptoManagerInterface::class);
        $cryptoManagerMock
            ->expects($invocatio ?:$this->exactly(2))
            ->method('findAll')
            ->willReturn($cryptos)
        ;

        return $cryptoManagerMock;
    }

    /**
     * @return MockObject|TokenManagerInterface
     */
    private function mockTokenManager(array $tokens, InvokedCount $invocation): TokenManagerInterface
    {
        $tokenManagerMock = $this->createMock(TokenManagerInterface::class);
        $tokenManagerMock
            ->expects($invocation)
            ->method('findAll')
            ->willReturn($tokens)
        ;

        return $tokenManagerMock;
    }

    private function mockMarketPairsConfig(?array $pairs = null): MarketPairsConfig
    {
        $marketPairsConfigMock = $this->createMock(MarketPairsConfig::class);
        $marketPairsConfigMock
            ->method('getParsedEnabledPairs')
            ->willReturn($pairs ?? [
                ['quote' => 'WEB', 'base' => 'BTC'],
                ['quote' => 'WEB', 'base' => 'ETH'],
                ['quote' => 'WEB', 'base' => 'BNB'],
                ['quote' => 'WEB', 'base' => 'USDC'],
                ['quote' => 'WEB', 'base' => 'CRO'],
                ['quote' => 'BTC', 'base' => 'USDC'],
            ])
        ;
        $marketPairsConfigMock
            ->method('getJoinedTopListPairs')
            ->willReturn(['WEBBTC', 'WEBETH', 'WEBBNB', 'WEBUSDC', 'WEBCRO', 'BTCUSDC'])
        ;

        return $marketPairsConfigMock;
    }

    /** @return MockObject|Crypto */
    private function mockCrypto(string $symbol, bool $exchangable, bool $tradable = false): Crypto
    {
        $cryptoMock = $this->createMock(Crypto::class);

        $cryptoMock->method('getSymbol')->willReturn($symbol);
        $cryptoMock->method('isExchangeble')->willReturn($exchangable);
        $cryptoMock->method('isTradable')->willReturn($tradable);

        return $cryptoMock;
    }

    /** @return MockObject|Token */
    private function mockToken(int $id, string $name, ?Crypto $crypto = null): Token
    {
        $tokenMock = $this->createMock(Token::class);

        $tokenMock->method('getId')->willReturn($id);
        $tokenMock->method('getSymbol')->willReturn($name);
        $tokenMock->method('getCryptoSymbol')->willReturn('WEB');
        $tokenCrypto = $this->createMock(TokenCrypto::class);
        $tokenCrypto->method('getCrypto')->willReturn($crypto ?? $this->mockCrypto('TEST', true));
        $collection = $this->createMock(Collection::class);
        $collection->method('toArray')->willReturn([$tokenCrypto]);
        $tokenMock->method('getExchangeCryptos')->willReturn($collection);

        return $tokenMock;
    }
}
