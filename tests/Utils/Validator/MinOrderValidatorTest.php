<?php declare(strict_types = 1);

namespace App\Tests\Utils\Validator;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Utils\Validator\MinOrderValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MinOrderValidatorTest extends TestCase
{
    /** @dataProvider orderProvider */
    public function testValid(
        string $price,
        string $amount,
        bool $isToken,
        int $basSubunit,
        int $quoteSubunit,
        bool $result
    ): void {
        $base = $this->mockTradable($basSubunit, false);
        $quote = $this->mockTradable($quoteSubunit, $isToken);

        $minOrderValidator =  new MinOrderValidator($base, $quote, $price, $amount);
        $this->assertEquals($result, $minOrderValidator->validate());
    }

    public function orderProvider(): array
    {
        return [
            ['1', '1', false, 8, 4, true],
            ['.001', '.001', false, 8, 4, true],
            ['.001', '.001', false, 4, 8, false],
            ['.01', '.01', false, 4, 8, true],
            ['.00001', '1', false, 8, 4, true],
            ['.00001', '1', false, 4, 8, false],
            ['.0001', '1', false, 4, 8, true],
            ['.00000001', true,false, 8, 4, true],
            ['.00000001', '1', false, 4, 8, false],
            ['.000000001', '1', false, 8, 4, false],
            ['1', '.0001', false, 8, 4, true],
            ['1', '.00001', false, 8, 4, false],
            ['1', '.00001', false, 4, 8, false],
            ['.00001', '.0001', false, 8, 4, false],
            ['.001', '.001', true, 8, 4, true],
            ['.00001', '.0001', true, 8, 4, false],
        ];
    }


    /** @return MockObject|TradebleInterface */
    private function mockTradable(int $subunit, bool $isToken): TradebleInterface
    {
        $cryptoMock = $this->createMock(Crypto::class);
        $cryptoMock->method('getShowSubunit')->willReturn($subunit);

        return $isToken
            ? $this->mockToken($cryptoMock)
            : $cryptoMock;
    }

    /** @return MockObject|Token */
    private function mockToken(Crypto $crypto): TradebleInterface
    {
        $tokenMock = $this->createMock(Token::class);
        $tokenMock->method('getCrypto')->willReturn($crypto);

        return $tokenMock;
    }
}
