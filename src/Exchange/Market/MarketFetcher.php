<?php

namespace App\Exchange\Market;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;

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
        $response = $this->jsonRpc->send(self::EXECUTED_ORDERS_METHOD, [
            $market,
            $limit,
            $offset,
        ]);

        if ($response->hasError()) {
            throw new FetchException();
        }

        return $response->getResult();
    }

    public function getUserExecutedHistory(int $userId, string $market, int $offset = 0, int $limit = 100): array
    {
        $response = $this->jsonRpc->send(self::USER_EXECUTED_HISTORY, [
            $userId,
            $market,
            $offset,
            $limit,
        ]);

        if ($response->hasError()) {
            throw new FetchException();
        }
        return $response->getResult();
    }

    public function getPendingOrdersByUser(int $userId, string $market, int $offset = 0, int $limit = 100): array
    {
        $response = $this->jsonRpc->send(self::PENDING_ORDERS_METHOD, [
            $userId,
            $market,
            $offset,
            $limit,
        ]);

        if ($response->hasError()) {
            throw new FetchException();
        }

        return $response->getResult()['records'];
    }

    public function getPendingOrders(string $market, int $offset, int $limit, int $side): array
    {
        $response = $this->jsonRpc->send(self::BOOK_ORDERS_METHOD, [
            $market,
            $side,
            $offset,
            $limit,
        ]);

        if ($response->hasError()) {
            throw new FetchException();
        }
        return $response->getResult();
    }
}
