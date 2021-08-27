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
    public function getDeployCost(string $symbol): Money;

    /**
     * @throws FetchException
     * @return array
     */
    public function getDeployCosts(): array;

    public function getDeployCostReferralReward(string $symbol): Money;
}
