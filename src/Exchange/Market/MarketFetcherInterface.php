<?php

namespace App\Exchange\Market;

use App\Exchange\Order;

interface MarketFetcherInterface
{
    /** @return Order[] */
    public function getPendingOrders(string $market, int $offset, int $limit, int $side): array;

    /** @return Order[] */
    public function getExecutedOrders(string $market, int $offset = 0, int $limit = 100): array;

    /** @return Order[] */
    public function getUserExecutedHistory(int $userId, string $market, int $offset = 0, int $limit = 100): array;

    /** @return Order[] */
    public function getPendingOrdersByUser(int $userId, string $market, int $offset = 0, int $limit = 100): array;
}
