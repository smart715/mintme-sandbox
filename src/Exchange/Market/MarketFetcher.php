<?php

namespace App\Exchange\Market;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Exchange\Market;
use App\Exchange\Order;
use Money\Currency;
use Money\Money;

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
        try {
            $response = $this->jsonRpc->send(self::EXECUTED_ORDERS_METHOD, [
                $market->getHiddenName(),
                $limit,
                $offset,
            ]);
        } catch (FetchException $e) {
            return [];
        }

        if ($response->hasError()) {
            return [];
        }

        return $this->parseExecutedOrders($response->getResult(), $market);
    }

    public function getPendingOrders(Market $market, int $offset, int $limit, string $side): array
    {
        try {
            $response = $this->jsonRpc->send(self::PENDING_ORDERS_METHOD, [
                $market->getHiddenName(),
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

        return $this->parsePendingOrders($response->getResult(), $market);
    }

    /** @return Order[] */
    private function parsePendingOrders(array $result, Market $market): array
    {
        return array_map(static function (array $orderData) use ($market) {
            return new Order(
                $orderData['id'],
                $orderData['user'],
                null,
                $market,
                new Money(
                    $orderData['amount'],
                    new Currency($market->getCurrencySymbol())
                ),
                $orderData['side'],
                new Money(
                    $orderData['price'],
                    new Currency($market->getCurrencySymbol())
                ),
                Order::PENDING_STATUS,
                $orderData['mtime']
            );
        }, $result);
    }

    /** @return Order[] */
    private function parseExecutedOrders(array $result, Market $market): array
    {
        return array_map(static function (array $orderData) use ($market) {
            return new Order(
                $orderData['id'],
                $orderData['maker'],
                $orderData['taker'],
                $market,
                new Money(
                    $orderData['amount'],
                    new Currency($market->getCurrencySymbol())
                ),
                Order::SIDE_MAP[$orderData['type']],
                new Money(
                    $orderData['price'],
                    new Currency($market->getCurrencySymbol())
                ),
                ORDER::FINISHED_STATUS,
                $orderData['time']
            );
        }, $result);
    }
}
