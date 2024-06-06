<?php declare(strict_types = 1);

namespace App\Manager\Model\Profit;

/** @codeCoverageIgnore */
class ProfitInternalTransactionModel extends AbstractProfitModel
{
    private string $profit;

    public function __construct(
        string $symbol,
        string $count = '0',
        string $profit = '0',
        string $profitInUsd = '0'
    ) {
        parent::__construct($symbol, $profitInUsd, $count);

        $this->profit = $profit;
    }

    public function getProfit(): string
    {
        return $this->profit;
    }
}
