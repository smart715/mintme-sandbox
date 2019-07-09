<?php declare(strict_types = 1);

namespace App\Tests\Exchange\Trade\Config;

use App\Exchange\Trade\Config\PrelaunchConfig;
use App\Utils\DateTime;
use PHPUnit\Framework\TestCase;

class PrelaunchConfigTest extends TestCase
{
    public function testGetFinishDate(): void
    {
        $config = new PrelaunchConfig('2', 0.5, true, $this->mockDateTime(1));
        $this->assertEquals(1, $config->getFinishDate()->getTimestamp());

        $config = new PrelaunchConfig('1998-06-22 03:23:34', 0.5, true, $this->mockDateTime(1));
        $this->assertEquals(898485814, $config->getFinishDate()->getTimestamp());
    }

    public function testIsEnabled(): void
    {
        $config = new PrelaunchConfig('1998-06-22 03:23:34', 0.5, true, $this->mockDateTime(898485813));
        $this->assertTrue($config->isEnabled());

        $config = new PrelaunchConfig('1998-06-22 03:23:34', 0.5, true, $this->mockDateTime(898485815));
        $this->assertFalse($config->isEnabled());
    }

    public function testIsFinished(): void
    {
        $config = new PrelaunchConfig('1998-06-22 03:23:34', 0.5, false, $this->mockDateTime(898485813));
        $this->assertFalse($config->isFinished());

        $config = new PrelaunchConfig('1998-06-22 03:23:34', 0.5, true, $this->mockDateTime(898485813));
        $this->assertFalse($config->isFinished());
    }

    private function mockDateTime(int $value): DateTime
    {
        $dt = $this->createMock(DateTime::class);

        $date = new \DateTimeImmutable();
        $date = $date->setTimestamp($value);

        $dt->method('now')->willReturn($date);

        return $dt;
    }
}
