<?php declare(strict_types = 1);

namespace App\Communications;

use App\Communications\Exception\FetchException;
use Money\Money;

interface ConnectCostFetcherInterface
{
    /**
     * @param string $symbol
     * @return Money
     * @throws FetchException
     */
    public function getCost(string $symbol): Money;

    /**
     * @throws FetchException
     * @return array
     */
    public function getCosts(): array;
}
