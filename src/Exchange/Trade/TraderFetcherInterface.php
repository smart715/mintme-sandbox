<?php declare(strict_types = 1);

namespace App\Exchange\Trade;

interface TraderFetcherInterface
{
    public function placeOrder(
        int $userId,
        string $tokenName,
        int $side,
        string $amount,
        string $price,
        string $takerFee,
        string $makerFee,
        int $referralId,
        string $referralFee
    ): PlaceOrderResult;

    public function executeOrder(
        int $userId,
        string $market,
        int $side,
        string $amount,
        string $fee,
        int $referralId,
        string $referralFee
    ): TradeResult;

    public function cancelOrder(int $userId, string $marketName, int $orderId): TradeResult;

    /** @return mixed[] */
    public function getFinishedOrders(
        int $userId,
        string $marketName,
        int $startTime,
        int $endTime,
        int $offset,
        int $limit,
        int $side
    ): array;

    /** @return mixed[] */
    public function getPendingOrders(
        int $userId,
        string $marketName,
        int $offset,
        int $limit,
        int $side
    ): array;

    /** @return mixed[] */
    public function getFinishedOrderDetails(int $orderId): array;

    /** @return mixed[] */
    public function getPendingOrderDetails(string $marketName, int $orderId): array;

    /** @return mixed[] */
    public function getOrderDetails(int $orderId, int $offset, int $limit): array;
}
