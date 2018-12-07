<?php

namespace App\Exchange\Market;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;

class MarketFetcher
{
    private const SELL = 1;
    private const BUY = 2;
    private const PENDING_ORDERS_METHOD = 'order.book';
    private const EXECUTED_ORDERS_METHOD = 'market.deals';

    /** @var JsonRpcInterface */
    private $jsonRpc;

    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    public function __construct(JsonRpcInterface $jsonRpc, MoneyWrapperInterface $moneyWrapper)
    {
        $this->moneyWrapper = $moneyWrapper;
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

    public function getPendingOrders(Market $market, int $offset, int $limit, int $side): array
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
        $moneyWrapper = $this->moneyWrapper;
        return array_map(static function (array $orderData) use ($market, $moneyWrapper) {
            return new Order(
                $orderData['id'],
                $orderData['user'],
                null,
                $market,
                $moneyWrapper->parse(
                    $orderData['amount'],
                    new Currency($market->getCurrencySymbol())
                ),
                $orderData['side'],
                $moneyWrapper->parse(
                    $orderData['price'],
                    new Currency($market->getCurrencySymbol())
                ),
                Order::PENDING_STATUS,
                $orderData['mtime']
            );
        }, $result['orders']);
    }

    /** @return Order[] */
    private function parseExecutedOrders(array $result, Market $market): array
    {
        $moneyWrapper = $this->moneyWrapper;
        return array_map(static function (array $orderData) use ($market, $moneyWrapper) {
            return new Order(
                $orderData['id'],
                $orderData['maker_id'],
                $orderData['taker_id'],
                $market,
                $moneyWrapper->parse(
                    $orderData['amount'],
                    new Currency($market->getCurrencySymbol())
                ),
                Order::SIDE_MAP[$orderData['type']],
                $moneyWrapper->parse(
                    $orderData['price'],
                    new Currency($market->getCurrencySymbol())
                ),
                ORDER::FINISHED_STATUS,
                $orderData['time']
            );
        }, $result);
    }
}
