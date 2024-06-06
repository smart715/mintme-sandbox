<?php declare(strict_types = 1);

namespace App\Config;

use App\Exception\FailedLoginConfigException;

/** @codeCoverageIgnore */
class FailedLoginConfig
{
    private array $failedLoginConfig;

    public function __construct(array $failedLoginConfig)
    {
        $this->failedLoginConfig = $failedLoginConfig;
    }

    public function getMaxChances(): int
    {
        if (!isset($this->failedLoginConfig['max_chances'])) {
            throw new FailedLoginConfigException('max_chances key does not exist');
        }

        return $this->failedLoginConfig['max_chances'];
    }

    public function getMaxHours(): int
    {
        if (!isset($this->failedLoginConfig['max_hours'])) {
            throw new FailedLoginConfigException('max_hours key does not exist');
        }

        return $this->failedLoginConfig['max_hours'];
    }
}
