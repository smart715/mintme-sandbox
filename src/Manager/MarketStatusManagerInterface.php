<?php declare(strict_types = 1);

namespace App\Manager;

use App\Exchange\Market;

interface MarketStatusManagerInterface
{
    public function getAllMarketsInfo(): array;

    /** @var Market[] */
    public function createMarketStatus(array $market): void;

    public function updateMarketStatus(Market $market): void;
}
