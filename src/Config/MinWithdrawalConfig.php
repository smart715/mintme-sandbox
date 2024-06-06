<?php declare(strict_types = 1);

namespace App\Config;

class MinWithdrawalConfig
{
    private array $minWithdrawals;

    public function __construct(array $minWithdrawals)
    {
        $this->minWithdrawals = $minWithdrawals;
    }

    public function getMinWithdrawalByCryptoSymbol(string $symbol): ?float
    {
        return $this->minWithdrawals[$symbol] ?? null;
    }
}
