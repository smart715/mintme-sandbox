<?php declare(strict_types = 1);

namespace App\Tests\Utils\Converter;

use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Exchange\Market;
use App\Utils\Converter\MarketNameConverter;
use App\Utils\Converter\TokenNameConverter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MarketNameConverterTest extends TestCase
{
    /**
     * @var string $base
     * @var string $quote
     * @var bool $isToken
     * @var string $marketName
     * @dataProvider convertProvider
     */
    public function testConvert(string $base, string $quote, bool $isToken, string $marketName): void
    {
        $tokenNameConverter = $this->mockTokenNameConverter();

        $market = $this->mockMarket(
            $this->mockTradeble($base, false),
            $this->mockTradeble($quote, $isToken)
        );

        $converter = new MarketNameConverter($tokenNameConverter);

        $this->assertEquals($converter->convert($market), $marketName);
    }

    public function convertProvider(): array
    {
        return [
            ['BTC', 'WEB', false, 'WEBBTC'],
            ['WEB', 'TOK', true, 'FOOWEB'],
            ['btc', 'web', false, 'WEBBTC'],
            ['web', 'TOK', true, 'FOOWEB'],
        ];
    }

    /**
     * @return MockObject|TokenNameConverter
     */
    private function mockTokenNameConverter(): TokenNameConverter
    {
        $tokenNameConverter = $this->createMock(TokenNameConverter::class);
        $tokenNameConverter->method('convert')->willReturn('FOO');

        return $tokenNameConverter;
    }

    /**
     * @var MockObject|TradebleInterface $base
     * @var MockObject|TradebleInterface $quote
     * @return MockObject|Market
     */
    private function mockMarket(TradebleInterface $base, TradebleInterface $quote): Market
    {
        $market = $this->createMock(Market::class);
        $market->method('getQuote')->willReturn($quote);
        $market->method('getBase')->willReturn($base);

        return $market;
    }

    /**
     * @param string $symbol
     * @param bool $isToken
     * @return MockObject|TradebleInterface
     */
    private function mockTradeble(string $symbol, bool $isToken): TradebleInterface
    {
        $tradeble = $isToken
            ? $this->createMock(Token::class)
            : $this->createMock(TradebleInterface::class);

        $tradeble->method('getSymbol')->willReturn($symbol);

        return $tradeble;
    }
}
