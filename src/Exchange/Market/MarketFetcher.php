<?php

namespace App\Exchange\Market;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Entity\User;
use App\Exchange\Deal;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;

class MarketFetcher implements MarketFetcherInterface
{
    public const SELL = 1;
    public const BUY = 2;
    private const BOOK_ORDERS_METHOD = 'order.book';
    private const PENDING_ORDERS_METHOD = 'order.pending';
    private const EXECUTED_ORDERS_METHOD = 'market.deals';
    private const USER_EXECUTED_HISTORY = 'market.user_deals';

    /** @var JsonRpcInterface */
    private $jsonRpc;

    public function __construct(JsonRpcInterface $jsonRpc)
    {
        $this->jsonRpc = $jsonRpc;
    }

    public function getExecutedOrders(string $market, int $offset = 0, int $limit = 100): array
    {
        try {
            $response = $this->jsonRpc->send(self::EXECUTED_ORDERS_METHOD, [
                $market,
                $limit,
                $offset,
            ]);
        } catch (FetchException $e) {
            return [];
        }

        if ($response->hasError()) {
            return [];
        }

        return $response->getResult();
    }

    public function getUserExecutedHistory(int $userId, string $market, int $offset = 0, int $limit = 100): array
    {
        try {
            $response = $this->jsonRpc->send(self::USER_EXECUTED_HISTORY, [
                $userId,
                $market,
                $offset,
                $limit,
            ]);
        } catch (FetchException $e) {
            return [];
        }

        if ($response->hasError()) {
            return [];
        }
        return $response->getResult();
    }

    public function getPendingOrdersByUser(int $userId, string $market, int $offset = 0, int $limit = 100): array
    {
        try {
            $response = $this->jsonRpc->send(self::PENDING_ORDERS_METHOD, [
                $userId,
                $market,
                $offset,
                $limit,
            ]);
        } catch (FetchException $e) {
            return [];
        }

        if ($response->hasError()) {
            return [];
        }

        return $response->getResult()['records'];
    }

    public function getPendingOrders(string $market, int $offset, int $limit, int $side): array
    {
        try {
            $response = $this->jsonRpc->send(self::BOOK_ORDERS_METHOD, [
                $market,
                $side,
                $offset,
                $limit,
            ]);
        } catch (FetchException $e) {
            return [];
        }

        if ($response->hasError()) {
            return [];
        }
        return $response->getResult();
    }
}
