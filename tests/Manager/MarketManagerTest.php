<?php

namespace App\Tests\Manager;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Exchange\Market;
use App\Manager\CryptoManager;
use App\Manager\MarketManager;
use App\Manager\TokenManager;
use PHPUnit\Framework\TestCase;

class MarketManagerTest extends TestCase
{
    public function testGetAllMarkets(): void
    {
        $cryptos = [
            $this->mockCrypto('WEB'),
            $this->mockCrypto('BTC'),
        ];
        $tokens = [
            $this->mockToken(123, 'tok1'),
            $this->mockToken(456, 'tok2'),
            $this->mockToken(789, 'tok3'),
        ];

        $cryptoManager = $this->mockCryptoManager($cryptos);
        $tokenManager = $this->mockTokenManager($tokens);

        $marketManager = new MarketManager($cryptoManager, $tokenManager);
        $markets = $marketManager->getAllMarkets();

        $this->assertEquals(6, count($markets));

        $this->assertEquals(
            [
                'TOK000000000123WEB' => ['tok1', 'WEB'],
                'TOK000000000456WEB' => ['tok2', 'WEB'],
                'TOK000000000789WEB' => ['tok3', 'WEB'],
                'TOK000000000123BTC' => ['tok1', 'BTC'],
                'TOK000000000456BTC' => ['tok2', 'BTC'],
                'TOK000000000789BTC' => ['tok3', 'BTC'],
            ],
            [
                $markets[0]->getHiddenName() => [$markets[0]->getTokenName(), $markets[0]->getCurrencySymbol()],
                $markets[1]->getHiddenName() => [$markets[1]->getTokenName(), $markets[1]->getCurrencySymbol()],
                $markets[2]->getHiddenName() => [$markets[2]->getTokenName(), $markets[2]->getCurrencySymbol()],
                $markets[3]->getHiddenName() => [$markets[3]->getTokenName(), $markets[3]->getCurrencySymbol()],
                $markets[4]->getHiddenName() => [$markets[4]->getTokenName(), $markets[4]->getCurrencySymbol()],
                $markets[5]->getHiddenName() => [$markets[5]->getTokenName(), $markets[5]->getCurrencySymbol()],
            ]
        );
    }

    private function mockCryptoManager(array $cryptos): CryptoManager
    {
        $cryptoManagerMock = $this->createMock(CryptoManager::class);
        $cryptoManagerMock
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($cryptos)
        ;
        return $cryptoManagerMock;
    }

    private function mockTokenManager(array $tokens): TokenManager
    {
        $tokenManagerMock = $this->createMock(TokenManager::class);
        $tokenManagerMock
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($tokens)
        ;
        return $tokenManagerMock;
    }

    private function mockCrypto(string $symbol): Crypto
    {
        $cryptoMock = $this->createMock(Crypto::class);
        $cryptoMock
            ->method('getSymbol')
            ->willReturn($symbol)
        ;
        return $cryptoMock;
    }

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
