<?php declare(strict_types = 1);

namespace App\Tests\Communications\SMS\Config;

use App\Communications\SMS\Config\SmsConfig;
use PHPUnit\Framework\TestCase;

class SmsConfigTest extends TestCase
{
    private SmsConfig $config;

    protected function setUp(): void
    {
        $smsProviders = [
            'test1' => ['name' => 'test1', 'enabled' => true, 'priority' => 1, 'retry' => 1],
            'test2' => ['name' => 'test2', 'enabled' => true, 'priority' => 2, 'retry' => 2],
            'test3' => ['name' => 'test3', 'enabled' => false, 'priority' => 3, 'retry' => 3],
        ];

        $this->config = new SmsConfig($smsProviders, false);
    }

    public function testGetProviders(): void
    {
        $this->assertEquals(
            [
                'test1' => ['name' => 'test1', 'enabled' => true, 'priority' => 1, 'retry' => 1],
                'test2' => ['name' => 'test2', 'enabled' => true, 'priority' => 2, 'retry' => 2],
            ],
            $this->config->getProviders()
        );
    }

    public function testHasProviders(): void
    {
        $this->assertTrue($this->config->hasProviders());
    }

    public function testIsSmsDisabled(): void
    {
        $this->assertFalse($this->config->isSmsDisabled());
    }
}
