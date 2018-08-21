<?php

namespace App\Exchange\Market;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Exchange\Market;
use App\Exchange\Order;

class MarketFetcher
{
    private const SELL = 'sell';
    private const BUY = 'buy';
    private const PENDING_ORDERS_METHOD = 'order.book';
    private const EXECUTED_ORDERS_METHOD = 'market.deals';

    /** @var JsonRpcInterface */
    private $jsonRpc;

    public function __construct(JsonRpcInterface $jsonRpc)
    {
        $this->jsonRpc = $jsonRpc;
    }

    public function getPendingSellOrders(Market $market, int $offset = 0, int $limit = 100): array
    {
        return $this->getPendingOrders($market, $offset, $limit, self::SELL);
    }

    public function getPendingBuyOrders(Market $market, int $offset = 0, int $limit = 100): array
    {
        return $this->getPendingOrders($market, $offset, $limit, self::BUY);
    }

    public function getExecutedOrders(Market $market, int $offset = 0, int $limit = 100): array
    {
        $params = [
            $market->getHiddenName(),
            $limit,
            $offset,
        ];

        try {
            $response = $this->jsonRpc->send(self::EXECUTED_ORDERS_METHOD, [$params]);
        } catch (FetchException $e) {
            return [];
        }

        if ($response->hasError()) {
            return [];
        }

        return $this->parseExecutedOrders($response->getResult(), $market);
    }

    private function getPendingOrders(Market $market, int $offset, int $limit, string $side): array
    {
        $params = [
            $market->getHiddenName(),
            $side,
            $offset,
            $limit,
        ];

        try {
            $response = $this->jsonRpc->send(self::PENDING_ORDERS_METHOD, [$params]);
        } catch (FetchException $e) {
            return [];
        }

        if ($response->hasError()) {
            return [];
        }

        return $this->parsePendingOrders($response->getResult(), $market);
    }

    /** @return Order[] */
    private function parsePendingOrders(array $result, Market $market): array
    {
        return array_map(function (array $orderData) use ($market) {
            return new Order(
                $orderData['id'],
                $orderData['user'],
                null,
                $market,
                $orderData['amount'],
                $orderData['side'],
                $orderData['price'],
                Order::PENDING_STATUS,
                $orderData['mtime']
            );
        }, $result);
    }

    /** @return Order[] */
    private function parseExecutedOrders(array $result, Market $market): array
    {
        return array_map(function (array $orderData) use ($market) {
            return new Order(
                $orderData['id'],
                $orderData['maker'],
                $orderData['taker'],
                $market,
                $orderData['amount'],
                Order::SIDE_MAP[$orderData['type']],
                $orderData['price'],
                ORDER::FINISHED_STATUS,
                $orderData['time']
            );
        }, $result);
    }
}