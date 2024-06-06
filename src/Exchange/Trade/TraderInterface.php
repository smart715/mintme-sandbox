<?php declare(strict_types = 1);

namespace App\Exchange\Trade;

use App\Entity\User;
use App\Exchange\Deal;
use App\Exchange\Market;
use App\Exchange\Order;

interface TraderInterface
{
    public function placeOrder(
        Order $order,
        bool $updateTokenOrCrypto = true,
        bool $isInitOrderType = false
    ): TradeResult;
    public function executeOrder(Order $order, bool $updateTokenOrCrypto = true): TradeResult;
    public function cancelOrder(Order $order): TradeResult;

    /** @return Order[] */
    public function getFinishedOrders(User $user, Market $market, array $filterOptions = []): array;

    /** @return Order[] */
    public function getPendingOrders(User $user, Market $market, array $filterOptions = []): array;

    /** @return Deal[] */
    public function getOrderDetails(Order $order, int $offset = 0, int $limit = 100): array;

    public function getFinishedOrder(Deal $deal): ?Order;

    public function getPendingOrder(Deal $deal): ?Order;
}
