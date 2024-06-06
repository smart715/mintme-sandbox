<?php declare(strict_types = 1);

namespace App\Tests\Utils\Converter;

use App\Utils\Converter\NetworkSymbolConverter;
use PHPUnit\Framework\TestCase;

class NetworkSymbolConverterTest extends TestCase
{
    private NetworkSymbolConverter $converter;

    protected function setUp(): void
    {
        $conversionMap = [
            "MINTME" => "WEB",
            "BSC" => "BNB",
            "POYGON" => "MATIC",
        ];

        $this->converter = new NetworkSymbolConverter($conversionMap);
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
            "MINTME will be replaced with WEB" => [
                "MINTME", "WEB",
            ],
            "BSC will be replaced with BNB" => [
                "BSC", "BNB",
            ],
            "POLYGON will be replaced with MATIC" => [
                "POYGON", "MATIC",
            ],
            "ETH will not be replaced" => [
                "ETH", "ETH",
            ],
        ];
    }
}
