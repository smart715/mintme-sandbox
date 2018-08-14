<?php

namespace App\Exchange\Trade\Config;

class LimitOrderConfig
{
    /** @var float */
    private $takerFeeRate;

    /** @var float */
    private $makerFeeRate;

    public function __construct(float $takerFeeRate, float $makerFeeRate)
    {
        $this->takerFeeRate = $takerFeeRate;
        $this->makerFeeRate = $makerFeeRate;
    }

    public function getTakerFeeRate(): float
    {
        return $this->takerFeeRate;
    }

    public function getMakerFeeRate(): float
    {
        return $this->makerFeeRate;
    }
}
