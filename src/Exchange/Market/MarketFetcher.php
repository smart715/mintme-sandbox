<?php

namespace App\Exchange\Market;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Exchange\Deal;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;

class MarketFetcher
{
    private const SELL = 'sell';
    private const BUY = 'buy';
    private const PENDING_ORDERS_METHOD = 'order.book';
    private const EXECUTED_ORDERS_METHOD = 'market.deals';
    private const USER_EXECUTED_HISTORY = 'market.user_deals';

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
    
    public function getUserExecutedHistory(
        int $userId,
        Market $market,
        MoneyWrapperInterface $moneyWrapper,
        int $offset = 0,
        int $limit = 100
    ): array {
        try {
            $response = $this->jsonRpc->send(self::USER_EXECUTED_HISTORY, [
                $userId,
                $market->getHiddenName(),
                $offset,
                $limit,
            ]);
        } catch (FetchException $e) {
            return [];
        }

        if ($response->hasError()) {
            return [];
        }
        return $this->parseExecutedHistory($response->getResult(), $market, $moneyWrapper);
    }

    private function getPendingOrders(Market $market, int $offset, int $limit, string $side): array
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
    
    /** @return Deal[] */
    private function parseExecutedHistory(array $result, Market $market, MoneyWrapperInterface $moneyWrapper): array
    {
        return array_map(static function (array $dealData) use ($market, $moneyWrapper) {
            return new Deal(
                $dealData['id'],
                $dealData['time'],
                $dealData['user'],
                $dealData['side'],
                $dealData['role'],
                $moneyWrapper->parse(
                    $dealData['amount'],
                    new Currency($market->getCurrencySymbol())
                ),
                $moneyWrapper->parse(
                    $dealData['price'],
                    new Currency($market->getCurrencySymbol())
                ),
                $moneyWrapper->parse(
                    $dealData['deal'],
                    new Currency($market->getCurrencySymbol())
                ),
                $moneyWrapper->parse(
                    $dealData['fee'],
                    new Currency($market->getCurrencySymbol())
                ),
                $dealData['deal_order_id'],
                $market->getHiddenName()
            );
        }, $result['records']);
    }
}
