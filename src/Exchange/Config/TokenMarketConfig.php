<?php declare(strict_types = 1);

namespace App\Exchange\Config;

/** @codeCoverageIgnore */
class TokenMarketConfig
{
    private array $costs;

    public function __construct(array $costs)
    {
        $this->costs = $costs;
    }

    public function getTokenMarketCost(string $symbol): float
    {
        if (!isset($this->costs[$symbol])) {
            throw new \InvalidArgumentException();
        }

        return $this->costs[$symbol];
    }

    public function getAllMarketCosts(): array
    {
        return $this->costs;
    }
}
