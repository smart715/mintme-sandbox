<?php

namespace App\Exchange\Balance\Model;

class BalanceResult
{
    /** @var float */
    private $abailable;

    /** @var float */
    private $freeze;

    private function __construct(float $abailable, float $freeze)
    {
        $this->abailable = $abailable;
        $this->freeze = $freeze;
    }

    public function getAvailable(): float
    {
        return $this->abailable;
    }

    public function getFreeze(): float
    {
        return $this->freeze;
    }

    public function isFailed(): bool
    {
        return empty($this->abailable) && empty($this->freeze);
    }

    public static function success(float $abailable, float $freeze): self
    {
        return new self($abailable, $freeze);
    }

    public static function fail(): self
    {
        return new self(0, 0);
    }
}
