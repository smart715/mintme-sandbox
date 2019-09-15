<?php declare(strict_types = 1);

namespace App\Communications;

use App\Communications\Exception\FetchException;
use Money\Money;

interface DeployCostFetcherInterface
{
    /**
     * @throws FetchException
     * @return Money
     */
    public function getDeployWebCost(): Money;
}
