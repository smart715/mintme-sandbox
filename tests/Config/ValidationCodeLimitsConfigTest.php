<?php declare(strict_types = 1);

namespace App\Tests\Config;

use App\Config\ValidationCodeLimitsConfig;
use App\Exception\ValidationCodeConfigException;
use PHPUnit\Framework\TestCase;

class ValidationCodeLimitsConfigTest extends TestCase
{
    private const CONFIGS = [
        'failed' => 5,
        'daily' => 3,
        'weekly' => 10,
        'monthly' => 20,
        'overall' => 50,
    ];

    public function testGetOverall(): void
    {
        $config = new ValidationCodeLimitsConfig(self::CONFIGS);

        $this->assertEquals(self::CONFIGS['overall'], $config->getOverall());
    }

    public function testGetOverallException(): void
    {
        $this->expectException(ValidationCodeConfigException::class);
        $this->expectExceptionMessage('overall key does not exist');

        $props = self::CONFIGS;
        unset($props['overall']);

        $config = new ValidationCodeLimitsConfig($props);

        $config->getOverall();
    }

    public function testGetDaily(): void
    {
        $config = new ValidationCodeLimitsConfig(self::CONFIGS);

        $this->assertEquals(self::CONFIGS['daily'], $config->getDaily());
    }

    public function testGetDailyException(): void
    {
        $this->expectException(ValidationCodeConfigException::class);
        $this->expectExceptionMessage('daily key does not exist');

        $props = self::CONFIGS;
        unset($props['daily']);

        $config = new ValidationCodeLimitsConfig($props);

        $config->getDaily();
    }

    public function testGetWeekly(): void
    {
        $config = new ValidationCodeLimitsConfig(self::CONFIGS);

        $this->assertEquals(self::CONFIGS['weekly'], $config->getWeekly());
    }

    public function testGetWeeklyException(): void
    {
        $this->expectException(ValidationCodeConfigException::class);
        $this->expectExceptionMessage('weekly key does not exist');

        $props = self::CONFIGS;
        unset($props['weekly']);

        $config = new ValidationCodeLimitsConfig($props);

        $config->getWeekly();
    }

    public function testGetMonthly(): void
    {
        $config = new ValidationCodeLimitsConfig(self::CONFIGS);

        $this->assertEquals(self::CONFIGS['monthly'], $config->getMonthly());
    }

    public function testGetMonthlyException(): void
    {
        $this->expectException(ValidationCodeConfigException::class);
        $this->expectExceptionMessage('monthly key does not exist');

        $props = self::CONFIGS;
        unset($props['monthly']);

        $config = new ValidationCodeLimitsConfig($props);

        $config->getMonthly();
    }

    public function testGetFailed(): void
    {
        $config = new ValidationCodeLimitsConfig(self::CONFIGS);

        $this->assertEquals(self::CONFIGS['failed'], $config->getFailed());
    }

    public function testGetFailedException(): void
    {
        $this->expectException(ValidationCodeConfigException::class);
        $this->expectExceptionMessage('failed key does not exist');

        $props = self::CONFIGS;
        unset($props['failed']);

        $config = new ValidationCodeLimitsConfig($props);

        $config->getFailed();
    }
}
