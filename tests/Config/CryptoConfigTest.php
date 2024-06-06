<?php declare(strict_types = 1);

namespace App\Tests\Config;

use App\Config\CryptoConfig;
use App\Utils\Symbols;
use PHPUnit\Framework\TestCase;

class CryptoConfigTest extends TestCase
{
    public function testGetCryptoDefaultNetwork(): void
    {
        $networks = [
            Symbols::USDC => Symbols::ETH,
        ];

        $cryptoConfig = new CryptoConfig($networks);

        $this->assertEquals(Symbols::ETH, $cryptoConfig->getCryptoDefaultNetwork(Symbols::USDC));
        $this->assertNull($cryptoConfig->getCryptoDefaultNetwork(Symbols::WEB));
    }
}
