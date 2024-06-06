<?php declare(strict_types = 1);

namespace App\Config;

/** @codeCoverageIgnore  */
class TradingConfig
{
    private array $tradingConfig;

    public function __construct(array $tradingConfig)
    {
        $this->tradingConfig = $tradingConfig;
    }

    public function getMarketsOnFirstPage(): int
    {
        return (int)$this->tradingConfig['markets_on_first_page'];
    }

    public function getMarketsPerPage(): int
    {
        return (int)$this->tradingConfig['markets_per_page'];
    }
}
