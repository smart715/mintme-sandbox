<?php declare(strict_types = 1);

namespace App\Config;

use App\Exception\FailedLoginConfigException;

/** @codeCoverageIgnore */
class BlacklistIpConfig
{
    private array $blacklistIpConfig;

    public function __construct(array $blacklistIpConfig)
    {
        if (!isset($blacklistIpConfig['blacklist_ip'])) {
            throw new FailedLoginConfigException('blacklist_ip key does not exist');
        }

        $this->blacklistIpConfig = $blacklistIpConfig['blacklist_ip'];
    }

    public function getMaxHours(): int
    {
        if (!isset($this->blacklistIpConfig['max_hours'])) {
            throw new FailedLoginConfigException('max_hours key does not exist');
        }

        return $this->blacklistIpConfig['max_hours'];
    }

    public function getMaxChances(): int
    {
        if (!isset($this->blacklistIpConfig['max_chances'])) {
            throw new FailedLoginConfigException('max_chances key does not exist');
        }

        return $this->blacklistIpConfig['max_chances'];
    }

    public function getDays(): int
    {
        if (!isset($this->blacklistIpConfig['days'])) {
            throw new FailedLoginConfigException('days key does not exist');
        }

        return $this->blacklistIpConfig['days'];
    }
}
