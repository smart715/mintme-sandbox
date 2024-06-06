<?php declare(strict_types = 1);

namespace App\Tests\Utils\Converter;

use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Exchange\Market;
use App\Utils\Converter\MarketNameConverter;
use App\Utils\Converter\TokenNameConverter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MarketNameConverterTest extends TestCase
{
    /**
     * @param string $base
     * @param string $quote
     * @param bool $isToken
     * @param string $marketName
     * @dataProvider convertProvider
     */
    public function testConvert(string $base, string $quote, bool $isToken, string $marketName): void
    {
        $tokenNameConverter = $this->mockTokenNameConverter();

        $market = $this->mockMarket(
            $this->mockTradable($base, false),
            $this->mockTradable($quote, $isToken)
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
     * @param MockObject|TradableInterface $base
     * @param MockObject|TradableInterface $quote
     * @return MockObject|Market
     */
    private function mockMarket(TradableInterface $base, TradableInterface $quote): Market
    {
        $market = $this->createMock(Market::class);
        $market->method('getQuote')->willReturn($quote);
        $market->method('getBase')->willReturn($base);

        return $market;
    }

    /**
     * @param string $symbol
     * @param bool $isToken
     * @return MockObject|TradableInterface
     */
    private function mockTradable(string $symbol, bool $isToken): TradableInterface
    {
        $tradable = $isToken
            ? $this->createMock(Token::class)
            : $this->createMock(TradableInterface::class);

        $tradable->method('getSymbol')->willReturn($symbol);

        return $tradable;
    }
}
