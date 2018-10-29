<?php

namespace App\Exchange\Balance\Model;

class BalanceResultContainer
{
    /**
     * @var mixed[]
     */
    private $balances;

    private function __construct(array $balances)
    {
        $this->balances = $balances;
    }

    public function getAll(): array
    {
        return array_map(function (array $balance) {
            return BalanceResult::success(
                (float)$balance['available'],
                (float)$balance['freeze']
            );
        }, $this->balances);
    }

    public function get(string $name): BalanceResult
    {
        return $this->getAll()[$name] ?? BalanceResult::fail();
    }

    public static function success(array $balances): self
    {
        return new self($balances);
    }

    public static function fail(): self
    {
        return new self([]);
    }
}
