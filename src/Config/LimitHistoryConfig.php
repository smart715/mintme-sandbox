<?php declare(strict_types = 1);

namespace App\Config;

class LimitHistoryConfig
{
    private int $limitMonths;

    public function __construct(int $limitMonths)
    {
        $this->limitMonths = $limitMonths;
    }

    public function getFromDate(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now - ' . $this->limitMonths . 'month');
    }
}
