<?php

namespace App\Exchange\Market;

use App\Exchange\Market;

interface MarketFetcherInterface
{
    public function getPendingSellOrders(Market $market, int $offset = 0, int $limit = 100): array;

    public function getPendingBuyOrders(Market $market, int $offset = 0, int $limit = 100): array;

    public function getExecutedOrders(Market $market, int $offset = 0, int $limit = 100): array;
}
