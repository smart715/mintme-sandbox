<?php declare(strict_types = 1);

namespace App\Exchange\Trade\Config;

/** @codeCoverageIgnore */
class IgnoreRequestDelay
{
    /** @var string[] */
    private array $ips;

    public function __construct(array $ips)
    {
        $this->ips = $ips;
    }

    /** @return string[] */
    public function ips(): array
    {
        return $this->ips;
    }

    public function hasIp(string $ip): bool
    {
        return in_array($ip, $this->ips);
    }
}
