<?php declare(strict_types = 1);

namespace App\Tests\Utils\Converter;

use App\Entity\Crypto;
use App\Entity\MarketStatus;
use App\Entity\Token\Token;
use App\Exchange\AbstractOrder;
use App\Exchange\Market;
use App\Exchange\MarketInfo;
use App\Utils\Converter\RebrandingConverter;
use PHPUnit\Framework\TestCase;

class RebrandingConverterTest extends TestCase
{
    private RebrandingConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new RebrandingConverter();
    }

    /**
     * @dataProvider convertProvider
     */
    public function testConvert(string $value, string $expected): void
    {
        $this->assertEquals(
            $expected,
            $this->converter->convert($value)
        );
    }

    public function convertProvider(): array
    {
        return [
            "Webchain will be replaced by MintMe Coin" => [
                "Webchain", "MintMe Coin",
            ],
            "webchain will be replaced by mintMe Coin" => [
                "webchain", "mintMe Coin",
            ],
            "WEB will be replaced by MINTME" => [
                "WEB", "MINTME",
            ],
            "web will be replaced by mintme" => [
                "web", "MINTME",
            ],
        ];
    }

    /**
     * @dataProvider reverseConvertProvider
     */
    public function testReverseConvert(string $value, string $expected): void
    {
        $this->assertEquals(
            $expected,
            $this->converter->reverseConvert($value)
        );
    }

    public function reverseConvertProvider(): array
    {
        return [
            '"MintMe" Coin will be replaced by "Webchain"' => [
                'MintMe Coin', 'Webchain',
            ],
            '"mintMe" Coin will be replaced by "webchain"' => [
                'mintMe Coin', 'webchain',
            ],
            '"MINTME" will be replaced by "WEB"' => [
                'MINTME', 'WEB',
            ],
            '"mintme" will be replaced by "WEB"' => [
                'mintme', 'WEB',
            ],
            '"1MINTME1" should not be replaced ' => [
                '1MINTME1', '1MINTME1',
            ],
            '"1MINTME" should not be replaced' => [
                '1MINTME', '1MINTME',
            ],
            '"1mintme1" should not be replaced' => [
                '1mintme1', '1mintme1',
            ],
            '"1mintme" should not be replaced' => [
                '1mintme', '1mintme',
            ],
            '"2 mintme 2" should be replaced by "2 WEB 2"' => [
                '2 mintme 2', '2 WEB 2',
            ],
            '"2 mintme 3" should be replaced by "2 WEB 3"' => [
                '2 mintme 3', '2 WEB 3',
            ],
            '"2 mintme" should be replaced by "2 WEB"' => [
                '2 mintme', '2 WEB',
            ],
        ];
    }

    public function testConvertMarketStatus(): void
    {
        $preConvert = "WEB";
        $postConvert = "MINTME";

        $market = $this->createMock(MarketStatus::class);
        $base = $this->createMock(Crypto::class);
        $market->expects($this->once())
            ->method('getCrypto')
            ->willReturn($base);

        $base
            ->method('getName')
            ->willReturnOnConsecutiveCalls($preConvert, $postConvert);
        $base->expects($this->once())->method('setName')->with($postConvert);

        $base
            ->method('getSymbol')
            ->willReturnOnConsecutiveCalls($preConvert, $postConvert);
        $base->expects($this->once())->method('setSymbol')->with($postConvert);

        $market->expects($this->once())
            ->method('setCrypto')
            ->with($base);

        $quote = new Token();
        $quote->setName($preConvert);
        $quote->setSymbol($preConvert);
        $market->expects($this->once())
            ->method('getQuote')
            ->willReturn($quote);

        $market->expects($this->once())
            ->method('setQuote')
            ->with($quote);

        $this->converter->convertMarketStatus($market);

        $this->assertEquals($postConvert, $base->getName());
        $this->assertEquals($postConvert, $base->getSymbol());

        $this->assertEquals($postConvert, $quote->getName());
        $this->assertEquals($postConvert, $quote->getSymbol());
    }

    public function testConvertMarketInfo(): void
    {
        $preConvert = "WEB";
        $postConvert = "MINTME";

        $market = $this->createMock(MarketInfo::class);
        $market->expects($this->once())
            ->method('getCryptoSymbol')
            ->willReturn($preConvert);

        $market->expects($this->once())
            ->method('setCryptoSymbol')
            ->with($postConvert);

        $market->expects($this->once())
            ->method('getTokenName')
            ->willReturn($preConvert);

        $market->expects($this->once())
            ->method('setTokenName')
            ->with($postConvert);

        $this->converter->convertMarketInfo($market);
    }

    public function testConvertOrder(): void
    {
        $preConvert = "WEB";
        $postConvert = "MINTME";

        $order = $this->createMock(AbstractOrder::class);

        $market = $this->createMock(Market::class);

        $order->expects($this->once())
            ->method('getMarket')
            ->willReturn($market);

        $base = $this->createMock(Crypto::class);
        $market->expects($this->once())
            ->method('getBase')
            ->willReturn($base);

        $base
            ->method('getName')
            ->willReturnOnConsecutiveCalls($preConvert, $postConvert);
        $base->expects($this->once())->method('setName')->with($postConvert);

        $base
            ->method('getSymbol')
            ->willReturnOnConsecutiveCalls($preConvert, $postConvert);
        $base->expects($this->once())->method('setSymbol')->with($postConvert);

        $market->expects($this->once())
            ->method('setBase')
            ->with($base);

        $quote = new Token();
        $quote->setName($preConvert);
        $quote->setSymbol($preConvert);
        $market->expects($this->once())
            ->method('getQuote')
            ->willReturn($quote);

        $market->expects($this->once())
            ->method('setQuote')
            ->with($quote);

        $this->converter->convertOrder($order);

        $this->assertEquals($postConvert, $base->getName());
        $this->assertEquals($postConvert, $base->getSymbol());

        $this->assertEquals($postConvert, $quote->getName());
        $this->assertEquals($postConvert, $quote->getSymbol());
    }
}
