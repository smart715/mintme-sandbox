<?php declare(strict_types = 1);

namespace App\Exchange\Trade\Config;

use App\Exchange\Market;

/** @codeCoverageIgnore */
class LimitOrderConfig
{
    private array $feeRates;

    public function __construct(array $feeRates)
    {
        $this->feeRates = $feeRates;
    }

    public function getFeeTokenRate(): string
    {
        return (string)$this->feeRates['token'];
    }

    public function getFeeCryptoRate(): string
    {
        return (string)$this->feeRates['coin'];
    }

    public function getFeeRateByMarket(Market $market): string
    {
        if ($market->isTokenMarket()) {
            return $this->getFeeTokenRate();
        }

        return $this->getFeeCryptoRate();
    }
}
