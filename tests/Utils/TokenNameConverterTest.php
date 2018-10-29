<?php

namespace App\Tests\Utils;

use App\Entity\Token\Token;
use App\Utils\TokenNameConverter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TokenNameConverterTest extends TestCase
{
    /**
     * @dataProvider convertProvider
     */
    public function testConvert(int $tokenId, string $tokenName): void
    {
        $converter = new TokenNameConverter();

        $this->assertEquals($tokenName, $converter->convert($this->mockToken($tokenId)));
    }

    public function convertProvider(): array
    {
        return [
            [ 1, 'TOK000000000001' ],
            [ 123, 'TOK000000000123' ],
            [ 321, 'TOK000000000321' ],
            [ 99999999999999, 'TOK99999999999999' ],
            [ -1, 'TOK0000000000-1' ],
        ];
    }

    /**
     * @return Token|MockObject
     */
    private function mockToken(int $value): Token
    {
        $token = $this->createMock(Token::class);

        $token->method('getId')->willReturn($value);

        return $token;
    }
}
