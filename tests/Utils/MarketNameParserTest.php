<?php declare(strict_types = 1);

namespace App\Tests\Utils;

use App\Utils\MarketNameParser;
use PHPUnit\Framework\TestCase;

class MarketNameParserTest extends TestCase
{
    /**
     * @dataProvider parserProvider
     */
    public function testParser(string $symbol, string $name, string $tokenName): void
    {
        $parser = new MarketNameParser();

        $this->assertEquals($symbol, $parser->parseSymbol($tokenName));
        $this->assertEquals($name, $parser->parseName($tokenName));
    }

    public function parserProvider(): array
    {
        return [
            ['WEB', 'TOK000000000001', 'TOK000000000001WEB'],
            ['WEB', 'TOK000000000002', 'TOK000000000002WEB'],
            ['WEB', 'TOK000000000003', 'TOK000000000003WEB'],
            ['WEB', 'TOK000000000004', 'TOK000000000004WEB'],
            ['WEB', 'TOK000000000005', 'TOK000000000005WEB'],
        ];
    }
}
