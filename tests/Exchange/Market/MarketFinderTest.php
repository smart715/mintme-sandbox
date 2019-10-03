<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Market;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketFinder;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use PHPUnit\Framework\TestCase;

class MarketFinderTest extends TestCase
{
    public function testFindNull(): void
    {
        $market = $this->createMock(Market::class);
        $finder = new MarketFinder(
            $this->mockCryptoManager(null),
            $this->mockTokenManager(null),
            $this->mockMarketFactory($market)
        );

        $this->assertNull($finder->find('foo', 'bar'));
    }

    public function testFindSame(): void
    {
        $market = $this->createMock(Market::class);
        $finder = new MarketFinder(
            $this->mockCryptoManager($this->createMock(Crypto::class)),
            $this->mockTokenManager(null),
            $this->mockMarketFactory($market)
        );

        $this->assertNull($finder->find('foo', 'bar'));
    }

    public function testFindSuccess(): void
    {
        $market = $this->createMock(Market::class);
        $finder = new MarketFinder(
            $this->mockCryptoManager($this->createMock(Crypto::class)),
            $this->mockTokenManager($this->createMock(Token::class)),
            $this->mockMarketFactory($market)
        );

        $this->assertEquals($market, $finder->find('foo', 'bar'));
    }

    public function testFindSuccessSameStorage(): void
    {
        $cryptoManager = $this->createMock(CryptoManagerInterface::class);
        $cryptoManager->expects($this->at(0))->method('findBySymbol')->willReturn($this->createMock(Crypto::class));
        $cryptoManager->expects($this->at(1))->method('findBySymbol')->willReturn($this->createMock(Crypto::class));
        $market = $this->createMock(Market::class);
        $finder = new MarketFinder(
            $cryptoManager,
            $this->mockTokenManager(null),
            $this->mockMarketFactory($market)
        );

        $this->assertEquals($market, $finder->find('foo', 'bar'));
    }

    private function mockTokenManager(?Token $token): TokenManagerInterface
    {
        $manager = $this->createMock(TokenManagerInterface::class);
        $manager->method('findByName')->willReturn($token);

        return $manager;
    }

    private function mockCryptoManager(?Crypto $crypto): CryptoManagerInterface
    {
        $manager = $this->createMock(CryptoManagerInterface::class);
        $manager->expects($this->at(0))->method('findBySymbol')->willReturn($crypto);

        return $manager;
    }

    private function mockMarketFactory(Market $market): MarketFactoryInterface
    {
        $factory = $this->createMock(MarketFactoryInterface::class);
        $factory->method('create')->willReturn($market);

        return $factory;
    }
}
