<?php declare(strict_types = 1);

namespace App\Tests\Validator\Constraints;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Utils\Validator\MinOrderValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MinOrderValidatorTest extends TestCase
{
    /** @dataProvider orderProvider */
    public function testValid(string $price, string $amount, bool $result): void
    {
        $base = $this->mockCrypto('BTC', 8);
        $quote = $this->mockCrypto('WEB', 4);

        $baseTradable = $this->mockToken($base);
        $quoteTradable = $this->mockToken($quote);

        $minOrderValidator =  new MinOrderValidator($baseTradable, $quoteTradable, $price, $amount);
        $this->assertEquals(
            $result,
            $minOrderValidator->validate()
        );
    }

    public function orderProvider(): array
    {
        return [
            ['1', '1', true],
            ['.001', '.001', true],
            ['.00001', '1', true],
            ['.000000001', '1', false],
            ['1', '.0001', true],
            ['1', '.00001', false],
            ['.00001', '.0001', false],
        ];
    }


    /** @return MockObject|Crypto */
    private function mockCrypto(string $symbol, int $subunit): Crypto
    {
        $cryptoMock = $this->createMock(Crypto::class);
        $cryptoMock->method('getShowSubunit')->willReturn($subunit);

        return $cryptoMock;
    }

    /** @return MockObject|Token */
    private function mockToken(Crypto $crypto): TradebleInterface
    {
        $tokenMock = $this->createMock(Token::class);
        $tokenMock->method('getCrypto')->willReturn($crypto);

        return $tokenMock;
    }
}
