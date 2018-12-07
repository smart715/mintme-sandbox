<?php

namespace App\Order;

use App\Entity\User;
use App\Exchange\Market;
use App\Order\Model\OrderInfo;

interface OrderListInterface
{
    /** @return OrderInfo[] */
    public function getSellPendingOrdersList(User $user, Market $market): array;

    /** @return OrderInfo[] */
    public function getBuyPendingOrdersList(User $user, Market $market): array;

    /** @return OrderInfo[] */
    public function getPendingOrdersList(User $user, Market $market, int $side): array;

    /** @return OrderInfo[] */
    public function getAllPendingOrders(Market $market, string $side): array;

    /** @return OrderInfo[] */
    public function getOrdersHistory(Market $market, int $offset = 0, int $limit = 20): array;
}
