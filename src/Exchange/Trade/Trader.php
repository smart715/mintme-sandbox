<?php

namespace App\Exchange\Trade;

use App\Communications\Exception\FetchException;
use App\Communications\JsonRpc;
use App\Entity\User;
use App\ValueObject\Market;
use App\ValueObject\Order;

class Trader implements TraderInterface
{
    private const PLACE_ORDER_METHOD = 'order.put_limit';
    private const CANCEL_ORDER_METHOD = 'order.cancel';
    private const FINISHED_ORDERS_METHOD = 'order.finished';
    private const PENDING_ORDERS_METHOD = 'order.pending';

    /** @var JsonRpc */
    private $jsonRpc;

    /** @var Config\LimitOrderConfig */
    private $config;

    public function __construct(JsonRpc $jsonRpc, Config\LimitOrderConfig $config)
    {
        $this->jsonRpc = $jsonRpc;
        $this->config = $config;
    }

    public function placeOrder(Order $order): TradeResult
    {
        $params = [
            $order->getUser()->getId(),
            $order->getMarket()->getHiddenName(),
            $order->getSide(),
            $order->getAmount(),
            $order->getPrice(),
            (string) $this->config->getTakerFeeRate(),
            (string) $this->config->getMakerFeeRate(),
            '',
        ];

        try {
            $response = $this->jsonRpc->send(self::PLACE_ORDER_METHOD, [$params]);
        } catch (FetchException $e) {
            return new TradeResult(TradeResult::FAILED);
        }

        if ($response->hasError()) {
            return 10 === $response->getError()['code']
                ? new TradeResult(TradeResult::INSUFFICIENT_BALANCE)
                : new TradeResult(TradeResult::FAILED);
        }

        return new TradeResult(TradeResult::SUCCESS);
    }

    public function cancelOrder(Order $order): TradeResult
    {
        $params = [
            $order->getUser()->getId(),
            $order->getMarket()->getHiddenName(),
            $order->getId(),
        ];

        try {
            $response = $this->jsonRpc->send(self::CANCEL_ORDER_METHOD, [$params]);
        } catch (FetchException $e) {
            return new TradeResult(TradeResult::FAILED);
        }

        if ($response->hasError()) {
            return $this->getCancelOrderErrorResult($response->getError()['code']);
        }

        return new TradeResult(TradeResult::SUCCESS);
    }

    /**
     * @inheritdoc
     */
    public function getFinishedOrders(User $user, Market $market, array $filterOptions = []): array
    {
        $options = new Config\OrderFilterConfig();
        $options->merge($filterOptions);

        $params = [
            $user->getId(),
            $market->getHiddenName(),
            $options['start_time'],
            $options['end_time'],
            $options['offset'],
            $options['limit'],
            Order::SIDE_MAP[$options['side']],
        ];

        try {
            $response = $this->jsonRpc->send(self::FINISHED_ORDERS_METHOD, [$params]);
        } catch (FetchException $e) {
            return [];
        }

        if ($response->hasError()) {
            return [];
        }

        $orders = array_map(function (array $rawOrder) use ($user, $market) {
            return $this->createFinishedOrder($rawOrder, $user, $market);
        }, $response->getResult()['records']);

        return $orders;
    }

    /**
     * @inheritdoc
     */
    public function getPendingOrders(User $user, Market $market, array $filterOptions = []): array
    {
        $options = new Config\OrderFilterConfig();
        $options->merge($filterOptions);

        $params = [
            $user->getId(),
            $market->getHiddenName(),
            $options['offset'],
            $options['limit'],
            Order::SIDE_MAP[$options['side']],
        ];

        try {
            $response = $this->jsonRpc->send(self::PENDING_ORDERS_METHOD, [$params]);
        } catch (FetchException $e) {
            return [];
        }

        if ($response->hasError()) {
            return [];
        }

        $orders = array_map(function (array $rawOrder) use ($user, $market) {
            return $this->createPendingOrder($rawOrder, $user, $market);
        }, $response->getResult()['records']);

        return $orders;
    }

    private function createFinishedOrder(array $orderData, User $user, Market $market): Order
    {
        return new Order(
            $orderData['id'],
            $user,
            $market,
            $orderData['amount'],
            $orderData['side'],
            $orderData['price'],
            Order::FINISHED_STATUS
        );
    }

    private function createPendingOrder(array $orderData, User $user, Market $market): Order
    {
        return new Order(
            $orderData['id'],
            $user,
            $market,
            $orderData['amount'],
            $orderData['side'],
            $orderData['price'],
            Order::PENDING_STATUS
        );
    }

    private function getCancelOrderErrorResult(int $errorCode): TradeResult
    {
        $errorMapping = [
            10 => TradeResult::ORDER_NOT_FOUND,
            11 => TradeResult::USER_NOT_MATCH,
        ];

        $result = array_key_exists($errorCode, $errorMapping)
            ? new TradeResult($errorMapping[$errorCode])
            : new TradeResult(TradeResult::FAILED);

        return $result;
    }
}
