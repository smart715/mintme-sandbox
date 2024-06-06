<?php declare(strict_types = 1);

namespace App\Config;

use App\Exception\ValidationCodeConfigException;

class ValidationCodeConfigs
{
    public const SMS = 'sms_limits';
    public const EMAIL = 'email_limits';
    protected array $availableConfigs = [ //phpcs:ignore
        self::SMS, self::EMAIL,
    ];
    protected array $validationCodeConfigs;
    public function __construct(array $validationCodeConfigs)
    {
        foreach ($this->availableConfigs as $key) {
            if (!array_key_exists($key, $validationCodeConfigs)) {
                throw new ValidationCodeConfigException("\"$key\" key does not exist.");
            }

            $this->validationCodeConfigs[$key] = new ValidationCodeLimitsConfig(
                $validationCodeConfigs[$key]
            );
        }
    }

    public function getCodeLimits(string $configKey): ValidationCodeLimitsConfig
    {
        if (!in_array($configKey, $this->availableConfigs)) {
            throw new ValidationCodeConfigException("\"$configKey\" key does not exist");
        }

        return $this->validationCodeConfigs[$configKey];
    }
}
