<?php declare(strict_types = 1);

namespace App\Tests\Config;

use App\Config\HideFeaturesConfig;
use App\Utils\Symbols;
use PHPUnit\Framework\TestCase;

class HideFeaturesConfigTest extends TestCase
{
    private const ENABLED_CRYPTOS = [
        Symbols::WEB => true,
        Symbols::ETH => false,
    ];

    public function testIsCryptoEnabled(): void
    {
        $config = new HideFeaturesConfig(
            true,
            true,
            true,
            self::ENABLED_CRYPTOS
        );

        $this->assertTrue($config->isCryptoEnabled(Symbols::WEB));
        $this->assertTrue($config->isCryptoEnabled(Symbols::MINTME));
        $this->assertFalse($config->isCryptoEnabled(Symbols::ETH));
        $this->assertFalse($config->isCryptoEnabled(Symbols::BTC));
    }

    public function testGetAllEnabledCryptos(): void
    {
        $result = [Symbols::WEB];

        $config = new HideFeaturesConfig(
            true,
            true,
            true,
            self::ENABLED_CRYPTOS
        );

        $this->assertEquals($result, $config->getAllEnabledCryptos());
    }
}
