<?php declare(strict_types = 1);

namespace App\Exchange\Market;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpcInterface;
use App\Exchange\Config\Config;

class MarketFetcher implements MarketFetcherInterface
{
    public const SELL = 1;
    public const BUY = 2;
    private const BOOK_ORDERS_METHOD = 'order.book';
    private const PENDING_ORDERS_METHOD = 'order.pending';
    private const EXECUTED_ORDERS_METHOD = 'market.deals';
    private const USER_EXECUTED_HISTORY = 'market.user_deals';
    private const MARKET_STATUS = 'market.status';
    private const KLINE = 'market.kline';
    private const PENDING_ORDER_DETAIL_METHOD = 'order.pending_detail';

    /** @var JsonRpcInterface */
    private $jsonRpc;

    /** @var Config */
    private $config;

    public function __construct(JsonRpcInterface $jsonRpc, Config $config)
    {
        $this->jsonRpc = $jsonRpc;
        $this->config = $config;
    }

    public function getMarketInfo(string $market, int $period = 86400): array
    {
        try {
            $response = $this->jsonRpc->send(self::MARKET_STATUS, [
                $market,
                $period,
            ]);
        } catch (FetchException $e) {
            return [];
        }

        if ($response->hasError()) {
            return [];
        }

        return $response->getResult();
    }

    public function getExecutedOrders(string $market, int $lastId = 0, int $limit = 100): array
    {
        $response = $this->jsonRpc->send(self::EXECUTED_ORDERS_METHOD, [
            $market,
            $limit,
            $lastId,
        ]);

        if ($response->hasError()) {
            throw new FetchException($response->getError()['message'] ?? '');
        }

        return array_map(function (array $order) {
            $order['maker_id'] -= $this->config->getOffset();

            $order['taker_id'] -= $this->config->getOffset();

            return $order;
        }, $response->getResult());
    }

    public function getUserExecutedHistory(int $userId, string $market, int $offset = 0, int $limit = 100): array
    {
        $response = $this->jsonRpc->send(self::USER_EXECUTED_HISTORY, [
            $userId + $this->config->getOffset(),
            $market,
            $offset,
            $limit,
        ]);

        if ($response->hasError()) {
            throw new FetchException($response->getError()['message'] ?? '');
        }

        return array_map(function (array $order) {
            $order['id'] -= $this->config->getOffset();

            return $order;
        }, $response->getResult()['records']);
    }

    public function getPendingOrdersByUser(int $userId, string $market, int $offset = 0, int $limit = 100): array
    {
        $response = $this->jsonRpc->send(self::PENDING_ORDERS_METHOD, [
            $userId + $this->config->getOffset(),
            $market,
            $offset,
            $limit,
        ]);

        if ($response->hasError()) {
            throw new FetchException($response->getError()['message'] ?? '');
        }

        return array_map(function (array $order) {
            $order['user'] -= $this->config->getOffset();

            return $order;
        }, $response->getResult()['records']);
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
            throw new FetchException($response->getError()['message'] ?? '');
        }

        return array_map(function (array $order) {
            $order['user'] -= $this->config->getOffset();

            return $order;
        }, $response->getResult()['orders']);
    }

    public function getPendingOrder(string $market, int $id): array
    {
        $response = $this->jsonRpc->send(self::PENDING_ORDER_DETAIL_METHOD, [
            $market,
            $id,
        ]);

        if ($response->hasError()) {
            throw new FetchException($response->getError()['message'] ?? '');
        }

        $order = $response->getResult();

        $order['user'] -= $this->config->getOffset();

        return $order;
    }

    public function getKLineStat(string $market, int $start, int $end, int $interval): array
    {
        $response = $this->jsonRpc->send(self::KLINE, [
            $market,
            $start,
            $end,
            $interval,
        ]);

        if ($response->hasError()) {
            throw new FetchException($response->getError()['message'] ?? '');
        }

        return $response->getResult();
    }
}
