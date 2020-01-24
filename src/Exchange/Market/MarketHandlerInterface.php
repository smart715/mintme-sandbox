<?php declare(strict_types = 1);

namespace App\Exchange\Market;

use App\Entity\User;
use App\Exchange\Market;
use App\Exchange\MarketInfo;
use App\Exchange\Order;

interface MarketHandlerInterface
{
    /** @return Order[] */
    public function getPendingSellOrders(Market $market, int $offset = 0, int $limit = 50): array;

    /** @return Order[] */
    public function getPendingBuyOrders(Market $market, int $offset = 0, int $limit = 50): array;

    /** @return Order[] */
    public function getExecutedOrders(Market $market, int $lastId = 0, int $limit = 50): array;

    /**
     * @param Market[] $markets
     * @return Order[]
     */
    public function getUserExecutedHistory(User $userId, array $markets, int $offset = 0, int $limit = 50): array;

    /**
     * @param Market[] $markets
     * @return Order[]
     */
    public function getPendingOrdersByUser(User $user, array $markets, int $offset = 0, int $limit = 50): array;

    public function getMarketInfo(Market $market, int $period = 86400): MarketInfo;

    /**
     * @return Market\Model\LineStat[]
     */
    public function getKLineStatDaily(Market $market): array;

    public function getExecutedOrder(Market $market, int $id, int $limit = 100): Order;
    public function getPendingOrder(Market $market, int $id): Order;
    public function getTradersByOrderPrice(Market $market, array $params = [], int $limit = 100): array;
}
