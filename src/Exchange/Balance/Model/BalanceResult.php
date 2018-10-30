<?php

namespace App\Exchange\Balance\Model;

class BalanceResult
{
    /** @var float */
    private $available;

    /** @var float */
    private $freeze;

    private function __construct(float $abailable, float $freeze)
    {
        $this->available = $abailable;
        $this->freeze = $freeze;
    }

    public function getAvailable(): float
    {
        return $this->available;
    }

    public function getFreeze(): float
    {
        return $this->freeze;
    }

    public function isFailed(): bool
    {
        return empty($this->available) && empty($this->freeze);
    }

    public static function success(float $available, float $freeze): self
    {
        return new self($available, $freeze);
    }

    public static function fail(): self
    {
        return new self(0, 0);
    }
}
