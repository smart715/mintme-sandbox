<?php declare(strict_types = 1);

namespace App\Tests\Config;

use App\Config\ValidationCodeConfigs;
use App\Config\ValidationCodeLimitsConfig;
use App\Exception\ValidationCodeConfigException;
use PHPUnit\Framework\TestCase;

class ValidationCodeConfigsTest extends TestCase
{
    private const CONFIGS = [
        'sms_limits' => [
            'failed' => 5,
            'daily' => 3,
            'weekly' => 10,
            'monthly' => 20,
            'overall' => 50,
        ],
        'email_limits' => [
            'failed' => 5,
            'daily' => 3,
            'weekly' => 10,
            'monthly' => 20,
            'overall' => 50,
        ],
    ];

    public function testInitException(): void
    {
        $key = 'sms_limits';

        $this->expectException(ValidationCodeConfigException::class);
        $this->expectExceptionMessage("\"${key}\" key does not exist.");

        $props = [
            'email_limits' => [],
        ];

        new ValidationCodeConfigs($props);
    }

    public function testGetCodeLimits(): void
    {
        $key = 'sms_limits';

        $codeLimits = new ValidationCodeLimitsConfig(self::CONFIGS[$key]);

        $validationLimitConfigs = new ValidationCodeConfigs(self::CONFIGS);

        $this->assertEquals(
            $codeLimits,
            $validationLimitConfigs->getCodeLimits($key)
        );
    }

    public function testGetCodeLimitsException(): void
    {
        $key = '2fa_limits';

        $this->expectException(ValidationCodeConfigException::class);
        $this->expectExceptionMessage("\"$key\" key does not exist");

        $validationLimitConfigs = new ValidationCodeConfigs(self::CONFIGS);

        $validationLimitConfigs->getCodeLimits($key);
    }
}
