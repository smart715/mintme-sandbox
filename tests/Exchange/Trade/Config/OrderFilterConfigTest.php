<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Trade\Config;

use App\Exchange\Trade\Config\OrderFilterConfig;
use PHPUnit\Framework\TestCase;

class OrderFilterConfigTest extends TestCase
{
    public function testConfig(): void
    {
        $config = new OrderFilterConfig();

        $config->merge([
            'foo' => null,
            'bar' => 1,
            'baz' => 2,
            'offset' => 3,
        ]);

        $this->assertEquals(null, $config->offsetGet('foo'));
        $this->assertEquals(1, $config->offsetGet('bar'));
        $this->assertEquals(null, $config->offsetGet('qux'));
        $this->assertEquals(3, $config->offsetGet('offset'));

        $config->offsetUnset('bar');

        $this->assertEquals(null, $config->offsetGet('bar'));
        $this->assertTrue($config->offsetExists('baz'));
        $this->assertFalse($config->offsetExists('bar'));

        $config->offsetSet('bar', 3);

        $this->assertTrue($config->offsetExists('bar'));
        $this->assertEquals(3, $config->offsetExists('bar'));

        $config[] = 123;
        $this->assertEquals(123, $config[0]);
    }
}
