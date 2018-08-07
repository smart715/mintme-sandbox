<?php

namespace App\Exchange\Trade;

use App\Entity\User;
use App\ValueObject\Market;
use App\ValueObject\Order;

interface TraderInterface
{
    public function placeOrder(Order $order): TradeResult;
    public function cancelOrder(Order $order): TradeResult;

    /** @return Order[] */
    public function getFinishedOrders(User $user, Market $market, array $filterOptions = []): array;

    /** @return Order[] */
    public function getPendingOrders(User $user, Market $market, array $filterOptions = []): array;
}
