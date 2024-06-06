<?php declare(strict_types = 1);

namespace App\Config;

use App\Exception\ValidationCodeConfigException;

class ValidationCodeLimitsConfig
{
    private array $validationCodeLimitsConfig;

    public function __construct(array $validationCodeLimitsConfig)
    {
        $this->validationCodeLimitsConfig = $validationCodeLimitsConfig;
    }

    public function getOverall(): int
    {
        if (!isset($this->validationCodeLimitsConfig['overall'])) {
            throw new ValidationCodeConfigException('overall key does not exist');
        }

        return $this->validationCodeLimitsConfig['overall'];
    }

    public function getDaily(): int
    {
        if (!isset($this->validationCodeLimitsConfig['daily'])) {
            throw new ValidationCodeConfigException('daily key does not exist');
        }

        return $this->validationCodeLimitsConfig['daily'];
    }

    public function getWeekly(): int
    {
        if (!isset($this->validationCodeLimitsConfig['weekly'])) {
            throw new ValidationCodeConfigException('weekly key does not exist');
        }

        return $this->validationCodeLimitsConfig['weekly'];
    }

    public function getMonthly(): int
    {
        if (!isset($this->validationCodeLimitsConfig['monthly'])) {
            throw new ValidationCodeConfigException('monthly key does not exist');
        }

        return $this->validationCodeLimitsConfig['monthly'];
    }

    public function getFailed(): int
    {
        if (!isset($this->validationCodeLimitsConfig['failed'])) {
            throw new ValidationCodeConfigException('failed key does not exist');
        }

        return $this->validationCodeLimitsConfig['failed'];
    }
}
