<?php declare(strict_types = 1);

namespace App\Communications;

use App\Communications\Exception\FetchException;

interface TokenPromotionCostFetcherInterface
{
    /**
     * @param string $tariffDuration
     * @return array
     * @throws FetchException
     */
    public function getCost(string $tariffDuration): array;

    /**
     * @return array
     * @throws FetchException
     */
    public function getCosts(): array;
}
