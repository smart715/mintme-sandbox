<?php

namespace App\Tests\Manager;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketManager;
use App\Manager\MarketManagerInterface;
use App\Manager\TokenManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MarketManagerTest extends TestCase
{
    public function testGetAllMarkets(): void
    {
        $tokens = [
            $this->mockToken(123, 'tok1'),
            $this->mockToken(456, 'tok2'),
            $this->mockToken(789, 'tok3'),
        ];

        $cryptoManager = $this->mockCryptoManager($this->mockCrypto('WEB'));
        $tokenManager = $this->mockTokenManager($tokens);

        /** @var MarketManagerInterface $marketManager */
        $marketManager = new MarketManager($cryptoManager, $tokenManager);
        $markets = $marketManager->getAllMarkets();

        $this->assertCount(3, $markets);
        $this->assertEquals([
            ['tok1', 'WEB'],
            ['tok2', 'WEB'],
            ['tok3', 'WEB'],
        ], [
            [$markets[0]->getToken()->getName(), $markets[0]->getCurrencySymbol()],
            [$markets[1]->getToken()->getName(), $markets[1]->getCurrencySymbol()],
            [$markets[2]->getToken()->getName(), $markets[2]->getCurrencySymbol()],
        ]);
    }

    /** @return MockObject|CryptoManagerInterface */
    private function mockCryptoManager(Crypto $crypto): CryptoManagerInterface
    {
        $cryptoManagerMock = $this->createMock(CryptoManagerInterface::class);
        $cryptoManagerMock
            ->expects($this->once())
            ->method('findBySymbol')
            ->willReturn($crypto)
        ;
        return $cryptoManagerMock;
    }

    /**
     * @param Token[] $tokens
     * @return MockObject|TokenManagerInterface
     */
    private function mockTokenManager(array $tokens): TokenManagerInterface
    {
        $tokenManagerMock = $this->createMock(TokenManagerInterface::class);
        $tokenManagerMock
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($tokens)
        ;
        return $tokenManagerMock;
    }

    /** @return MockObject|Crypto */
    private function mockCrypto(string $symbol): Crypto
    {
        $cryptoMock = $this->createMock(Crypto::class);
        $cryptoMock
            ->method('getSymbol')
            ->willReturn($symbol)
        ;
        return $cryptoMock;
    }

    /** @return MockObject|Token */
    private function mockToken(int $id, string $name): Token
    {
        $tokenMock = $this->createMock(Token::class);
        $tokenMock
            ->method('getId')
            ->willReturn($id)
        ;
        $tokenMock
            ->method('getName')
            ->willReturn($name)
        ;
        return $tokenMock;
    }
}
