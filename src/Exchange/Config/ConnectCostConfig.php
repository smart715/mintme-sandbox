<?php declare(strict_types = 1);

namespace App\Exchange\Config;

use App\Utils\Symbols;

/** @codeCoverageIgnore */
class ConnectCostConfig
{
    private array $connectCosts;
    private array $connectFees;

    public function __construct(
        array $connectCosts,
        array $connectFees
    ) {
        $connectCosts[Symbols::WEB] = $connectCosts[Symbols::MINTME];
        $connectFees[Symbols::WEB] = 0;

        $this->connectCosts = $connectCosts;
        $this->connectFees = $connectFees;
    }

    public function getConnectFee(string $symbol): float
    {
        return $this->connectFees[$symbol] ?? 0;
    }

    public function getConnectCost(string $symbol): float
    {
        if (!isset($this->connectCosts[$symbol])) {
            throw new \InvalidArgumentException();
        }

        return $this->connectCosts[$symbol];
    }

    public function getSymbols(): array
    {
        return array_keys($this->connectCosts);
    }
}
