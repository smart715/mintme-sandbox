<?php declare(strict_types = 1);

namespace App\Manager\Model\Profit;

/** @codeCoverageIgnore */
class TotalProfitModel extends AbstractProfitModel
{
    public function __construct(string $symbol, string $count = '0', string $profitInUsd = '0')
    {
        parent::__construct($symbol, $profitInUsd, $count);
    }
}
