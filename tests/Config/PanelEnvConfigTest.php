<?php declare(strict_types = 1);

namespace App\Tests\Config;

use App\Config\PanelEnvConfig;
use PHPUnit\Framework\TestCase;

class PanelEnvConfigTest extends TestCase
{
    public function testIsDev(): void
    {
        $panelEnvConfig = new PanelEnvConfig('dev');

        $this->assertTrue($panelEnvConfig->isDev());
    }

    public function testIsProd(): void
    {
        $panelEnvConfig = new PanelEnvConfig('prod');

        $this->assertTrue($panelEnvConfig->isProd());
    }
}
